<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'logo',
        'url',
        'type',
        'workspace_id',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class);
    }
}
