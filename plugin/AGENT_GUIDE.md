# Agent Integration Guide - AI-Powered Security Fixes

This guide explains how the AWS Agent (using Bedrock) should interact with the WordPress plugin to apply intelligent, context-aware security fixes.

## Overview

The plugin is designed to be **AI-friendly** - the Agent analyzes the site using AWS Bedrock and decides which fixes to apply based on:
- Current security state
- WordPress version and configuration
- Installed plugins/themes
- Compliance requirements (GDPR, PDPA, etc.)
- Best practices and risk assessment

## Workflow

```
1. Agent scans site (headless browser)
2. Agent gets security-state from plugin
3. Agent sends data to AWS Bedrock
4. Bedrock analyzes and creates fix plan
5. Agent applies fixes via plugin API
6. Agent monitors results and reports
```

## Step 1: Get Security State

**Endpoint**: `GET /wp-json/ssc/v1/security-state`

This returns comprehensive information for Bedrock to analyze:

```bash
curl https://example.com/wp-json/ssc/v1/security-state \
  -H "Authorization: Bearer API_KEY"
```

**Response Structure**:
```json
{
  "wordpress": {
    "version": "6.4.2",
    "debug_mode": false,
    "ssl_admin": false,
    "file_edit_disabled": false,
    "auto_update_core": true
  },
  "plugins": [
    {
      "name": "Contact Form 7",
      "version": "5.8",
      "active": true,
      "needs_update": false
    }
  ],
  "themes": [...],
  "users": {
    "admin_users": [...],
    "total_users": 15,
    "default_role": "subscriber"
  },
  "database": {
    "prefix": "wp_",
    "is_default_prefix": true
  },
  "filesystem": {...},
  "apis": {
    "xml_rpc_enabled": true,
    "rest_api_enabled": true
  },
  "configs": {...}
}
```

## Step 2: Send to Bedrock for Analysis

Use AWS Bedrock (Claude 3.5 Sonnet recommended) with this prompt structure:

```python
prompt = f"""You are a WordPress security expert. Analyze this site's security state and create a remediation plan.

Site Security State:
{json.dumps(security_state, indent=2)}

Scan Results:
- Headers: {scan_headers}
- CSP: {scan_csp}
- Cookies: {scan_cookies}
- SSL/TLS: {scan_ssl}

Create a JSON array of fixes prioritized by:
1. Critical security risks
2. Compliance requirements (GDPR/PDPA if region is EU/MY)
3. Best practices
4. Performance impact

Available fix types: header, csp, banner, policy_page, wp_config, disable_file_edit, 
limit_login_attempts, disable_xml_rpc, secure_uploads, disable_user_enumeration, 
htaccess_protection, remove_version, secure_cookies, force_ssl_admin, disable_plugin, 
update_plugin, add_security_key

Return ONLY a JSON object:
{{
  "score": 0-100,
  "issues": [...],
  "fixes": [...]
}}
"""

response = bedrock.invoke_model(
    modelId="anthropic.claude-3-5-sonnet-20241022",
    body=json.dumps({"prompt": prompt, "max_tokens": 4000})
)
```

## Step 3: Apply Fixes

The Agent can apply fixes in two ways:

### Option A: Batch Apply (Recommended)
```bash
curl -X POST https://example.com/wp-json/ssc/v1/apply-fixes \
  -H "Authorization: Bearer API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fixes": [
      {
        "type": "header",
        "key": "X-Frame-Options",
        "value": "SAMEORIGIN"
      },
      {
        "type": "disable_xml_rpc",
        "reason": "Prevent DDoS attacks"
      },
      {
        "type": "limit_login_attempts",
        "max_attempts": 5,
        "lockout_duration": 1800
      }
    ]
  }'
```

### Option B: Individual Security Fix
```bash
curl -X POST https://example.com/wp-json/ssc/v1/apply-security-fix \
  -H "Authorization: Bearer API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fix": {
      "type": "disable_xml_rpc",
      "reason": "Bedrock identified potential DDoS risk"
    }
  }'
```

## Available Fix Types

### 1. Basic Security Fixes (Already Implemented)

