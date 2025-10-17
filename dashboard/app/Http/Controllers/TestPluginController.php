<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestPluginController extends Controller
{
    /**
     * Test plugin connection
     */
    public function testConnection(Site $site)
    {
        try {
            $response = Http::get($site->wp_api_base . '/status');
            
            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'message' => $response->successful() ? 'Plugin is reachable!' : 'Plugin not responding'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test authenticated endpoint
     */
    public function testAuth(Site $site)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $site->wp_api_key
            ])->get($site->wp_api_base . '/config');
            
            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'message' => $response->successful() ? 'Authentication works!' : 'Authentication failed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auth test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get security state (what Agent would see)
     */
    public function getSecurityState(Site $site)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $site->wp_api_key
            ])->get($site->wp_api_base . '/security-state');
            
            return response()->json([
                'success' => $response->successful(),
                'data' => $response->json(),
                'message' => 'Security state retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get security state: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply sample fixes (manual testing)
     */
    public function applySampleFixes(Site $site)
    {
        try {
            // Sample fixes to test the plugin
            $fixes = [
                [
                    'type' => 'header',
                    'key' => 'X-Frame-Options',
                    'value' => 'SAMEORIGIN'
                ],
                [
                    'type' => 'header',
                    'key' => 'X-Content-Type-Options',
                    'value' => 'nosniff'
                ],
                [
                    'type' => 'disable_xml_rpc',
                    'reason' => 'Manual test - prevent DDoS'
                ],
                [
                    'type' => 'remove_version',
                    'reason' => 'Manual test - hide WordPress version'
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $site->wp_api_key,
                'Content-Type' => 'application/json'
            ])->post($site->wp_api_base . '/apply-fixes', [
                'fixes' => $fixes
            ]);
            
            // Create action record
            \App\Models\Action::create([
                'site_id' => $site->id,
                'type' => 'manual_test_fixes',
                'payload' => ['fixes' => $fixes],
                'result' => $response->json()
            ]);

            return response()->json([
                'success' => $response->successful(),
                'data' => $response->json(),
                'message' => 'Sample fixes applied!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply fixes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a mock scan result (simulate what Agent would do)
     */
    public function createMockScan(Site $site)
    {
        try {
            // Get actual security state from plugin
            $securityState = Http::withHeaders([
                'Authorization' => 'Bearer ' . $site->wp_api_key
            ])->get($site->wp_api_base . '/security-state')->json();

            // Create mock issues based on security state
            $issues = [];
            $score = 100;

            if ($securityState['apis']['xml_rpc_enabled'] ?? false) {
                $issues[] = [
                    'id' => 'xml-rpc-enabled',
                    'severity' => 'high',
                    'why' => 'XML-RPC is enabled and can be exploited for DDoS attacks',
                    'cvss' => 7.5
                ];
                $score -= 15;
            }

            if ($securityState['database']['is_default_prefix'] ?? false) {
                $issues[] = [
                    'id' => 'default-db-prefix',
                    'severity' => 'medium',
                    'why' => 'Using default wp_ database prefix makes SQL injection easier',
                    'cvss' => 5.0
                ];
                $score -= 10;
            }

            if (!($securityState['configs']['file_edit_disabled'] ?? false)) {
                $issues[] = [
                    'id' => 'file-edit-enabled',
                    'severity' => 'medium',
                    'why' => 'Theme/plugin file editing is enabled in admin',
                    'cvss' => 6.0
                ];
                $score -= 10;
            }

            if ($securityState['wordpress']['debug_mode'] ?? false) {
                $issues[] = [
                    'id' => 'debug-mode',
                    'severity' => 'low',
                    'why' => 'Debug mode is enabled in production',
                    'cvss' => 3.0
                ];
                $score -= 5;
            }

            // Create mock plan
            $plan = [
                'fixes' => []
            ];

            if ($securityState['apis']['xml_rpc_enabled'] ?? false) {
                $plan['fixes'][] = [
                    'type' => 'disable_xml_rpc',
                    'priority' => 1,
                    'reason' => 'Prevent DDoS amplification attacks'
                ];
            }

            // Create scan record
            $scan = $site->scans()->create([
                'status' => 'complete',
                'score' => max(0, $score),
                'issues' => $issues,
                'plan' => $plan,
                'applied' => false,
                'raw' => [
                    'security_state' => $securityState,
                    'mock' => true,
                    'note' => 'This is a mock scan for testing'
                ]
            ]);

            // Update site score
            $site->update(['last_score' => $scan->score]);

            // Create action
            \App\Models\Action::create([
                'site_id' => $site->id,
                'scan_id' => $scan->id,
                'type' => 'mock_scan',
                'payload' => ['scan_id' => $scan->id],
                'result' => ['score' => $scan->score, 'issues_count' => count($issues)]
            ]);

            return response()->json([
                'success' => true,
                'scan' => $scan,
                'message' => 'Mock scan created successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create mock scan: ' . $e->getMessage()
            ], 500);
        }
    }
}
