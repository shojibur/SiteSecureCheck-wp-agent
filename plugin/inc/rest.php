<?php
if (!defined('ABSPATH')) { exit; }

add_action('rest_api_init', function () {
    register_rest_route('ssc/v1', '/status', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () { return [ 'ok' => true ]; }
    ]);

    register_rest_route('ssc/v1', '/apply-fix', [
        'methods' => 'POST',
        'permission_callback' => function () {
            $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
            if (!$auth || stripos($auth, 'Bearer ') !== 0) { return false; }
            $token = trim(substr($auth, 7));
            return hash_equals((string) get_option('ssc_api_key'), $token);
        },
        'callback' => function (WP_REST_Request $req) {
            $body = $req->get_json_params();
            $fix = isset($body['fix']) ? $body['fix'] : null;
            if (!$fix || !is_array($fix)) { return new WP_Error('invalid', 'Invalid fix'); }
            $headers = get_option('ssc_custom_headers', []);
            if (!is_array($headers)) { $headers = []; }
            if (($fix['type'] ?? '') === 'header' && ($fix['key'] ?? '') && array_key_exists('value', $fix)) {
                $headers[$fix['key']] = $fix['value'];
                update_option('ssc_custom_headers', $headers, false);
                return ['ok' => true, 'fix' => $fix];
            }
            return new WP_Error('unsupported', 'Unsupported fix');
        }
    ]);
});

// Also emit any stored custom headers
add_action('wp_send_headers', function () {
    $headers = get_option('ssc_custom_headers', []);
    if (is_array($headers)) {
        foreach ($headers as $k => $v) {
            if (is_string($k)) { header($k . ': ' . $v, true); }
        }
    }
});

