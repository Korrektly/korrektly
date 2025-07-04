<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'owner_id',
    ];

    public function getLogoAttribute($value)
    {
        return "https://api.dicebear.com/9.x/glass/svg?seed={$this->id}";
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    public function getAppInstallationsCountAttribute(): int
    {
        return $this->apps()->withCount('installations')->get()->sum('installations_count');
    }
}
