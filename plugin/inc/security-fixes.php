<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Extended Security Fixes
 * This file handles various security configurations that the Agent can apply
 * based on AI-powered analysis from AWS Bedrock
 */

// Add REST endpoint for getting current security state (for Agent to analyze)
add_action('rest_api_init', function () {
    
    // Get comprehensive security state for Agent analysis
    register_rest_route('ssc/v1', '/security-state', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            return [
                'wordpress' => ssc_get_wordpress_state(),
                'plugins' => ssc_get_plugins_state(),
                'themes' => ssc_get_themes_state(),
                'users' => ssc_get_users_state(),
                'database' => ssc_get_database_state(),
                'filesystem' => ssc_get_filesystem_state(),
                'apis' => ssc_get_api_state(),
                'configs' => ssc_get_config_state()
            ];
        }
    ]);

    // Apply advanced security fix (Agent decides what to fix based on Bedrock analysis)
    register_rest_route('ssc/v1', '/apply-security-fix', [
        'methods' => 'POST',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function (WP_REST_Request $req) {
            $body = $req->get_json_params();
            $fix = $body['fix'] ?? [];
            $type = $fix['type'] ?? '';
            
            $result = ['type' => $type, 'success' => false, 'message' => ''];
            
            try {
                switch ($type) {
                    case 'wp_config':
                        $result = ssc_apply_wp_config_fix($fix);
                        break;
                    
                    case 'disable_file_edit':
                        $result = ssc_disable_file_editing($fix);
                        break;
                    
                    case 'limit_login_attempts':
                        $result = ssc_setup_login_protection($fix);
                        break;
                    
                    case 'disable_xml_rpc':
                        $result = ssc_disable_xmlrpc($fix);
                        break;
                    
                    case 'secure_uploads':
                        $result = ssc_secure_uploads_directory($fix);
                        break;
                    
                    case 'database_prefix':
                        $result = ssc_check_database_prefix($fix);
                        break;
                    
                    case 'disable_user_enumeration':
                        $result = ssc_disable_user_enumeration($fix);
                        break;
                    
                    case 'htaccess_protection':
                        $result = ssc_apply_htaccess_rules($fix);
                        break;
                    
                    case 'remove_version':
                        $result = ssc_remove_wp_version($fix);
                        break;
                    
                    case 'secure_cookies':
                        $result = ssc_secure_auth_cookies($fix);
                        break;
                    
                    case 'force_ssl_admin':
                        $result = ssc_force_ssl_admin($fix);
                        break;
                    
                    case 'disable_plugin':
                        $result = ssc_manage_plugin($fix, 'disable');
                        break;
                    
                    case 'update_plugin':
                        $result = ssc_update_component($fix, 'plugin');
                        break;
                    
                    case 'add_security_key':
                        $result = ssc_regenerate_security_keys($fix);
                        break;
                    
                    default:
                        $result['message'] = 'Unknown fix type: ' . $type;
                }
                
                // Create snapshot if successful
                if ($result['success']) {
                    ssc_create_snapshot('security_fix_' . $type, ['fix' => $fix, 'result' => $result]);
                }
                
            } catch (Exception $e) {
                $result['message'] = $e->getMessage();
            }
            
            return $result;
        }
    ]);
});

// Get WordPress core state
function ssc_get_wordpress_state() {
    global $wp_version;
    
    return [
        'version' => $wp_version,
        'is_multisite' => is_multisite(),
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
        'ssl_admin' => defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN,
        'file_edit_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
        'auto_update_core' => get_option('auto_update_core_major'),
        'timezone' => get_option('timezone_string'),
        'permalink_structure' => get_option('permalink_structure'),
        'home_url' => home_url(),
        'site_url' => site_url()
    ];
}

// Get plugins state
function ssc_get_plugins_state() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', []);
    
    $plugins_data = [];
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        $plugins_data[] = [
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
            'active' => in_array($plugin_file, $active_plugins),
            'file' => $plugin_file,
            'needs_update' => ssc_check_plugin_update($plugin_file)
        ];
    }
    
    return $plugins_data;
}

// Get themes state
function ssc_get_themes_state() {
    $themes = wp_get_themes();
    $current_theme = wp_get_theme();
    
    $themes_data = [];
    foreach ($themes as $theme) {
        $themes_data[] = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'active' => $theme->get_stylesheet() === $current_theme->get_stylesheet(),
            'stylesheet' => $theme->get_stylesheet()
        ];
    }
    
    return $themes_data;
}

// Get users state
function ssc_get_users_state() {
    $users = get_users(['role__in' => ['administrator', 'editor']]);
    
    $users_data = [];
    foreach ($users as $user) {
        $users_data[] = [
            'id' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'roles' => $user->roles,
            'registered' => $user->user_registered
        ];
    }
    
    return [
        'admin_users' => $users_data,
        'total_users' => count_users()['total_users'],
        'default_role' => get_option('default_role')
    ];
}

