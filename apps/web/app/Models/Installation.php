<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'identifier',
        'app_id',
        'last_seen_at',
        'version',
        'ip_address',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Scope for recently active installations (active within the last hour)
     */
    public function scopeRecentlyActive($query)
    {
        return $query->where('last_seen_at', '>=', now()->subHour());
    }

    /**
     * Scope for inactive installations (not seen for more than a month)
     */
    public function scopeInactive($query)
    {
        return $query->where('last_seen_at', '<', now()->subMonth());
    }
}
