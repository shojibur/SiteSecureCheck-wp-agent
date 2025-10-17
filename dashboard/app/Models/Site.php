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
     * Check plugin connection status
     */
    public function checkConnection(): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->get($this->wp_api_base . '/status');

            $isConnected = $response->successful();

            $this->update([
                'connection_status' => $isConnected ? 'connected' : 'error',
                'connection_checked_at' => now(),
                'connection_error' => $isConnected ? null : 'HTTP ' . $response->status()
            ]);

            return [
                'success' => $isConnected,
                'status' => $response->status(),
                'data' => $response->json()
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

