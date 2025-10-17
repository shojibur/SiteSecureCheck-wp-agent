# SiteSecureCheck Agent

AWS-based security scanning agent using Lambda, API Gateway, Puppeteer, and Bedrock AI.

## Architecture

```
┌─────────────────┐      ┌──────────────────┐      ┌─────────────────┐
│   Dashboard     │─────>│  API Gateway     │─────>│  Orchestrator   │
│   (Laravel)     │      │   /scan POST     │      │    Lambda       │
└─────────────────┘      └──────────────────┘      └────────┬────────┘
        ▲                                                    │
        │                                                    │
        │ Webhook                                            ├─> Scanner Lambda
        │                                                    │   (Puppeteer)
        │                                                    │
        │                                                    ├─> Bedrock AI
        │                                                    │   (Claude)
        │                                                    │
        │                                                    └─> WordPress Plugin
        │                                                        (Apply fixes)
        │
        └────────────────────────────────────────────────────────┘
```

## Components

### 1. Scanner Lambda
- Runs headless Chrome via Puppeteer
- Scans website for security issues
- Checks HTTPS, HSTS, CSP, cookies, headers, forms, etc.
- GDPR/PDPA compliance checks
- Returns detailed browser-based security analysis

### 2. Orchestrator Lambda
- Coordinates the entire scan workflow
- Invokes Scanner Lambda
- Fetches WordPress security state from plugin
- Calls AWS Bedrock for AI-powered analysis
- Applies recommended fixes (if auto_fix enabled)
- Sends webhook callback to dashboard
- Handles errors gracefully

### 3. API Gateway
- Exposes POST /scan endpoint
- Throttling: 10 req/sec rate limit, 20 burst
- Returns scan results synchronously (or via webhook)

## Prerequisites

1. **AWS Account** with access to:
   - Lambda
   - API Gateway
   - IAM
   - Bedrock (Claude 3.5 Sonnet model)

2. **AWS CLI** configured with credentials
3. **Node.js 20+** and npm
4. **AWS CDK** installed globally: `npm install -g aws-cdk`

## Setup

### Step 1: Install Dependencies

```bash
cd agent
npm install
```

### Step 2: Bootstrap CDK (first time only)

```bash
npx cdk bootstrap
```

### Step 3: Deploy

```bash
npm run build
npx cdk deploy
```

After deployment, CDK will output:
```
SiteSecureCheckStack.ApiUrl = https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/
SiteSecureCheckStack.ScanEndpoint = https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan
```

### Step 4: Configure Dashboard

Copy the `ScanEndpoint` URL and update your dashboard `.env`:

```bash
cd ../dashboard

# Generate webhook token
WEBHOOK_TOKEN=$(openssl rand -hex 32)
echo $WEBHOOK_TOKEN

# Update .env
echo "AGENT_SCAN_URL=https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan" >> .env
echo "AGENT_WEBHOOK_TOKEN=$WEBHOOK_TOKEN" >> .env
```

### Step 5: Enable Bedrock Model Access

1. Go to AWS Console → Bedrock
2. Navigate to "Model access"
3. Request access to **Claude 3.5 Sonnet v2** (`anthropic.claude-3-5-sonnet-20241022-v2:0`)
4. Wait for approval (usually instant)

## Usage

### Trigger a Scan

From the dashboard, click "Scan" on any site. The system will:

1. Queue the scan job
2. Call the Agent API Gateway endpoint
3. Agent runs browser scan + WordPress scan
4. Agent analyzes with Bedrock AI
5. Agent applies fixes (if `auto_fix` enabled)
6. Agent sends webhook back to dashboard
7. Dashboard updates scan record and sends Teams notification

### Manual API Call

```bash
curl -X POST https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan \
  -H "Content-Type: application/json" \
  -d '{
    "site_id": "uuid-here",
    "domain": "example.com",
    "wp_api_base": "https://example.com/wp-json/sitesecurecheck/v1",
    "wp_api_key": "your-api-key",
    "region_mode": "GDPR",
    "auto_fix": true,
    "callback": "https://yourdashboard.com/api/webhook",
    "callback_token": "your-webhook-token"
  }'
```

## Request Payload

