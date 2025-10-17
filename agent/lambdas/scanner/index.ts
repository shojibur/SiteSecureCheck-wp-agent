import type { Handler } from 'aws-lambda';
import chromium from '@sparticuz/chromium';
import puppeteer from 'puppeteer-core';

interface ScanRequest {
  domain: string;
  region_mode?: string;
}

interface ScanResult {
  domain: string;
  timestamp: string;
  browser_data: {
    tls: {
      https: boolean;
      hsts: boolean;
      hsts_value?: string;
    };
    headers: Record<string, string>;
    cookies: Array<{
      name: string;
      secure: boolean;
      httpOnly: boolean;
      sameSite: string;
      domain: string;
    }>;
    third_parties: string[];
    csp_present: boolean;
    csp_value?: string;
    response_headers: Record<string, string>;
    page_title: string;
    meta_tags: Record<string, string>;
    forms: Array<{
      action: string;
      method: string;
      hasHttps: boolean;
    }>;
    external_scripts: string[];
    external_styles: string[];
  };
  security_observations: string[];
}

export const handler: Handler<ScanRequest, ScanResult> = async (event) => {
  const { domain, region_mode = 'GDPR' } = event;

  console.log(`Starting scan for ${domain} with region mode: ${region_mode}`);

  const browser = await puppeteer.launch({
    args: chromium.args,
    defaultViewport: chromium.defaultViewport,
    executablePath: await chromium.executablePath(),
    headless: chromium.headless
  });

  try {
    const page = await browser.newPage();
    const observations: string[] = [];
    const responseHeaders: Record<string, string> = {};
    const externalScripts: string[] = [];
    const externalStyles: string[] = [];
    const thirdPartyDomains = new Set<string>();

    // Intercept network requests
    await page.setRequestInterception(true);
    page.on('request', (request) => {
      const url = request.url();
      try {
        const reqUrl = new URL(url);
        const reqDomain = reqUrl.hostname;
        const mainDomain = new URL(`https://${domain}`).hostname;

        if (reqDomain !== mainDomain && !reqDomain.endsWith(`.${mainDomain}`)) {
          thirdPartyDomains.add(reqDomain);
        }

        if (url.endsWith('.js')) externalScripts.push(url);
        if (url.endsWith('.css')) externalStyles.push(url);
      } catch (e) {
        // Invalid URL, skip
      }
      request.continue();
    });

    // Navigate and capture response
    const response = await page.goto(`https://${domain}`, {
      waitUntil: 'networkidle0',
      timeout: 30000
    });

    if (!response) {
      throw new Error('No response received');
    }

    // Extract response headers
    const headers = response.headers();
    Object.keys(headers).forEach(key => {
      responseHeaders[key] = headers[key];
    });

    // Check TLS/HTTPS
    const isHttps = response.url().startsWith('https://');
    const hstsHeader = headers['strict-transport-security'];
    const hasHsts = !!hstsHeader;

    if (!isHttps) {
      observations.push('Site does not use HTTPS');
    }
    if (isHttps && !hasHsts) {
      observations.push('HTTPS enabled but HSTS header missing');
    }

    // Check CSP
    const cspHeader = headers['content-security-policy'] || headers['content-security-policy-report-only'];
    const hasCsp = !!cspHeader;

    if (!hasCsp) {
      observations.push('No Content-Security-Policy header found');
    }

    // Get cookies
    const cookies = await page.cookies();
    const insecureCookies = cookies.filter(c => !c.secure && isHttps);
    if (insecureCookies.length > 0) {
      observations.push(`${insecureCookies.length} cookie(s) without Secure flag on HTTPS site`);
    }

    const noHttpOnlyCookies = cookies.filter(c => !c.httpOnly);
    if (noHttpOnlyCookies.length > 0) {
      observations.push(`${noHttpOnlyCookies.length} cookie(s) without HttpOnly flag`);
    }

    // Check for common security headers
    const securityHeaders = [
      'x-frame-options',
      'x-content-type-options',
      'x-xss-protection',
      'referrer-policy',
      'permissions-policy'
    ];

    securityHeaders.forEach(header => {
      if (!headers[header]) {
        observations.push(`Missing security header: ${header}`);
      }
    });

    // Extract page metadata
    const pageTitle = await page.title();
    const metaTags: Record<string, string> = await page.evaluate(() => {
      const metas: Record<string, string> = {};
      document.querySelectorAll('meta').forEach(meta => {
        const name = meta.getAttribute('name') || meta.getAttribute('property');
        const content = meta.getAttribute('content');
        if (name && content) {
          metas[name] = content;
        }
      });
      return metas;
    });

    // Check forms
    const forms = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('form')).map(form => ({
        action: form.action || '',
        method: (form.method || 'get').toLowerCase(),
        hasHttps: form.action.startsWith('https://')
      }));
    });

    const insecureForms = forms.filter(f => f.action && !f.hasHttps);
    if (insecureForms.length > 0) {
      observations.push(`${insecureForms.length} form(s) submitting to non-HTTPS URL`);
    }

    // Check for mixed content
    if (isHttps && externalScripts.some(s => s.startsWith('http://'))) {
      observations.push('Mixed content detected: HTTP scripts on HTTPS page');
    }

    // GDPR/PDPA specific checks
    if (region_mode === 'GDPR' || region_mode === 'PDPA') {
      const hasCookieConsent = await page.evaluate(() => {
        const text = document.body.innerText.toLowerCase();
        return text.includes('cookie') && (text.includes('consent') || text.includes('accept'));
      });

      if (!hasCookieConsent && cookies.length > 0) {
        observations.push(`${region_mode} region: No cookie consent banner detected but cookies are set`);
      }
    }

    const result: ScanResult = {
      domain,
      timestamp: new Date().toISOString(),
      browser_data: {
        tls: {
          https: isHttps,
          hsts: hasHsts,
          hsts_value: hstsHeader
        },
        headers: responseHeaders,
        cookies: cookies.map(c => ({
          name: c.name,
          secure: c.secure,
          httpOnly: c.httpOnly,
          sameSite: c.sameSite || 'none',
          domain: c.domain
        })),
        third_parties: Array.from(thirdPartyDomains),
        csp_present: hasCsp,
        csp_value: cspHeader,
        response_headers: responseHeaders,
        page_title: pageTitle,
        meta_tags: metaTags,
        forms,
        external_scripts: externalScripts.slice(0, 50), // limit to first 50
        external_styles: externalStyles.slice(0, 50)
      },
      security_observations: observations
    };

    console.log(`Scan complete for ${domain}. Found ${observations.length} observations.`);

    return result;

  } finally {
    await browser.close();
  }
};
