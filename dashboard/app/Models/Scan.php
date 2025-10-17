<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    protected $guarded = [];
    protected $casts = [ 'issues' => 'array', 'plan' => 'array', 'raw' => 'array', 'applied' => 'boolean' ];

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function actions(): HasMany { return $this->hasMany(Action::class); }
}