```typescript
{
  site_id: string;          // UUID from dashboard
  domain: string;           // Domain to scan (without protocol)
  wp_api_base: string;      // WordPress REST API base URL
  wp_api_key: string;       // WordPress API key (Bearer token)
  region_mode?: string;     // "GDPR" | "PDPA" | "general" (default: "GDPR")
  auto_fix?: boolean;       // Apply fixes automatically (default: false)
  callback?: string;        // Webhook URL to POST results
  callback_token?: string;  // Bearer token for webhook auth
}
```

## Response Payload

```typescript
{
  site_id: string;
  domain: string;
  timestamp: string;
  score: number;                    // 0-100
  assessment: string;               // Overall security assessment
  browser_scan: {
    observations: string[];         // List of security observations
    tls: {
      https: boolean;
      hsts: boolean;
      hsts_value?: string;
    };
    third_parties: string[];        // Third-party domains detected
  };
  wordpress_state: {                // WordPress security state
    xml_rpc_enabled?: boolean;
    login_url_default?: boolean;
    file_editing_enabled?: boolean;
    debug_mode?: boolean;
    // ... etc
  };
  recommended_actions: Array<{
    fix_type: string;
    priority: "high" | "medium" | "low";
    reason: string;
    config?: Record<string, any>;
  }>;
  applied_actions: string[];        // Fix types that were applied
  apply_logs: string[];             // Logs from applying fixes
  auto_fix_enabled: boolean;
}
```

## Available Fix Types

The Bedrock AI can recommend these fix types:

- `disable_xml_rpc` - Disable XML-RPC (prevents DDoS)
- `change_login_url` - Change login URL from default
- `disable_user_enumeration` - Prevent username discovery
- `disable_file_editing` - Disable file editor in admin
- `remove_version_info` - Hide WordPress version
- `secure_wp_config` - Secure wp-config.php
- `add_security_headers` - Add security headers
- `enforce_strong_passwords` - Require strong passwords
- `limit_login_attempts` - Add login rate limiting
- `add_2fa` - Enable two-factor authentication
- `disable_directory_listing` - Prevent directory browsing
- `secure_htaccess` - Harden .htaccess
- `protect_uploads` - Prevent PHP execution in uploads
- `hide_login_errors` - Hide login error details

## Monitoring

### CloudWatch Logs

- **Orchestrator**: `/aws/lambda/SiteSecureCheckStack-OrchestratorFn`
- **Scanner**: `/aws/lambda/SiteSecureCheckStack-ScannerFn`

### API Gateway Logs

Check API Gateway → Stages → prod → Logs

## Cost Estimation

- **Lambda**: ~$0.01 per scan (2GB memory, 60s max)
- **API Gateway**: $3.50 per million requests
- **Bedrock**: ~$0.003 per request (Claude 3.5 Sonnet)
- **Total**: ~$0.01-0.02 per scan

## Troubleshooting

### "Bedrock access denied"
- Ensure you've requested model access in AWS Console
- Check IAM permissions include `bedrock:InvokeModel`

### "Scanner timeout"
- Website might be slow to load
- Increase timeout in `ssc-stack.ts` (currently 60s)

### "WordPress plugin not responding"
- Verify plugin is activated
- Check API key is correct
- Test plugin endpoints manually

### "Webhook not received"
- Check `AGENT_WEBHOOK_TOKEN` matches in both .env files
- Verify callback URL is publicly accessible
- Check Laravel logs: `tail -f storage/logs/laravel.log`

## Development

### Local Testing

You can't run Puppeteer locally easily (requires Chromium layer), but you can:

1. Test orchestrator logic with mock scanner results
2. Use AWS SAM for local Lambda testing
3. Test individual functions via AWS Console

### Update and Redeploy

```bash
npm run build
npx cdk deploy
```

CDK will only update changed resources.

## Security Notes

- API Gateway has no authentication (relies on obscurity + webhook token)
- Consider adding API keys or IAM auth for production
- Webhook token should be 32+ random bytes
- Store AWS credentials securely (never commit to git)
- Lambda functions run with minimal IAM permissions

## Cleanup

To delete all AWS resources:

```bash
npx cdk destroy
```

This will remove:
- Lambda functions
- API Gateway
- IAM roles
- CloudWatch log groups

---

For more information, see the [project documentation](../docs/PROJECT.md).