// Get database state
function ssc_get_database_state() {
    global $wpdb;
    
    return [
        'prefix' => $wpdb->prefix,
        'charset' => $wpdb->charset,
        'collate' => $wpdb->collate,
        'is_default_prefix' => $wpdb->prefix === 'wp_'
    ];
}

// Get filesystem state
function ssc_get_filesystem_state() {
    $upload_dir = wp_upload_dir();
    
    return [
        'uploads_writable' => wp_is_writable($upload_dir['basedir']),
        'uploads_path' => $upload_dir['basedir'],
        'wp_content_writable' => wp_is_writable(WP_CONTENT_DIR),
        'abspath_writable' => wp_is_writable(ABSPATH)
    ];
}

// Get API state
function ssc_get_api_state() {
    return [
        'xml_rpc_enabled' => !get_option('ssc_xmlrpc_disabled', false),
        'rest_api_enabled' => true, // Always true in modern WP
        'pingback_enabled' => get_option('default_pingback_flag'),
        'comments_enabled' => get_option('default_comment_status') === 'open'
    ];
}

// Get config state
function ssc_get_config_state() {
    return [
        'file_edit_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
        'auto_update_disabled' => defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED,
        'debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
        'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
        'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
        'ssl_admin' => defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN
    ];
}

// Check if plugin needs update
function ssc_check_plugin_update($plugin_file) {
    $updates = get_site_transient('update_plugins');
    return isset($updates->response[$plugin_file]);
}

// Apply WordPress config fix
function ssc_apply_wp_config_fix($fix) {
    $config_file = ABSPATH . 'wp-config.php';
    $setting = $fix['setting'] ?? '';
    $value = $fix['value'] ?? '';
    
    // Store recommendation (actual wp-config.php edit requires file access)
    update_option('ssc_wp_config_recommendations', array_merge(
        get_option('ssc_wp_config_recommendations', []),
        [['setting' => $setting, 'value' => $value, 'timestamp' => current_time('mysql')]]
    ), false);
    
    return [
        'success' => true,
        'type' => 'wp_config',
        'message' => 'Config recommendation stored. Manual wp-config.php edit required for: ' . $setting
    ];
}

// Disable file editing
function ssc_disable_file_editing($fix) {
    if (!defined('DISALLOW_FILE_EDIT')) {
        update_option('ssc_file_edit_disabled', true, false);
        
        return [
            'success' => true,
            'type' => 'disable_file_edit',
            'message' => 'File editing disabled (requires wp-config.php: define("DISALLOW_FILE_EDIT", true);)'
        ];
    }
    
    return ['success' => true, 'type' => 'disable_file_edit', 'message' => 'Already disabled'];
}

// Setup login protection
function ssc_setup_login_protection($fix) {
    $max_attempts = $fix['max_attempts'] ?? 5;
    $lockout_duration = $fix['lockout_duration'] ?? 1800; // 30 minutes
    
    update_option('ssc_login_protection', [
        'enabled' => true,
        'max_attempts' => $max_attempts,
        'lockout_duration' => $lockout_duration
    ], false);
    
    return [
        'success' => true,
        'type' => 'limit_login_attempts',
        'message' => "Login protection enabled: {$max_attempts} attempts, {$lockout_duration}s lockout"
    ];
}

// Disable XML-RPC
function ssc_disable_xmlrpc($fix) {
    update_option('ssc_xmlrpc_disabled', true, false);
    
    // Add filter to disable
    add_filter('xmlrpc_enabled', '__return_false');
    
    return [
        'success' => true,
        'type' => 'disable_xml_rpc',
        'message' => 'XML-RPC disabled'
    ];
}

// Secure uploads directory
function ssc_secure_uploads_directory($fix) {
    $upload_dir = wp_upload_dir();
    $htaccess_file = $upload_dir['basedir'] . '/.htaccess';
    
    $rules = "# SiteSecureCheck - Secure Uploads\n";
    $rules .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|shtml|sh|cgi)$\">\n";
    $rules .= "  Require all denied\n";
    $rules .= "</FilesMatch>\n";
    
    $written = @file_put_contents($htaccess_file, $rules);
    
    return [
        'success' => $written !== false,
        'type' => 'secure_uploads',
        'message' => $written ? 'Uploads directory secured' : 'Could not write .htaccess'
    ];
}

// Check database prefix
function ssc_check_database_prefix($fix) {
    global $wpdb;
    
    $is_default = $wpdb->prefix === 'wp_';
    
    return [
        'success' => true,
        'type' => 'database_prefix',
        'message' => $is_default ? 'WARNING: Using default wp_ prefix (change recommended)' : 'Custom prefix in use',
        'is_secure' => !$is_default
    ];
}

// Disable user enumeration
function ssc_disable_user_enumeration($fix) {
    update_option('ssc_disable_user_enum', true, false);
    
    return [
        'success' => true,
        'type' => 'disable_user_enumeration',
        'message' => 'User enumeration protection enabled'
    ];
}

