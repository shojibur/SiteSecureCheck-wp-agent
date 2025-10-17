<?php
/*
Plugin Name: SiteSecureCheck
Description: Security headers, CSP, and control plane for SiteSecureCheck.
Version: 0.1.0
*/

if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/inc/headers.php';
require_once __DIR__ . '/inc/rest.php';
require_once __DIR__ . '/inc/csp.php';
require_once __DIR__ . '/inc/banner.php';
require_once __DIR__ . '/inc/rollback.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/security-fixes.php';

register_activation_hook(__FILE__, function () {
    if (!get_option('ssc_api_key')) {
        if (!function_exists('wp_generate_password')) { require_once ABSPATH . 'wp-includes/pluggable.php'; }
        $key = wp_generate_password(48, true, true);
        add_option('ssc_api_key', $key, '', false);
    }
});

