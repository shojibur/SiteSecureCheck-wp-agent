<?php
if (!defined('ABSPATH')) { exit; }

// Send custom security headers - fires very early before any output
add_action('send_headers', function () {
    // Get custom headers from options (set by API)
    $custom_headers = get_option('ssc_custom_headers', []);

    if (!empty($custom_headers) && is_array($custom_headers)) {
        // Send custom headers set via API
        foreach ($custom_headers as $key => $value) {
            if (is_string($key) && !empty($key)) {
                header($key . ': ' . $value, true);
            }
        }
    } else {
        // Default security headers if none configured
        header('X-Frame-Options: SAMEORIGIN', true);
        header('X-Content-Type-Options: nosniff', true);
        header('X-XSS-Protection: 1; mode=block', true);
    }

    // CSP if configured
    $csp_config = get_option('ssc_csp_config', []);
    if (!empty($csp_config['policy'])) {
        $header_name = ($csp_config['mode'] === 'enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
        header($header_name . ': ' . $csp_config['policy'], true);
    }
}, 1); // Priority 1 to run very early

