<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    use HasUuid;

    protected $fillable = [
        'identifier',
        'app_id',
        'last_seen_at',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
