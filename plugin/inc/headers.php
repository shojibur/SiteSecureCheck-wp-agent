<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_send_headers', function () {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', true);
    header('X-Frame-Options: SAMEORIGIN', true);
    header('X-Content-Type-Options: nosniff', true);
    header('Referrer-Policy: strict-origin-when-cross-origin', true);
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()', true);
    $csp = "default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; connect-src 'self'";
    header('Content-Security-Policy-Report-Only: ' . $csp, true);
});

