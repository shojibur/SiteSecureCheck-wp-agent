<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Action extends Model
{
    protected $guarded = [];
    protected $casts = [ 'payload' => 'array', 'result' => 'array' ];

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function scan(): BelongsTo { return $this->belongsTo(Scan::class); }
}

