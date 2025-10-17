<?php
if (!defined('ABSPATH')) { exit; }

// Display cookie consent banner
add_action('wp_footer', function () {
    $banner_config = get_option('ssc_banner_config', []);

    if (empty($banner_config) || !($banner_config['enabled'] ?? false)) {
        return; // Banner not enabled
    }

    $region = $banner_config['region'] ?? 'OTHER';
    $message = $banner_config['message'] ?? 'This website uses cookies to ensure you get the best experience.';
    $block_scripts = $banner_config['block_scripts'] ?? [];

    // Only show banner if user hasn't consented
    ?>
    <div id="ssc-cookie-banner" class="ssc-banner" style="display: none;">
        <div class="ssc-banner-content">
            <p class="ssc-banner-message"><?php echo esc_html($message); ?></p>
            <div class="ssc-banner-buttons">
                <?php if (in_array($region, ['EU', 'MY'])): ?>
                    <button id="ssc-accept-all" class="ssc-btn ssc-btn-primary">Accept All</button>
                    <button id="ssc-accept-necessary" class="ssc-btn ssc-btn-secondary">Necessary Only</button>
                <?php else: ?>
                    <button id="ssc-accept-all" class="ssc-btn ssc-btn-primary">Accept</button>
                <?php endif; ?>
                <button id="ssc-banner-close" class="ssc-btn ssc-btn-text">×</button>
            </div>
        </div>
    </div>

    <style>
        .ssc-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1a1a1a;
            color: #fff;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .ssc-banner-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .ssc-banner-message {
            margin: 0;
            flex: 1;
            font-size: 14px;
            line-height: 1.5;
        }
        .ssc-banner-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .ssc-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .ssc-btn-primary {
            background: #0ea5e9;
            color: white;
        }
        .ssc-btn-primary:hover {
            background: #0284c7;
        }
        .ssc-btn-secondary {
            background: transparent;
            color: white;
            border: 1px solid #fff;
        }
        .ssc-btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }
        .ssc-btn-text {
            background: transparent;
            color: #999;
            font-size: 24px;
            padding: 5px 10px;
        }
        .ssc-btn-text:hover {
            color: #fff;
        }
        @media (max-width: 768px) {
            .ssc-banner-content {
                flex-direction: column;
                align-items: stretch;
            }
            .ssc-banner-buttons {
                justify-content: center;
            }
        }
    </style>

    <script>
        (function() {
            const COOKIE_NAME = 'ssc_consent';
            const COOKIE_DURATION = 365;

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? match[2] : null;
            }

            function setCookie(name, value, days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                document.cookie = name + '=' + value + '; expires=' + date.toUTCString() + '; path=/; SameSite=Lax';
            }

            function showBanner() {
                const banner = document.getElementById('ssc-cookie-banner');
                if (banner) {
                    banner.style.display = 'block';
                }
            }

            function hideBanner() {
                const banner = document.getElementById('ssc-cookie-banner');
                if (banner) {
                    banner.style.display = 'none';
                }
            }

            function acceptAll() {
                setCookie(COOKIE_NAME, 'all', COOKIE_DURATION);
                hideBanner();
                window.location.reload();
            }

            function acceptNecessary() {
                setCookie(COOKIE_NAME, 'necessary', COOKIE_DURATION);
                hideBanner();
            }

            // Check if user has already consented
            const consent = getCookie(COOKIE_NAME);
            if (!consent) {
                showBanner();

                // Block scripts if in EU/MY mode
                <?php if (in_array($region, ['EU', 'MY']) && !empty($block_scripts)): ?>
                const blockedScripts = <?php echo json_encode($block_scripts); ?>;
                document.addEventListener('DOMContentLoaded', function() {
                    const scripts = document.querySelectorAll('script[src]');
                    scripts.forEach(function(script) {
                        const src = script.src;
                        for (let pattern of blockedScripts) {
                            if (src.includes(pattern)) {
                                script.type = 'text/plain';
                                script.setAttribute('data-ssc-blocked', 'true');
                            }
                        }
                    });
                });
                <?php endif; ?>
            }

            // Event listeners
            document.getElementById('ssc-accept-all')?.addEventListener('click', acceptAll);
            document.getElementById('ssc-accept-necessary')?.addEventListener('click', acceptNecessary);
            document.getElementById('ssc-banner-close')?.addEventListener('click', hideBanner);
        })();
    </script>
    <?php
});

// Add REST endpoint to update banner config
add_action('rest_api_init', function () {
    register_rest_route('ssc/v1', '/banner-config', [
        'methods' => 'GET',
        'permission_callback' => 'ssc_verify_api_key',
        'callback' => function () {
            return get_option('ssc_banner_config', []);
        }
    ]);
});
