<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\UsesUuid;

class Site extends Model
{
    use UsesUuid;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'auto_fix' => 'boolean',
        'last_score' => 'integer',
        'connection_checked_at' => 'datetime'
    ];

    public function scans(): HasMany { return $this->hasMany(Scan::class); }
    public function actions(): HasMany { return $this->hasMany(Action::class); }

    /**
     * Check plugin connection status with authentication
     */
    public function checkConnection(): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->wp_api_key
                ])
                ->get($this->wp_api_base . '/status');

            $isConnected = $response->successful();
            $data = $response->json();

            // Check if authentication worked
            $authenticated = $data['authenticated'] ?? false;

            $this->update([
                'connection_status' => ($isConnected && $authenticated) ? 'connected' : 'error',
                'connection_checked_at' => now(),
                'connection_error' => $isConnected ?
                    ($authenticated ? null : 'Authentication failed - check API key') :
                    'HTTP ' . $response->status()
            ]);

            return [
                'success' => $isConnected && $authenticated,
                'status' => $response->status(),
                'authenticated' => $authenticated,
                'data' => $data
            ];
        } catch (\Exception $e) {
            $this->update([
                'connection_status' => 'error',
                'connection_checked_at' => now(),
                'connection_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

