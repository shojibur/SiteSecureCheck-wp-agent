# Testing SiteSecureCheck Without the Agent

## Why "Scan" Doesn't Work Yet

The **Scan button** in the dashboard tries to call the AWS Agent, which doesn't exist yet. The scan workflow is:

```
Dashboard → Agent (AWS Lambda) → Plugin
                ↓
         [Bedrock AI Analysis]
                ↓
         Generate Fix Plan
                ↓
         Apply Fixes
```

**Without the Agent, you can't scan automatically.** But you CAN test the plugin!

## How to Test Plugin Connection (Manual)

I've added test endpoints so you can verify everything works:

### 1. Test Plugin is Reachable

```bash
# Test status endpoint (public)
curl http://your-wordpress-site.com/wp-json/ssc/v1/status
```

**Expected Response**:
```json
{
  "ok": true,
  "version": "0.1.0",
  "site_url": "http://your-wordpress-site.com"
}
```

### 2. Test Authentication

```bash
# Test with your API key (from WordPress admin)
curl http://your-wordpress-site.com/wp-json/ssc/v1/config \
  -H "Authorization: Bearer YOUR_API_KEY_HERE"
```

**Expected Response**:
```json
{
  "headers": {},
  "csp": {},
  "banner": {},
  "region": "OTHER"
}
```

### 3. Test Security State (What Agent Will See)

```bash
curl http://your-wordpress-site.com/wp-json/ssc/v1/security-state \
  -H "Authorization: Bearer YOUR_API_KEY_HERE"
```

**Expected Response**:
```json
{
  "wordpress": {
    "version": "6.4.2",
    "debug_mode": false,
    "ssl_admin": false,
    ...
  },
  "plugins": [...],
  "themes": [...],
  "users": {...},
  "database": {...}
}
```

## Testing from Dashboard (NEW!)

I've added test routes you can call from your browser or Postman:

### From Browser Console

Open your site in the dashboard, then in browser console:

```javascript
// Get your site ID from the URL (e.g., /sites/1)
const siteId = 1;

// Test 1: Check connection
fetch(`/sites/${siteId}/test/connection`)
  .then(r => r.json())
  .then(console.log);

// Test 2: Check authentication
fetch(`/sites/${siteId}/test/auth`)
  .then(r => r.json())
  .then(console.log);

// Test 3: Get security state
fetch(`/sites/${siteId}/test/security-state`)
  .then(r => r.json())
  .then(console.log);

// Test 4: Apply sample fixes
fetch(`/sites/${siteId}/test/apply-sample`, {method: 'POST'})
  .then(r => r.json())
  .then(console.log);

// Test 5: Create mock scan (simulates Agent)
fetch(`/sites/${siteId}/test/mock-scan`, {method: 'POST'})
  .then(r => r.json())
  .then(console.log);
```

### Using cURL

```bash
# Replace YOUR_SITE_ID with actual site ID from dashboard
SITE_ID=1
BASE_URL="http://localhost:8000"

# Test connection
curl "$BASE_URL/sites/$SITE_ID/test/connection"

# Test authentication
curl "$BASE_URL/sites/$SITE_ID/test/auth"

# Get security state
curl "$BASE_URL/sites/$SITE_ID/test/security-state"

# Apply sample security fixes
curl -X POST "$BASE_URL/sites/$SITE_ID/test/apply-sample"

# Create a mock scan (simulates what Agent would do)
curl -X POST "$BASE_URL/sites/$SITE_ID/test/mock-scan"
```

## What Each Test Does

### 1. `/test/connection`
- Calls plugin's `/status` endpoint
- Verifies plugin is installed and active
- No authentication needed

### 2. `/test/auth`
- Calls plugin's `/config` endpoint with API key
- Verifies API key is correct
- Tests authentication system

### 3. `/test/security-state`
- Gets comprehensive security information
- Shows what Agent will analyze with Bedrock
- Returns WordPress config, plugins, users, etc.

