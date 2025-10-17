import type { APIGatewayProxyHandler } from 'aws-lambda';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
import { BedrockRuntimeClient, InvokeModelCommand } from '@aws-sdk/client-bedrock-runtime';

const lambda = new LambdaClient({});
const bedrock = new BedrockRuntimeClient({ region: process.env.BEDROCK_REGION || 'us-east-1' });

interface ScanRequest {
  site_id: string;
  domain: string;
  wp_api_base: string;
  wp_api_key: string;
  region_mode?: string;
  auto_fix?: boolean;
  callback?: string;
  callback_token?: string;
}

interface SecurityState {
  xml_rpc_enabled?: boolean;
  login_url_default?: boolean;
  user_enumeration_possible?: boolean;
  file_editing_enabled?: boolean;
  debug_mode?: boolean;
  version_exposed?: boolean;
  [key: string]: any;
}

interface BedrockAnalysis {
  recommended_actions: Array<{
    fix_type: string;
    priority: 'high' | 'medium' | 'low';
    reason: string;
    config?: Record<string, any>;
  }>;
  overall_assessment: string;
  score: number;
}

export const handler: APIGatewayProxyHandler = async (event) => {
  const body: ScanRequest = event.body ? JSON.parse(event.body) : {};
  const {
    site_id,
    domain,
    wp_api_base,
    wp_api_key,
    region_mode = 'GDPR',
    auto_fix = false,
    callback,
    callback_token
  } = body;

  console.log(`Starting scan for site ${site_id} (${domain})`);

  try {
    // Step 1: Invoke scanner Lambda for browser-based scan
    console.log('Invoking scanner Lambda...');
    const scannerResponse = await lambda.send(
      new InvokeCommand({
        FunctionName: process.env.SCANNER_FUNCTION_NAME,
        Payload: JSON.stringify({ domain, region_mode })
      })
    );

    const scannerResult = JSON.parse(
      new TextDecoder().decode(scannerResponse.Payload)
    );

    console.log(`Scanner found ${scannerResult.security_observations?.length || 0} observations`);

    // Step 2: Fetch WordPress security state
    console.log('Fetching WordPress security state...');
    let wpSecurityState: SecurityState = {};
    try {
      const wpResponse = await fetch(`${wp_api_base}/security-state`, {
        headers: {
          Authorization: `Bearer ${wp_api_key}`
        }
      });

      if (wpResponse.ok) {
        wpSecurityState = await wpResponse.json();
        console.log('WordPress security state retrieved successfully');
      } else {
        console.warn(`WordPress security-state endpoint returned ${wpResponse.status}`);
      }
    } catch (err) {
      console.error('Failed to fetch WordPress security state:', err);
    }

    // Step 3: Call Bedrock for AI analysis
    console.log('Analyzing with AWS Bedrock...');
    const bedrockAnalysis = await analyzeWithBedrock(
      scannerResult,
      wpSecurityState,
      region_mode
    );

    console.log(`Bedrock recommended ${bedrockAnalysis.recommended_actions.length} actions`);

    // Step 4: Apply fixes if auto_fix is enabled
    let appliedActions: string[] = [];
    let applyLogs: string[] = [];

    if (auto_fix && bedrockAnalysis.recommended_actions.length > 0) {
      console.log('Auto-fix enabled, applying recommended actions...');

      for (const action of bedrockAnalysis.recommended_actions) {
        if (action.priority === 'high' || action.priority === 'medium') {
          try {
            const applyResponse = await fetch(`${wp_api_base}/apply-fix`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${wp_api_key}`
              },
              body: JSON.stringify({
                fix_type: action.fix_type,
                config: action.config || {}
              })
            });

            if (applyResponse.ok) {
              const result = await applyResponse.json();
              appliedActions.push(action.fix_type);
              applyLogs.push(`✓ Applied ${action.fix_type}: ${result.message || 'Success'}`);
              console.log(`Applied fix: ${action.fix_type}`);
            } else {
              applyLogs.push(`✗ Failed to apply ${action.fix_type}: HTTP ${applyResponse.status}`);
              console.warn(`Failed to apply ${action.fix_type}: ${applyResponse.status}`);
            }
          } catch (err) {
            applyLogs.push(`✗ Error applying ${action.fix_type}: ${(err as Error).message}`);
            console.error(`Error applying ${action.fix_type}:`, err);
          }
        } else {
          applyLogs.push(`⊘ Skipped ${action.fix_type} (low priority)`);
        }
      }
    }

    // Step 5: Prepare response
    const response = {
      site_id,
      domain,
      timestamp: new Date().toISOString(),
      score: bedrockAnalysis.score,
      assessment: bedrockAnalysis.overall_assessment,
      browser_scan: {
        observations: scannerResult.security_observations || [],
        tls: scannerResult.browser_data?.tls,
        third_parties: scannerResult.browser_data?.third_parties || []
      },
      wordpress_state: wpSecurityState,
      recommended_actions: bedrockAnalysis.recommended_actions,
      applied_actions: appliedActions,
      apply_logs: applyLogs,
      auto_fix_enabled: auto_fix
    };

    // Step 6: Send webhook callback if provided
    if (callback && callback_token) {
      console.log('Sending webhook callback...');
      try {
        await fetch(callback, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${callback_token}`
          },
          body: JSON.stringify(response)
        });
        console.log('Webhook callback sent successfully');
      } catch (err) {
        console.error('Failed to send webhook:', err);
      }
    }

    return {
      statusCode: 200,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(response)
    };
  } catch (error) {
    console.error('Scan failed:', error);

    const errorResponse = {
      site_id,
      error: (error as Error).message,
      timestamp: new Date().toISOString()
    };

    // Try to send error callback
    if (callback && callback_token) {
      try {
        await fetch(callback, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${callback_token}`
          },
          body: JSON.stringify(errorResponse)
        });
      } catch (err) {
        console.error('Failed to send error webhook:', err);
      }
    }

    return {
      statusCode: 500,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(errorResponse)
    };
  }
};

async function analyzeWithBedrock(
  browserData: any,
  wpState: SecurityState,
  regionMode: string
): Promise<BedrockAnalysis> {
  const prompt = `You are a security expert analyzing a WordPress website for vulnerabilities and compliance issues.

Region/Compliance Mode: ${regionMode}

Browser Scan Results:
${JSON.stringify(browserData, null, 2)}

WordPress Security State:
${JSON.stringify(wpState, null, 2)}

Analyze the security posture and provide:
1. A security score (0-100, where 100 is perfect)
2. An overall assessment (2-3 sentences)
3. Recommended actions with priorities

Available fix types you can recommend:
- disable_xml_rpc
- change_login_url
- disable_user_enumeration
- disable_file_editing
- remove_version_info
- secure_wp_config
- add_security_headers (config: {headers: {...}})
- enforce_strong_passwords
- limit_login_attempts
- add_2fa
- disable_directory_listing
- secure_htaccess
- protect_uploads
- hide_login_errors

Consider ${regionMode} compliance requirements (e.g., GDPR cookie consent, data protection headers).

Respond ONLY with valid JSON in this exact format:
{
  "score": <number>,
  "overall_assessment": "<string>",
  "recommended_actions": [
    {
      "fix_type": "<string>",
      "priority": "high|medium|low",
      "reason": "<string>",
      "config": {}
    }
  ]
}`;

  try {
    const response = await bedrock.send(
      new InvokeModelCommand({
        modelId: process.env.BEDROCK_MODEL_ID || 'anthropic.claude-3-5-sonnet-20241022-v2:0',
        contentType: 'application/json',
        accept: 'application/json',
        body: JSON.stringify({
          anthropic_version: 'bedrock-2023-05-31',
          max_tokens: 2000,
          messages: [
            {
              role: 'user',
              content: prompt
            }
          ]
        })
      })
    );

    const responseBody = JSON.parse(new TextDecoder().decode(response.body));
    const analysisText = responseBody.content[0].text;

    // Extract JSON from response (Claude might wrap it in markdown)
    const jsonMatch = analysisText.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      throw new Error('No JSON found in Bedrock response');
    }

    const analysis: BedrockAnalysis = JSON.parse(jsonMatch[0]);

    // Validate and provide defaults
    if (!analysis.score) analysis.score = 50;
    if (!analysis.overall_assessment) analysis.overall_assessment = 'Analysis incomplete';
    if (!analysis.recommended_actions) analysis.recommended_actions = [];

    return analysis;
  } catch (error) {
    console.error('Bedrock analysis failed:', error);

    // Fallback analysis based on simple rules
    return createFallbackAnalysis(browserData, wpState);
  }
}

function createFallbackAnalysis(browserData: any, wpState: SecurityState): BedrockAnalysis {
  const actions: BedrockAnalysis['recommended_actions'] = [];
  let score = 100;

  // Check browser data
  if (!browserData.browser_data?.tls?.https) {
    actions.push({
      fix_type: 'add_security_headers',
      priority: 'high',
      reason: 'Site not using HTTPS',
      config: {}
    });
    score -= 30;
  }

  if (!browserData.browser_data?.tls?.hsts) {
    actions.push({
      fix_type: 'add_security_headers',
      priority: 'high',
      reason: 'HSTS header missing',
      config: { headers: { 'Strict-Transport-Security': 'max-age=31536000; includeSubDomains' } }
    });
    score -= 10;
  }

  if (!browserData.browser_data?.csp_present) {
    actions.push({
      fix_type: 'add_security_headers',
      priority: 'medium',
      reason: 'No Content-Security-Policy header',
      config: {}
    });
    score -= 10;
  }

  // Check WordPress state
  if (wpState.xml_rpc_enabled) {
    actions.push({
      fix_type: 'disable_xml_rpc',
      priority: 'high',
      reason: 'XML-RPC is enabled and can be exploited for DDoS attacks',
      config: {}
    });
    score -= 15;
  }

  if (wpState.login_url_default) {
    actions.push({
      fix_type: 'change_login_url',
      priority: 'medium',
      reason: 'Default login URL makes site vulnerable to brute force attacks',
      config: {}
    });
    score -= 10;
  }

  if (wpState.file_editing_enabled) {
    actions.push({
      fix_type: 'disable_file_editing',
      priority: 'high',
      reason: 'File editing in admin dashboard is a security risk',
      config: {}
    });
    score -= 15;
  }

  if (wpState.debug_mode) {
    actions.push({
      fix_type: 'secure_wp_config',
      priority: 'high',
      reason: 'Debug mode exposes sensitive information',
      config: {}
    });
    score -= 10;
  }

  return {
    score: Math.max(0, score),
    overall_assessment: `Automated analysis found ${actions.length} security issues. ${score >= 80 ? 'Overall security posture is good.' : score >= 60 ? 'Some security improvements recommended.' : 'Significant security improvements needed.'}`,
    recommended_actions: actions
  };
}