#### header
```json
{
  "type": "header",
  "key": "Strict-Transport-Security",
  "value": "max-age=31536000; includeSubDomains; preload"
}
```

#### csp
```json
{
  "type": "csp",
  "policy": "default-src 'self'; script-src 'self' 'unsafe-inline'",
  "mode": "report-only",
  "report_uri": "/wp-json/ssc/v1/csp-report"
}
```

#### banner
```json
{
  "type": "banner",
  "enabled": true,
  "region": "EU",
  "message": "We use cookies to improve your experience",
  "block_scripts": ["google-analytics.com", "facebook.net"]
}
```

#### policy_page
```json
{
  "type": "policy_page",
  "title": "Privacy Policy",
  "content": "Generated policy content from Bedrock...",
  "slug": "privacy-policy"
}
```

### 2. Extended Security Fixes (NEW)

#### wp_config
```json
{
  "type": "wp_config",
  "setting": "WP_DEBUG",
  "value": "false",
  "reason": "Disable debug mode in production"
}
```

#### disable_file_edit
```json
{
  "type": "disable_file_edit",
  "reason": "Prevent unauthorized code modifications"
}
```

#### limit_login_attempts
```json
{
  "type": "limit_login_attempts",
  "max_attempts": 5,
  "lockout_duration": 1800
}
```

#### disable_xml_rpc
```json
{
  "type": "disable_xml_rpc",
  "reason": "Prevent DDoS amplification attacks"
}
```

#### secure_uploads
```json
{
  "type": "secure_uploads",
  "reason": "Prevent PHP execution in uploads directory"
}
```

#### disable_user_enumeration
```json
{
  "type": "disable_user_enumeration",
  "reason": "Prevent username discovery attacks"
}
```

#### htaccess_protection
```json
{
  "type": "htaccess_protection",
  "rules_type": "wordpress_hardening",
  "reason": "Add Apache-level security rules"
}
```

#### remove_version
```json
{
  "type": "remove_version",
  "reason": "Hide WordPress version from attackers"
}
```

#### secure_cookies
```json
{
  "type": "secure_cookies",
  "samesite": "Strict",
  "reason": "Prevent CSRF attacks"
}
```

#### force_ssl_admin
```json
{
  "type": "force_ssl_admin",
  "reason": "Encrypt admin panel communications"
}
```

#### disable_plugin
```json
{
  "type": "disable_plugin",
  "plugin_file": "vulnerable-plugin/plugin.php",
  "reason": "Plugin has known security vulnerability CVE-2024-XXXX"
}
```

#### update_plugin
```json
{
  "type": "update_plugin",
  "component": "contact-form-7",
  "reason": "Security update available"
}
```

#### add_security_key
```json
{
  "type": "add_security_key",
  "reason": "Using default or weak security keys"
}
```

## Example Bedrock Response

```json
{
  "score": 45,
  "risk_level": "high",
  "issues": [
    {
      "id": "xml-rpc-enabled",
      "severity": "high",
      "category": "attack_surface",
      "description": "XML-RPC is enabled and can be used for DDoS amplification attacks",
      "cvss_score": 7.5
    },
    {
      "id": "default-db-prefix",
      "severity": "medium",
      "category": "configuration",
      "description": "Using default 'wp_' database prefix makes SQL injection easier"
    },
    {
      "id": "no-login-limit",
      "severity": "high",
      "category": "authentication",
      "description": "No login attempt limiting detected, vulnerable to brute force"
    }
  ],
  "fixes": [
    {
      "priority": 1,
      "type": "disable_xml_rpc",
      "reason": "Eliminate DDoS amplification vector",
      "impact": "low",
      "estimated_score_gain": 15
    },
    {
      "priority": 2,
      "type": "limit_login_attempts",
      "max_attempts": 5,
      "lockout_duration": 1800,
      "reason": "Prevent brute force attacks on wp-login.php",
      "impact": "low",
      "estimated_score_gain": 10
    },
    {
      "priority": 3,
      "type": "header",
      "key": "X-Frame-Options",
      "value": "SAMEORIGIN",
      "reason": "Prevent clickjacking attacks",
      "impact": "none",
      "estimated_score_gain": 5
    },
    {
      "priority": 4,
      "type": "disable_user_enumeration",
      "reason": "Prevent username discovery via REST API",
      "impact": "low",
      "estimated_score_gain": 5
    },
    {
      "priority": 5,
      "type": "csp",
      "policy": "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google-analytics.com",
      "mode": "report-only",
      "report_uri": "/wp-json/ssc/v1/csp-report",
      "reason": "Start CSP rollout in safe mode",
      "impact": "none",
      "estimated_score_gain": 10
    }
  ],
  "recommendations": [
    "Consider changing database prefix (requires manual migration)",
    "Review and update all plugins to latest versions",
    "Enable 2FA for administrator accounts"
  ]
}
```