### 4. `/test/apply-sample`
- Applies these sample fixes:
  - `X-Frame-Options: SAMEORIGIN` header
  - `X-Content-Type-Options: nosniff` header
  - Disable XML-RPC
  - Remove WordPress version
- Creates an Action record in dashboard
- Tests the fix application system

### 5. `/test/mock-scan`
- Analyzes security state
- Creates mock issues (XML-RPC enabled, default DB prefix, etc.)
- Creates a Scan record with score
- Updates site's last_score
- **This simulates what the Agent will do!**

## Expected Test Flow

1. **Install plugin** in WordPress
2. **Copy API key** from WordPress admin
3. **Add site** in dashboard with:
   - Domain: `https://your-site.com`
   - WP API Base: `https://your-site.com/wp-json/ssc/v1`
   - WP API Key: [paste from plugin]
4. **Test connection**: Call `/test/connection`
5. **Test auth**: Call `/test/auth`
6. **View security state**: Call `/test/security-state`
7. **Apply sample fixes**: Call `/test/apply-sample`
8. **Create mock scan**: Call `/test/mock-scan`
9. **View results** in dashboard (Sites → View)

## Troubleshooting

### "Connection failed"
- ✅ Plugin activated in WordPress?
- ✅ WordPress site accessible?
- ✅ REST API enabled? (try: `/wp-json/`)
- ✅ Correct URL in dashboard?

### "Authentication failed"
- ✅ API key copied correctly?
- ✅ No extra spaces in key?
- ✅ Using correct endpoint (with `/ssc/v1/`)?

### "Apply fixes failed"
- ✅ Check WordPress error logs
- ✅ File permissions OK?
- ✅ Plugin up to date?

## When Agent is Ready

Once you implement the Agent, it will:

1. Call `/security-state` to get site info
2. Send to **AWS Bedrock** for AI analysis
3. Receive intelligent fix plan from AI
4. Apply fixes via `/apply-fixes` endpoint
5. Post results to dashboard webhook

The **test endpoints will no longer be needed** - Agent does it all automatically!

## Quick Test Script

Save this as `test-plugin.sh`:

```bash
#!/bin/bash

# Configuration
SITE_ID=1
DASHBOARD_URL="http://localhost:8000"
WP_URL="http://your-wordpress-site.com"
API_KEY="your-api-key-here"

echo "=== Testing SiteSecureCheck Plugin ==="
echo ""

echo "1. Testing WordPress plugin status..."
curl -s "$WP_URL/wp-json/ssc/v1/status" | jq
echo ""

echo "2. Testing plugin authentication..."
curl -s "$WP_URL/wp-json/ssc/v1/config" \
  -H "Authorization: Bearer $API_KEY" | jq
echo ""

echo "3. Testing from Dashboard - Connection..."
curl -s "$DASHBOARD_URL/sites/$SITE_ID/test/connection" | jq
echo ""

echo "4. Testing from Dashboard - Auth..."
curl -s "$DASHBOARD_URL/sites/$SITE_ID/test/auth" | jq
echo ""

echo "5. Applying sample fixes..."
curl -s -X POST "$DASHBOARD_URL/sites/$SITE_ID/test/apply-sample" | jq
echo ""

echo "6. Creating mock scan..."
curl -s -X POST "$DASHBOARD_URL/sites/$SITE_ID/test/mock-scan" | jq
echo ""

echo "=== Tests Complete ==="
```

Then run:
```bash
chmod +x test-plugin.sh
./test-plugin.sh
```

## Summary

- ❌ **Scan button**: Won't work (needs Agent)
- ✅ **Plugin connection**: Can test with `/test/connection`
- ✅ **API authentication**: Can test with `/test/auth`
- ✅ **Security analysis**: Can test with `/test/security-state`
- ✅ **Apply fixes**: Can test with `/test/apply-sample`
- ✅ **Mock scanning**: Can test with `/test/mock-scan`

**Use the test endpoints to verify everything is working before building the Agent!**
