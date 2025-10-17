# SiteSecureCheck - Quick Start Guide

## Overview

SiteSecureCheck is an agent-powered security & compliance system for WordPress that scans sites, identifies issues, and safely applies fixes through a plugin.

**Current Status**: Dashboard ✅ | Plugin ✅ | Agent ⏳

## Setup Instructions

### 1. Dashboard (Laravel)

```bash
cd dashboard

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_DATABASE=sitesecurecheck
# etc...

# Run migrations
php artisan migrate

# Build frontend
npm run build

# Start server
php artisan serve
```

Access dashboard at: `http://localhost:8000`

### 2. WordPress Plugin

```bash
# Copy plugin to WordPress
cp -r plugin /path/to/wordpress/wp-content/plugins/sitesecurecheck

# Or create symlink for development
ln -s /path/to/SiteSecureCheck/plugin /path/to/wordpress/wp-content/plugins/sitesecurecheck
```

**In WordPress Admin**:
1. Go to Plugins → Activate "SiteSecureCheck"
2. Go to SiteSecureCheck menu
3. Copy the API Key

### 3. Connect Plugin to Dashboard

**In Dashboard**:
1. Go to Sites → New Site
2. Fill in:
   - **Name**: My WordPress Site
   - **Domain**: https://yoursite.com
   - **WP API Base**: https://yoursite.com/wp-json/ssc/v1
   - **WP API Key**: [paste from plugin]
   - **Region**: EU / MY / US / OTHER
   - **Auto Fix**: On/Off
3. Save

### 4. Test the Connection

```bash
# Test plugin status
curl https://yoursite.com/wp-json/ssc/v1/status

# Test authenticated endpoint
curl https://yoursite.com/wp-json/ssc/v1/config \
  -H "Authorization: Bearer YOUR_API_KEY"
```

## Using the System

### Manual Fix Application

From dashboard or via API:

```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/apply-fixes \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fixes": [
      {
        "type": "header",
        "key": "X-Frame-Options",
        "value": "SAMEORIGIN"
      },
      {
        "type": "csp",
        "policy": "default-src '\''self'\''",
        "mode": "report-only"
      },
      {
        "type": "banner",
        "enabled": true,
        "region": "EU",
        "message": "We use cookies"
      }
    ]
  }'
```

### Check Applied Fixes

**In WordPress**:
- Go to SiteSecureCheck → View current headers, CSP, banner config

**Via API**:
```bash
curl https://yoursite.com/wp-json/ssc/v1/config \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### Rollback Changes

```bash
# Get snapshots
curl https://yoursite.com/wp-json/ssc/v1/snapshots \
  -H "Authorization: Bearer YOUR_API_KEY"

# Rollback to specific snapshot
curl -X POST https://yoursite.com/wp-json/ssc/v1/rollback \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{"snapshot_id": "snap_xyz123"}'
```

### CSP Workflow

1. **Apply CSP in Report-Only**:
```json
{
  "type": "csp",
  "policy": "default-src 'self'; script-src 'self' 'unsafe-inline'",
  "mode": "report-only",
  "report_uri": "/wp-json/ssc/v1/csp-report"
}
```

2. **Monitor Violations**:
```bash
curl https://yoursite.com/wp-json/ssc/v1/csp-violations \
  -H "Authorization: Bearer YOUR_API_KEY"
```

3. **Promote to Enforce** (when no violations):
```bash
curl -X POST https://yoursite.com/wp-json/ssc/v1/csp-promote \
  -H "Authorization: Bearer YOUR_API_KEY"
```

## Dashboard Features

### Modern UI Highlights
- 🎨 Professional gradient designs
- 🔄 Smooth animations and transitions
- 📱 Fully responsive
- 🎯 Intuitive navigation
- 📊 Real-time status indicators
- 🔐 Secure credential management

### Available Pages
- **Sites Index**: List all sites, create new, scan, delete
- **Site Detail**: View scans, issues, actions with tabs
- **Site Edit**: Update configuration and settings
- **Profile**: Manage user account

## Plugin Features

### Security Headers
- Strict-Transport-Security (HSTS)
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy
- Custom headers via API

### CSP Management
- Report-Only for safe testing
- Violation tracking
- Auto report-uri injection
- One-click promotion to Enforce

### Cookie Banner
- Region-aware (EU/MY require consent)
- Script blocking for GDPR/PDPA
- Customizable messaging
- Accept All / Necessary Only options

### Rollback
- Automatic snapshots
- Version history (last 20)
- One-click restore
- Audit trail

## What's Next?

### Agent Implementation (TODO)
The missing piece is the AWS agent that:
1. Scans WordPress sites (headless browser)
2. Uses Bedrock to analyze and create remediation plan
3. Posts results to dashboard webhook
4. Optionally applies fixes via plugin API
5. Sends Teams/Email notifications

### Agent Structure (Planned)
```
agent/
├── lib/
│   ├── scanner/      # Headless browser
│   ├── bedrock/      # AI reasoning
│   └── notifier/     # Teams/Email
├── lambda/
│   └── scan.ts       # Main scan handler
└── cdk/
    └── stack.ts      # Infrastructure
```

## Troubleshooting

### Plugin Issues

**API Key not working**:
- Verify key in WordPress admin
- Check Bearer token format: `Authorization: Bearer <key>`
- Ensure REST API is enabled

**Headers not appearing**:
- Check if another plugin is conflicting
- Verify headers in /config endpoint
- Clear cache (browser, CDN, WordPress)

**Banner not showing**:
- Check banner enabled in config
- Clear browser cookies (`ssc_consent`)
- Verify region setting

### Dashboard Issues

**Site connection failed**:
- Test /status endpoint manually
- Verify API key is correct
- Check WP API base URL format
- Ensure WordPress REST API accessible

**Styles not loading**:
```bash
cd dashboard
npm run build
```

## Security Notes

- **API Keys**: 48-character, cryptographically secure
- **Authentication**: Bearer token, constant-time comparison
- **Storage**: WordPress options (can be encrypted)
- **Transmission**: HTTPS required for production
- **Secrets**: Never exposed to browser/frontend

## Support

- **Documentation**: See `/documents/PROJECT.md`
- **Plugin README**: See `/plugin/README.md`
- **Implementation Status**: See `/IMPLEMENTATION_STATUS.md`

---

**Built with**: Laravel 11, Vue 3, Tailwind CSS, WordPress 5.0+, AWS CDK (planned)
