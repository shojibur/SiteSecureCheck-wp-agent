# SiteSecureCheck Plugin - Complete Capabilities

## 🚀 Enhanced with AI-Powered Security Fixes

The plugin is now designed to work with **AWS Bedrock AI** - the Agent analyzes your site and intelligently decides which fixes to apply based on actual security risks, not static rules.

## 📊 New Endpoint: Security State Analysis

### `GET /wp-json/ssc/v1/security-state`

Returns comprehensive site information for AI analysis:

```json
{
  "wordpress": {
    "version": "6.4.2",
    "debug_mode": false,
    "ssl_admin": false,
    "file_edit_disabled": false
  },
  "plugins": [{
    "name": "Plugin Name",
    "version": "1.0",
    "active": true,
    "needs_update": false
  }],
  "themes": [...],
  "users": {
    "admin_users": [...],
    "total_users": 15
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

## 🛠️ All Available Fix Types (18 Total)

### Basic Security (Original - 4 types)

1. **header** - Security headers (HSTS, X-Frame-Options, etc.)
2. **csp** - Content Security Policy with Report-Only mode
3. **banner** - Region-aware cookie consent (EU/MY/US/OTHER)
4. **policy_page** - Auto-generate Privacy/Cookie policy pages

### Extended Security (NEW - 14 types)

5. **wp_config** - WordPress configuration recommendations
6. **disable_file_edit** - Prevent theme/plugin editing
7. **limit_login_attempts** - Brute force protection
8. **disable_xml_rpc** - Eliminate DDoS vector
9. **secure_uploads** - Prevent PHP execution in uploads
10. **database_prefix** - Check for default 'wp_' prefix
11. **disable_user_enumeration** - Prevent username discovery
12. **htaccess_protection** - Apache-level security rules
13. **remove_version** - Hide WordPress version
14. **secure_cookies** - HttpOnly, Secure, SameSite
15. **force_ssl_admin** - Encrypt admin communications
16. **disable_plugin** - Deactivate vulnerable plugins
17. **update_plugin** - Recommend/apply updates
18. **add_security_key** - Regenerate auth keys

## 🤖 How AI Integration Works

```
┌─────────────┐
│  Agent      │ 1. Scan site (headless browser)
│  (AWS)      │ 2. Get /security-state
└──────┬──────┘
       │
       ↓
┌─────────────┐
│  Bedrock AI │ 3. Analyze security state
│  (Claude)   │ 4. Generate fix plan with priorities
└──────┬──────┘
       │
       ↓
┌─────────────┐
│  Plugin     │ 5. Apply fixes via /apply-fixes
│  (WP)       │ 6. Create snapshot for rollback
└─────────────┘
```

## 📝 Example AI-Generated Fix Plan

Bedrock analyzes the site and returns:

```json
{
  "score": 45,
  "risk_level": "high",
  "issues": [
    {
      "id": "xml-rpc-enabled",
      "severity": "high",
      "category": "attack_surface",
      "description": "XML-RPC DDoS risk",
      "cvss_score": 7.5
    },
    {
      "id": "no-login-limit",
      "severity": "high",
      "category": "authentication",
      "description": "Brute force vulnerable"
    }
  ],
  "fixes": [
    {
      "priority": 1,
      "type": "disable_xml_rpc",
      "reason": "Eliminate DDoS vector",
      "estimated_score_gain": 15
    },
    {
      "priority": 2,
      "type": "limit_login_attempts",
      "max_attempts": 5,
      "lockout_duration": 1800,
      "reason": "Prevent brute force",
      "estimated_score_gain": 10
    },
    {
      "priority": 3,
      "type": "header",
      "key": "X-Frame-Options",
      "value": "SAMEORIGIN",
      "reason": "Prevent clickjacking",
      "estimated_score_gain": 5
    }
  ]
}
```

## 🔒 Security Features

### Login Protection
- Configurable max attempts (default: 5)
- IP-based lockout (default: 30 min)
- Automatic transient cleanup
- No database bloat

### XML-RPC Protection
- Complete disable option
- Prevents DDoS amplification
- Pingback attack mitigation

### User Enumeration Protection
- Blocks REST API user endpoint
- Prevents username discovery
- Protects against targeted attacks

### File Upload Security
- .htaccess in uploads directory
- Blocks PHP execution
- Prevents shell uploads

### Version Hiding
- Removes WP version from meta
- Removes from RSS feeds
- Reduces attack surface

## 📦 What Gets Stored

All configurations stored in WordPress options:

- `ssc_api_key` - 48-char secure key
- `ssc_custom_headers` - Applied headers
- `ssc_csp_config` - CSP policy & mode
- `ssc_banner_config` - Cookie banner settings
- `ssc_snapshots` - Last 20 rollback points
- `ssc_login_protection` - Brute force settings
- `ssc_xmlrpc_disabled` - XML-RPC state
- `ssc_disable_user_enum` - Enumeration protection
- `ssc_remove_version` - Version hiding
- `ssc_wp_config_recommendations` - Manual wp-config edits needed
- `ssc_update_recommendations` - Plugin/theme updates needed

## 🔄 Automatic Security Enforcement

These run on every request:

```php
// Disable XML-RPC if configured
add_filter('xmlrpc_enabled', '__return_false');

