<?php
if (!defined('ABSPATH')) { exit; }

// Add admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'SiteSecureCheck',
        'SiteSecureCheck',
        'manage_options',
        'sitesecurecheck',
        'ssc_admin_page',
        'dashicons-shield',
        100
    );
});

// Admin page content
function ssc_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $api_key = get_option('ssc_api_key', '');
    $headers = get_option('ssc_custom_headers', []);
    $csp_config = get_option('ssc_csp_config', []);
    $banner_config = get_option('ssc_banner_config', []);
    $snapshots = get_option('ssc_snapshots', []);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="ssc-admin-container">
            <!-- API Key Section -->
            <div class="ssc-card">
                <h2>🔑 API Configuration</h2>
                <p>Use this API key in your SiteSecureCheck Dashboard to connect this site.</p>
                <div class="ssc-api-key-box">
                    <code id="ssc-api-key"><?php echo esc_html($api_key); ?></code>
                    <button type="button" class="button" onclick="sscCopyApiKey()">Copy</button>
                </div>
                <p class="description">
                    <strong>REST API Base:</strong> <code><?php echo esc_url(rest_url('ssc/v1')); ?></code>
                </p>
            </div>

            <!-- Current Configuration -->
            <div class="ssc-card">
                <h2>⚙️ Current Configuration</h2>
                
                <h3>Security Headers (<?php echo count($headers); ?>)</h3>
                <?php if (!empty($headers)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($headers as $key => $value): ?>
                                <tr>
                                    <td><code><?php echo esc_html($key); ?></code></td>
                                    <td><code><?php echo esc_html($value); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="description">No custom headers configured.</p>
                <?php endif; ?>

                <h3 style="margin-top: 20px;">Content Security Policy</h3>
                <?php if (!empty($csp_config)): ?>
                    <p>
                        <strong>Mode:</strong> 
                        <span class="ssc-badge <?php echo ($csp_config['mode'] ?? 'report-only') === 'enforce' ? 'ssc-badge-danger' : 'ssc-badge-warning'; ?>">
                            <?php echo esc_html(strtoupper($csp_config['mode'] ?? 'report-only')); ?>
                        </span>
                    </p>
                    <p><strong>Policy:</strong></p>
                    <textarea readonly style="width: 100%; height: 80px; font-family: monospace;"><?php echo esc_textarea($csp_config['policy'] ?? ''); ?></textarea>
                <?php else: ?>
                    <p class="description">No CSP configured.</p>
                <?php endif; ?>

                <h3 style="margin-top: 20px;">Cookie Banner</h3>
                <?php if (!empty($banner_config)): ?>
                    <p>
                        <strong>Status:</strong> 
                        <span class="ssc-badge <?php echo ($banner_config['enabled'] ?? false) ? 'ssc-badge-success' : 'ssc-badge-secondary'; ?>">
                            <?php echo ($banner_config['enabled'] ?? false) ? 'ENABLED' : 'DISABLED'; ?>
                        </span>
                    </p>
                    <p><strong>Region:</strong> <?php echo esc_html($banner_config['region'] ?? 'OTHER'); ?></p>
                <?php else: ?>
                    <p class="description">No banner configured.</p>
                <?php endif; ?>
            </div>

            <!-- Rollback History -->
            <div class="ssc-card">
                <h2>⏮️ Rollback History</h2>
                <?php if (!empty($snapshots)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Snapshot ID</th>
                                <th>Type</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($snapshots, 0, 10) as $snapshot): ?>
                                <tr>
                                    <td><code><?php echo esc_html($snapshot['id']); ?></code></td>
                                    <td><?php echo esc_html($snapshot['type']); ?></td>
                                    <td><?php echo esc_html($snapshot['timestamp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="description">No snapshots available.</p>
                <?php endif; ?>
            </div>

            <!-- Status -->
            <div class="ssc-card">
                <h2>📊 Status</h2>
                <p>
                    <strong>Plugin Version:</strong> 0.1.0<br>
                    <strong>REST API:</strong> <span class="ssc-badge ssc-badge-success">Active</span><br>
                    <strong>Site URL:</strong> <?php echo esc_url(get_site_url()); ?>
                </p>
            </div>
        </div>
    </div>

    <style>
        .ssc-admin-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .ssc-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .ssc-card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .ssc-card h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .ssc-api-key-box {
            display: flex;
            gap: 10px;
            align-items: center;
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .ssc-api-key-box code {
            flex: 1;
            word-break: break-all;
            font-size: 12px;
        }
        .ssc-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ssc-badge-success {
            background: #d4edda;
            color: #155724;
        }
        .ssc-badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .ssc-badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .ssc-badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
    </style>

    <script>
        function sscCopyApiKey() {
            const apiKey = document.getElementById('ssc-api-key').textContent;
            navigator.clipboard.writeText(apiKey).then(function() {
                alert('API Key copied to clipboard!');
            });
        }
    </script>
    <?php
}

// Add admin notice on activation
add_action('admin_notices', function () {
    if (!get_option('ssc_api_key')) {
        return;
    }

    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_sitesecurecheck') {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><strong>SiteSecureCheck is active!</strong> Go to <a href="<?php echo admin_url('admin.php?page=sitesecurecheck'); ?>">Settings</a> to view your API key.</p>
        </div>
        <?php
    }
});
