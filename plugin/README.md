# SiteSecureCheck WordPress Plugin

The WordPress plugin component of the SiteSecureCheck security & compliance system.

## Features

- **Security Headers Management**: Apply and manage security headers (HSTS, X-Frame-Options, CSP, etc.)
- **Content Security Policy (CSP)**: 
  - Report-Only mode for safe testing
  - Violation reporting and tracking
  - One-click promotion to Enforce mode
- **Cookie Consent Banner**: 
  - Region-aware (EU/MY/US/Other)
  - Script blocking for GDPR/PDPA compliance
  - Customizable messaging
- **Policy Page Generation**: Automatically create Privacy/Cookie policy pages
- **Versioned Rollback**: Snapshot system for instant rollback of changes
- **Secure REST API**: Bearer token authentication for agent communication

## Installation

1. Upload the `plugin` folder to `/wp-content/plugins/sitesecurecheck`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **SiteSecureCheck** in the admin menu
4. Copy the API key and use it in your SiteSecureCheck Dashboard

## REST API Endpoints

### Public Endpoints

- `GET /wp-json/ssc/v1/status` - Plugin status check
- `POST /wp-json/ssc/v1/csp-report` - CSP violation reporting

### Secured Endpoints (Requires Bearer Token)

- `GET /wp-json/ssc/v1/config` - Get current configuration
- `POST /wp-json/ssc/v1/apply-fixes` - Apply security fixes (batch)
- `POST /wp-json/ssc/v1/rollback` - Rollback to a snapshot
- `GET /wp-json/ssc/v1/snapshots` - Get rollback history
- `GET /wp-json/ssc/v1/csp-violations` - Get CSP violations
- `POST /wp-json/ssc/v1/csp-promote` - Promote CSP to enforce mode

## API Usage Examples

### Apply Fixes

```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/apply-fixes \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fixes": [
      {
        "type": "header",
        "key": "Strict-Transport-Security",
        "value": "max-age=31536000; includeSubDomains; preload"
      },
      {
        "type": "csp",
        "policy": "default-src '\''self'\''; script-src '\''self'\'' '\''unsafe-inline'\''",
        "mode": "report-only",
        "report_uri": "/wp-json/ssc/v1/csp-report"
      },
      {
        "type": "banner",
        "enabled": true,
        "region": "EU",
        "message": "We use cookies to improve your experience",
        "block_scripts": ["google-analytics", "facebook"]
      },
      {
        "type": "policy_page",
        "title": "Privacy Policy",
        "content": "Your privacy policy content here...",
        "slug": "privacy-policy"
      }
    ]
  }'
```

### Rollback

```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/rollback \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"snapshot_id": "snap_abc123"}'
```

### Promote CSP to Enforce

```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/csp-promote \
  -H "Authorization: Bearer YOUR_API_KEY"
```

## Fix Types

### Header Fix
```json
{
  "type": "header",
  "key": "X-Frame-Options",
  "value": "SAMEORIGIN"
}
```

### CSP Fix
```json
{
  "type": "csp",
  "policy": "default-src 'self'",
  "mode": "report-only",
  "report_uri": "/wp-json/ssc/v1/csp-report"
}
```

### Banner Fix
```json
{
  "type": "banner",
  "enabled": true,
  "region": "EU",
  "message": "Cookie message",
  "block_scripts": ["analytics.js"]
}
```

### Policy Page Fix
```json
{
  "type": "policy_page",
  "title": "Privacy Policy",
  "content": "Policy content...",
  "slug": "privacy-policy"
}
```

## Snapshot System

Every change creates a snapshot with:
- Unique ID
- Type (apply_fixes, rollback, csp_promote)
- Data (configuration at that point)
- Timestamp
- User/agent info

Last 20 snapshots are kept for rollback.

## Security

- API key is generated on activation (48 characters)
- All agent endpoints require Bearer token authentication
- Keys never exposed to front-end
- Constant-time comparison for token validation

## Region Modes

- **EU**: GDPR compliance - explicit consent required, script blocking
- **MY**: PDPA compliance - explicit consent required, script blocking  
- **US**: Notice-based - informational banner
- **OTHER**: Simple acceptance banner

## Requirements

- WordPress 5.0+
- PHP 7.4+
- REST API enabled

## Support

Part of the SiteSecureCheck system. See main project documentation.

## License

MIT