// Block user enumeration
add_action('rest_authentication_errors', ...);

// Hide WP version
remove_action('wp_head', 'wp_generator');

// Login attempt limiting
add_action('wp_login_failed', ...);
add_filter('authenticate', ...);
```

## 🎯 Why This Approach is Better

### Traditional Security Plugins
- ❌ Static rules
- ❌ One-size-fits-all
- ❌ Lots of false positives
- ❌ Can't adapt to new threats

### SiteSecureCheck with Bedrock AI
- ✅ AI analyzes your specific site
- ✅ Prioritizes by actual risk
- ✅ Context-aware fixes
- ✅ Learns from new threats
- ✅ Safe rollback if issues
- ✅ Region-aware compliance

## 📈 Scoring System

The AI can estimate score improvements:

- **Score 0-30**: Critical - immediate attention needed
- **Score 31-60**: High risk - multiple vulnerabilities
- **Score 61-80**: Medium - some improvements needed
- **Score 81-95**: Good - minor tweaks
- **Score 96-100**: Excellent - well secured

Each fix has an `estimated_score_gain` so you can see impact.

## 🧪 Testing the New Features

### Test Security State
```bash
curl https://yoursite.com/wp-json/ssc/v1/security-state \
  -H "Authorization: Bearer YOUR_KEY" | jq
```

### Test XML-RPC Disable
```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/apply-security-fix \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"fix": {"type": "disable_xml_rpc"}}'

# Verify it worked
curl -X POST https://yoursite.com/xmlrpc.php
# Should return error or 404
```

### Test Login Protection
```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/apply-security-fix \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fix": {
      "type": "limit_login_attempts",
      "max_attempts": 3,
      "lockout_duration": 600
    }
  }'

# Try failed logins to test
```

### Test User Enumeration Protection
```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/apply-security-fix \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"fix": {"type": "disable_user_enumeration"}}'

# Try to access users endpoint (should fail)
curl https://yoursite.com/wp-json/wp/v2/users
```

## 🎨 Admin UI Updates

The WordPress admin page now shows:
- ✅ Security state summary
- ✅ Applied fixes and their status
- ✅ Recommendations requiring manual action
- ✅ Login protection status
- ✅ XML-RPC status
- ✅ User enumeration protection status

## 📚 Documentation Files

1. **AGENT_GUIDE.md** - Complete guide for Agent developers
2. **PLUGIN_CAPABILITIES.md** - This file
3. **README.md** - Plugin installation and basic usage
4. **QUICK_START.md** - End-to-end setup guide

## 🚦 What's Automatic vs Manual

### Automatic (Plugin applies directly)
- ✅ Security headers
- ✅ CSP (Report-Only)
- ✅ Cookie banner
- ✅ XML-RPC disable
- ✅ Login protection
- ✅ User enumeration blocking
- ✅ Version hiding
- ✅ Plugin deactivation

### Manual (Plugin provides recommendations)
- ⚠️ wp-config.php edits (file access needed)
- ⚠️ Database prefix change (migration needed)
- ⚠️ Security key regeneration (wp-config.php)
- ⚠️ Force SSL admin (wp-config.php)
- ⚠️ Plugin updates (may need testing)

## 💡 Pro Tips for Agent Development

1. **Always call /security-state first** - Bedrock needs this context
2. **Let AI prioritize** - Don't hard-code fix order
3. **Monitor CSP violations** - Before promoting to enforce
4. **Use snapshots** - Test fixes, rollback if needed
5. **Respect region settings** - EU/MY need consent banners
6. **Check estimated_score_gain** - Apply high-value fixes first
7. **Handle manual fixes gracefully** - Notify user via dashboard
8. **Re-scan after fixes** - Verify score improvement
9. **Rate limit API calls** - Don't overwhelm the site
10. **Log everything** - For audit trail

---

**The plugin is now a flexible, AI-ready security enforcement engine that can adapt to any WordPress site's specific needs!**