## Agent Implementation Example (Python)

```python
import boto3
import requests
import json

class SiteSecureCheckAgent:
    def __init__(self, bedrock_client, site_config):
        self.bedrock = bedrock_client
        self.site = site_config
        
    def scan_and_fix(self):
        # Step 1: Get security state
        security_state = self.get_security_state()
        
        # Step 2: Run headless browser scan
        scan_results = self.run_browser_scan()
        
        # Step 3: Analyze with Bedrock
        plan = self.analyze_with_bedrock(security_state, scan_results)
        
        # Step 4: Apply fixes
        results = self.apply_fixes(plan['fixes'])
        
        # Step 5: Report back
        self.report_to_dashboard(plan, results)
        
        return results
    
    def get_security_state(self):
        response = requests.get(
            f"{self.site['wp_api_base']}/security-state",
            headers={"Authorization": f"Bearer {self.site['wp_api_key']}"}
        )
        return response.json()
    
    def analyze_with_bedrock(self, security_state, scan_results):
        prompt = self.build_prompt(security_state, scan_results)
        
        response = self.bedrock.invoke_model(
            modelId="anthropic.claude-3-5-sonnet-20241022",
            body=json.dumps({
                "anthropic_version": "bedrock-2023-05-31",
                "max_tokens": 4000,
                "messages": [{
                    "role": "user",
                    "content": prompt
                }]
            })
        )
        
        result = json.loads(response['body'].read())
        return json.loads(result['content'][0]['text'])
    
    def apply_fixes(self, fixes):
        response = requests.post(
            f"{self.site['wp_api_base']}/apply-fixes",
            headers={
                "Authorization": f"Bearer {self.site['wp_api_key']}",
                "Content-Type": "application/json"
            },
            json={"fixes": fixes}
        )
        return response.json()
```

## Best Practices for Agent

1. **Always get security-state first** - Bedrock needs context
2. **Prioritize by risk** - Apply critical fixes first
3. **Test CSP in report-only** - Never start with enforce mode
4. **Create snapshots** - Plugin auto-creates but verify
5. **Monitor violations** - Check CSP violations before promoting
6. **Region awareness** - EU/MY require cookie consent
7. **Validate fixes** - Re-scan after applying to verify
8. **Handle errors gracefully** - Some fixes need manual intervention
9. **Report everything** - Send full results to dashboard
10. **Rate limit** - Don't overwhelm the site

## Error Handling

Fixes may fail or require manual steps. Common scenarios:

- **wp_config fixes**: Require file system access
- **Database prefix**: Requires migration, just warn
- **Plugin updates**: May need manual testing
- **Security keys**: Require wp-config.php edit

The plugin will return success=true with a message explaining what's needed.

## Security Considerations

- API keys are 48-char cryptographically secure
- All endpoints use Bearer auth with constant-time comparison
- Snapshots auto-created for rollback
- No destructive operations without approval
- File system operations are limited

## Testing

```bash
# Test security state endpoint
curl https://example.com/wp-json/ssc/v1/security-state \
  -H "Authorization: Bearer YOUR_KEY" | jq

# Test single fix
curl -X POST https://example.com/wp-json/ssc/v1/apply-security-fix \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"fix": {"type": "disable_xml_rpc"}}'

# Verify it worked
curl https://example.com/wp-json/ssc/v1/config \
  -H "Authorization: Bearer YOUR_KEY" | jq
```

---

This design lets Bedrock make intelligent, context-aware decisions about which fixes to apply, making the system truly AI-powered rather than rule-based.
