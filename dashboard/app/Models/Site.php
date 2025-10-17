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
    protected $casts = [ 'auto_fix' => 'boolean', 'last_score' => 'integer' ];

    public function scans(): HasMany { return $this->hasMany(Scan::class); }
    public function actions(): HasMany { return $this->hasMany(Action::class); }
}

