<?php
if (!defined('ABSPATH')) { exit; }

// Apply CSP header based on configuration
add_action('wp_send_headers', function () {
    $csp_config = get_option('ssc_csp_config', []);

    if (empty($csp_config) || empty($csp_config['policy'])) {
        return; // No CSP configured
    }

    $policy = $csp_config['policy'];
    $mode = $csp_config['mode'] ?? 'report-only';
    $report_uri = $csp_config['report_uri'] ?? '';

    // Add report-uri if provided
    if ($report_uri && strpos($policy, 'report-uri') === false) {
        $policy .= '; report-uri ' . $report_uri;
    }

    // Set appropriate header based on mode
    if ($mode === 'enforce') {
        header('Content-Security-Policy: ' . $policy, true);
    } else {
        header('Content-Security-Policy-Report-Only: ' . $policy, true);
    }
}, 20); // Priority 20 to run after default headers

// CSP violation reporting endpoint
add_action('rest_api_init', function () {
    register_rest_route('ssc/v1', '/csp-report', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $req) {
            $body = $req->get_body();
            $report = json_decode($body, true);

            if (!$report) {
                return new WP_Error('invalid', 'Invalid CSP report');
            }

            // Store violation report
            $violations = get_option('ssc_csp_violations', []);
            if (!is_array($violations)) { $violations = []; }

            $violation = [
                'timestamp' => current_time('mysql'),
                'report' => $report,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];

            array_unshift($violations, $violation);
            // Keep only last 100 violations
            $violations = array_slice($violations, 0, 100);

            update_option('ssc_csp_violations', $violations, false);

            return ['ok' => true];
        }
    ]);

    // Get CSP violations
    register_rest_route('ssc/v1', '/csp-violations', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            $violations = get_option('ssc_csp_violations', []);
            return ['violations' => $violations];
        }
    ]);

    // Promote CSP to enforce mode
    register_rest_route('ssc/v1', '/csp-promote', [
        'methods' => 'POST',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            $csp_config = get_option('ssc_csp_config', []);

            if (empty($csp_config)) {
                return new WP_Error('no_csp', 'No CSP configuration found');
            }

            // Create snapshot before promoting
            ssc_create_snapshot('csp_promote', ['csp' => $csp_config]);

            // Update mode to enforce
            $csp_config['mode'] = 'enforce';
            $csp_config['promoted_at'] = current_time('mysql');
            update_option('ssc_csp_config', $csp_config, false);

            return [
                'ok' => true,
                'mode' => 'enforce',
                'promoted_at' => $csp_config['promoted_at']
            ];
        }
    ]);
});