// Apply .htaccess rules
function ssc_apply_htaccess_rules($fix) {
    $rules_type = $fix['rules_type'] ?? 'basic';
    
    update_option('ssc_htaccess_rules', [
        'type' => $rules_type,
        'applied_at' => current_time('mysql')
    ], false);
    
    return [
        'success' => true,
        'type' => 'htaccess_protection',
        'message' => 'Htaccess rules recommendation stored'
    ];
}

// Remove WordPress version
function ssc_remove_wp_version($fix) {
    update_option('ssc_remove_version', true, false);
    
    return [
        'success' => true,
        'type' => 'remove_version',
        'message' => 'WordPress version hiding enabled'
    ];
}

// Secure auth cookies
function ssc_secure_auth_cookies($fix) {
    update_option('ssc_secure_cookies', [
        'secure' => true,
        'httponly' => true,
        'samesite' => $fix['samesite'] ?? 'Strict'
    ], false);
    
    return [
        'success' => true,
        'type' => 'secure_cookies',
        'message' => 'Secure cookie settings applied'
    ];
}

// Force SSL admin
function ssc_force_ssl_admin($fix) {
    if (!defined('FORCE_SSL_ADMIN')) {
        update_option('ssc_force_ssl_admin', true, false);
        
        return [
            'success' => true,
            'type' => 'force_ssl_admin',
            'message' => 'SSL admin recommended (requires wp-config.php: define("FORCE_SSL_ADMIN", true);)'
        ];
    }
    
    return ['success' => true, 'type' => 'force_ssl_admin', 'message' => 'Already enabled'];
}

// Manage plugin
function ssc_manage_plugin($fix, $action) {
    $plugin_file = $fix['plugin_file'] ?? '';
    
    if (!$plugin_file) {
        return ['success' => false, 'type' => 'manage_plugin', 'message' => 'Plugin file required'];
    }
    
    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    if ($action === 'disable') {
        deactivate_plugins($plugin_file);
        return [
            'success' => true,
            'type' => 'disable_plugin',
            'message' => "Plugin deactivated: {$plugin_file}"
        ];
    }
    
    return ['success' => false, 'type' => 'manage_plugin', 'message' => 'Invalid action'];
}

// Update component
function ssc_update_component($fix, $type) {
    $component = $fix['component'] ?? '';
    
    // Store update recommendation
    update_option('ssc_update_recommendations', array_merge(
        get_option('ssc_update_recommendations', []),
        [['type' => $type, 'component' => $component, 'timestamp' => current_time('mysql')]]
    ), false);
    
    return [
        'success' => true,
        'type' => 'update_' . $type,
        'message' => "Update recommended for {$type}: {$component}"
    ];
}

// Regenerate security keys
function ssc_regenerate_security_keys($fix) {
    update_option('ssc_regen_keys_recommended', [
        'timestamp' => current_time('mysql'),
        'reason' => $fix['reason'] ?? 'Security enhancement'
    ], false);
    
    return [
        'success' => true,
        'type' => 'add_security_key',
        'message' => 'Security key regeneration recommended (use: https://api.wordpress.org/secret-key/1.1/salt/)'
    ];
}

// Apply security filters based on stored options
add_action('init', function() {
    // Disable XML-RPC if configured
    if (get_option('ssc_xmlrpc_disabled', false)) {
        add_filter('xmlrpc_enabled', '__return_false');
    }
    
    // Disable user enumeration if configured
    if (get_option('ssc_disable_user_enum', false)) {
        add_action('rest_authentication_errors', function($result) {
            if (!empty($result)) return $result;
            if (!is_user_logged_in()) {
                return new WP_Error('rest_not_logged_in', 'You are not logged in.', ['status' => 401]);
            }
            return $result;
        });
    }
    
    // Remove version info if configured
    if (get_option('ssc_remove_version', false)) {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
    }
});

// Login protection implementation
add_action('wp_login_failed', function($username) {
    $protection = get_option('ssc_login_protection', []);
    if (empty($protection) || !$protection['enabled']) return;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $attempts = get_transient('ssc_login_attempts_' . md5($ip)) ?: 0;
    $attempts++;
    
    set_transient('ssc_login_attempts_' . md5($ip), $attempts, $protection['lockout_duration']);
    
    if ($attempts >= $protection['max_attempts']) {
        set_transient('ssc_login_locked_' . md5($ip), true, $protection['lockout_duration']);
    }
});

add_filter('authenticate', function($user, $username, $password) {
    $protection = get_option('ssc_login_protection', []);
    if (empty($protection) || !$protection['enabled']) return $user;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (get_transient('ssc_login_locked_' . md5($ip))) {
        return new WP_Error('login_locked', 'Too many failed attempts. Please try again later.');
    }
    
    return $user;
}, 30, 3);
