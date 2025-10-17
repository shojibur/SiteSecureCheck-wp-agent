# Deployment Checklist

Follow these steps to deploy the SiteSecureCheck Agent to AWS.

## Pre-Deployment

- [ ] AWS CLI installed and configured (`aws configure`)
- [ ] AWS CDK installed globally (`npm install -g aws-cdk`)
- [ ] Node.js 20+ installed
- [ ] Dependencies installed (`npm install` in `/agent` directory)
- [ ] TypeScript compiled successfully (`npm run build`)

## AWS Prerequisites

- [ ] AWS account with appropriate permissions
- [ ] IAM user/role with permissions for:
  - Lambda (create, update, invoke)
  - API Gateway (create, update)
  - IAM (create roles and policies)
  - CloudWatch Logs (create log groups)
  - Bedrock (invoke model)

## Deployment Steps

### 1. Bootstrap CDK (First Time Only)

```bash
cd agent
npx cdk bootstrap
```

This creates the CDK toolkit stack in your AWS account.

### 2. Enable Bedrock Model Access

**Important**: Do this BEFORE deploying!

1. Go to AWS Console → Amazon Bedrock
2. Region: Select your deployment region (e.g., us-east-1)
3. Navigate to "Model access" in the left sidebar
4. Click "Manage model access"
5. Find "Claude 3.5 Sonnet v2" (`anthropic.claude-3-5-sonnet-20241022-v2:0`)
6. Check the box next to it
7. Click "Request model access"
8. Wait for approval (usually instant)

### 3. Deploy the Stack

```bash
npm run build
npx cdk deploy
```

You'll be asked to confirm IAM changes. Type `y` and press Enter.

Deployment takes 2-5 minutes.

### 4. Copy Output Values

After deployment, CDK outputs important URLs:

```
SiteSecureCheckStack.ApiUrl = https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/
SiteSecureCheckStack.ScanEndpoint = https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan
```

**Copy the ScanEndpoint URL** - you'll need it for the dashboard.

### 5. Configure Dashboard

```bash
cd ../dashboard

# Generate a secure webhook token
openssl rand -hex 32

# Edit .env file
nano .env
```

Add these lines (or update if they exist):

```env
AGENT_SCAN_URL=https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan
AGENT_WEBHOOK_TOKEN=<the-token-you-generated>
```

Make sure your `APP_URL` is publicly accessible (Agent needs to POST webhooks back).

### 6. Restart Queue Worker

```bash
# If running queue worker locally
php artisan queue:restart

# If using systemd/supervisor, restart the service
sudo systemctl restart laravel-worker
```

## Verification

### Test 1: Check API Gateway

```bash
curl https://xxxxx.execute-api.us-east-1.amazonaws.com/prod/scan
```

Should return an error (no body provided), but confirms API is accessible.

### Test 2: Check Lambda Functions

```bash
aws lambda list-functions | grep SiteSecureCheck
```

Should show two functions:
- `SiteSecureCheckStack-ScannerFn...`
- `SiteSecureCheckStack-OrchestratorFn...`

### Test 3: Check Bedrock Access

```bash
aws bedrock list-foundation-models --region us-east-1 | grep claude-3-5-sonnet
```

Should show the model if you have access.

### Test 4: Trigger a Scan

1. Log into your dashboard
2. Go to Sites
3. Click "Check Connection" on a site (should show "Connected")
4. Click "Scan" button
5. Monitor Laravel logs: `tail -f storage/logs/laravel.log`
6. Check CloudWatch logs:
   - Go to AWS Console → CloudWatch → Log groups
   - `/aws/lambda/SiteSecureCheckStack-OrchestratorFn...`
   - View latest log stream

## Troubleshooting

### "Unable to resolve endpoint"

**Cause**: CDK can't find AWS credentials

**Fix**:
```bash
aws configure
# Enter your AWS Access Key ID and Secret Access Key
```

### "Bedrock access denied"

**Cause**: Model access not requested

**Fix**: Follow Step 2 above to request model access in AWS Console

### "Scanner timeout"

**Cause**: Target website is slow or blocking requests

**Fix**:
- Check CloudWatch logs for details
- Increase timeout in `cdk/lib/ssc-stack.ts` (line 27)
- Redeploy: `npx cdk deploy`

### "Webhook not received"

**Cause**: Dashboard APP_URL not publicly accessible, or webhook token mismatch

**Fix**:
- Ensure `APP_URL` in dashboard `.env` is a public URL
- Verify `AGENT_WEBHOOK_TOKEN` matches in both agent and dashboard
- Check Laravel logs for webhook errors
- Test webhook endpoint: `curl -X POST https://yourdashboard.com/api/webhook -H "Authorization: Bearer your-token"`

### "502 Bad Gateway"

**Cause**: Lambda function crashed or timed out

**Fix**:
- Check CloudWatch logs for error details
- Common causes:
  - Out of memory (increase in `ssc-stack.ts`)
  - Timeout (increase in `ssc-stack.ts`)
  - Missing dependencies (check `package.json`)

## Updating

When you make changes to Lambda code:

```bash
cd agent
npm run build
npx cdk deploy
```

CDK will detect changes and update only affected resources.

## Cost Monitoring

Check AWS Cost Explorer to monitor spending:

```bash
aws ce get-cost-and-usage \
  --time-period Start=2025-01-01,End=2025-01-31 \
  --granularity MONTHLY \
  --metrics BlendedCost \
  --filter file://filter.json
```

Expected costs per scan:
- Lambda execution: ~$0.005
- API Gateway: ~$0.000035
- Bedrock: ~$0.003
- **Total**: ~$0.01 per scan

## Rollback

If something goes wrong:

```bash
npx cdk destroy
# Wait for resources to be deleted
npx cdk deploy
```

Or manually delete the CloudFormation stack in AWS Console.

## Production Checklist

Before going to production:

- [ ] Enable API Gateway access logging
- [ ] Set up CloudWatch alarms for Lambda errors
- [ ] Configure API Gateway API keys (add authentication)
- [ ] Set up AWS Budget alerts
- [ ] Enable X-Ray tracing for debugging
- [ ] Review and restrict IAM permissions
- [ ] Set up VPC for Lambda functions (optional, for private resources)
- [ ] Configure dead letter queue for failed scans
- [ ] Set up backup/DR plan
- [ ] Document architecture diagram
- [ ] Load test with expected traffic

## Support

- AWS Documentation: https://docs.aws.amazon.com/
- CDK Documentation: https://docs.aws.amazon.com/cdk/
- Bedrock Documentation: https://docs.aws.amazon.com/bedrock/

For project-specific issues, check the main README or contact the team.
