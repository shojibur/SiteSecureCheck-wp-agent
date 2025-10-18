<?php
if (!defined('ABSPATH')) { exit; }

// Helper function to verify API key
function ssc_verify_api_key() {
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    if (!$auth || stripos($auth, 'Bearer ') !== 0) { return false; }
    $token = trim(substr($auth, 7));
    return hash_equals((string) get_option('ssc_api_key'), $token);
}

// Helper function to create version snapshot
function ssc_create_snapshot($type, $data) {
    $snapshots = get_option('ssc_snapshots', []);
    if (!is_array($snapshots)) { $snapshots = []; }

    $snapshot = [
        'id' => uniqid('snap_'),
        'type' => $type,
        'data' => $data,
        'timestamp' => current_time('mysql'),
        'user' => 'agent'
    ];

    array_unshift($snapshots, $snapshot);
    // Keep only last 20 snapshots
    $snapshots = array_slice($snapshots, 0, 20);
    update_option('ssc_snapshots', $snapshots, false);

    return $snapshot['id'];
}

add_action('rest_api_init', function () {
    // Status endpoint (requires auth to verify connection)
    register_rest_route('ssc/v1', '/status', [
        'methods' => 'GET',
        'permission_callback' => function() {
            // Allow both public access and authenticated access
            // If Authorization header is present, verify it
            $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
            if ($auth) {
                return ssc_verify_api_key();
            }
            // Otherwise allow public access for health checks
            return true;
        },
        'callback' => function () {
            $is_authenticated = ssc_verify_api_key();
            return [
                'ok' => true,
                'version' => '0.1.0',
                'site_url' => get_site_url(),
                'authenticated' => $is_authenticated
            ];
        }
    ]);

    // Get current configuration (secured)
    register_rest_route('ssc/v1', '/config', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            return [
                'headers' => get_option('ssc_custom_headers', []),
                'csp' => get_option('ssc_csp_config', []),
                'banner' => get_option('ssc_banner_config', []),
                'region' => get_option('ssc_region_mode', 'OTHER')
            ];
        }
    ]);

    // Get security state (secured) - for scanning
    register_rest_route('ssc/v1', '/security-state', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            global $wp_version;

            return [
                'wordpress' => [
                    'version' => $wp_version,
                    'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
                    'ssl_enabled' => is_ssl(),
                ],
                'configs' => [
                    'file_edit_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
                ],
                'headers' => get_option('ssc_custom_headers', []),
                'csp' => get_option('ssc_csp_config', []),
                'banner' => get_option('ssc_banner_config', []),
                'region' => get_option('ssc_region_mode', 'OTHER'),
                'database' => [
                    'prefix' => $GLOBALS['wpdb']->prefix,
                    'is_default_prefix' => $GLOBALS['wpdb']->prefix === 'wp_',
                ],
                'apis' => [
                    'xml_rpc_enabled' => apply_filters('xmlrpc_enabled', true),
                ]
            ];
        }
    ]);

    // Apply fixes (batch endpoint)
    register_rest_route('ssc/v1', '/apply-fixes', [
        'methods' => 'POST',
        'permission_callback' => function() {
            $verified = ssc_verify_api_key();
            error_log('SSC Apply Fixes Auth Check: ' . ($verified ? 'PASSED' : 'FAILED'));
            if (!$verified) {
                error_log('SSC Auth Header: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'MISSING'));
            }
            return $verified;
        },
        'callback' => function (WP_REST_Request $req) {
            $body = $req->get_json_params();
            $fixes = isset($body['fixes']) ? $body['fixes'] : [];
            if (!is_array($fixes)) {
                return new WP_Error('invalid', 'fixes must be an array');
            }

            $results = [];
            $snapshot_data = [];

            foreach ($fixes as $fix) {
                if (!is_array($fix)) { continue; }

                $type = $fix['type'] ?? '';
                $result = ['type' => $type, 'success' => false];

                switch ($type) {
                    case 'header':
                        if (($fix['key'] ?? '') && array_key_exists('value', $fix)) {
                            $headers = get_option('ssc_custom_headers', []);
                            if (!is_array($headers)) { $headers = []; }
                            $headers[$fix['key']] = $fix['value'];
                            update_option('ssc_custom_headers', $headers, false);
                            $snapshot_data['headers'] = $headers;
                            $result['success'] = true;
                            $result['key'] = $fix['key'];
                        }
                        break;

                    case 'csp':
                        $csp_config = [
                            'policy' => $fix['policy'] ?? '',
                            'mode' => $fix['mode'] ?? 'report-only',
                            'report_uri' => $fix['report_uri'] ?? ''
                        ];
                        update_option('ssc_csp_config', $csp_config, false);
                        $snapshot_data['csp'] = $csp_config;
                        $result['success'] = true;
                        break;

                    case 'banner':
                        $banner_config = [
                            'enabled' => $fix['enabled'] ?? true,
                            'region' => $fix['region'] ?? 'OTHER',
                            'message' => $fix['message'] ?? '',
                            'block_scripts' => $fix['block_scripts'] ?? []
                        ];
                        update_option('ssc_banner_config', $banner_config, false);
                        update_option('ssc_region_mode', $banner_config['region'], false);
                        $snapshot_data['banner'] = $banner_config;
                        $result['success'] = true;
                        break;

                    case 'policy_page':
                        $page_data = [
                            'title' => $fix['title'] ?? 'Privacy Policy',
                            'content' => $fix['content'] ?? '',
                            'slug' => $fix['slug'] ?? 'privacy-policy'
                        ];

                        // Check if page exists
                        $existing = get_page_by_path($page_data['slug']);
                        if ($existing) {
                            wp_update_post([
                                'ID' => $existing->ID,
                                'post_content' => $page_data['content']
                            ]);
                            $result['page_id'] = $existing->ID;
                        } else {
                            $page_id = wp_insert_post([
                                'post_title' => $page_data['title'],
                                'post_content' => $page_data['content'],
                                'post_name' => $page_data['slug'],
                                'post_status' => 'publish',
                                'post_type' => 'page'
                            ]);
                            $result['page_id'] = $page_id;
                        }
                        $result['success'] = true;
                        break;
                }

                $results[] = $result;
            }

            // Create snapshot
            if (!empty($snapshot_data)) {
                $snapshot_id = ssc_create_snapshot('apply_fixes', $snapshot_data);
                $results['snapshot_id'] = $snapshot_id;
            }

            return [
                'ok' => true,
                'applied' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
                'results' => $results
            ];
        }
    ]);

    // Rollback endpoint
    register_rest_route('ssc/v1', '/rollback', [
        'methods' => 'POST',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function (WP_REST_Request $req) {
            $body = $req->get_json_params();
            $snapshot_id = $body['snapshot_id'] ?? '';

            $snapshots = get_option('ssc_snapshots', []);
            if (!is_array($snapshots)) {
                return new WP_Error('no_snapshots', 'No snapshots available');
            }

            $snapshot = null;
            foreach ($snapshots as $s) {
                if ($s['id'] === $snapshot_id) {
                    $snapshot = $s;
                    break;
                }
            }

            if (!$snapshot) {
                return new WP_Error('not_found', 'Snapshot not found');
            }

            // Restore data
            $data = $snapshot['data'];
            if (isset($data['headers'])) {
                update_option('ssc_custom_headers', $data['headers'], false);
            }
            if (isset($data['csp'])) {
                update_option('ssc_csp_config', $data['csp'], false);
            }
            if (isset($data['banner'])) {
                update_option('ssc_banner_config', $data['banner'], false);
            }

            // Create new snapshot for rollback
            ssc_create_snapshot('rollback', $data);

            return [
                'ok' => true,
                'snapshot_id' => $snapshot_id,
                'restored_at' => current_time('mysql')
            ];
        }
    ]);

    // Get snapshots history
    register_rest_route('ssc/v1', '/snapshots', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            $snapshots = get_option('ssc_snapshots', []);
            return ['snapshots' => $snapshots];
        }
    ]);
});

// Emit custom headers
add_action('wp_send_headers', function () {
    $headers = get_option('ssc_custom_headers', []);
    if (is_array($headers)) {
        foreach ($headers as $k => $v) {
            if (is_string($k)) { header($k . ': ' . $v, true); }
        }
    }
});

