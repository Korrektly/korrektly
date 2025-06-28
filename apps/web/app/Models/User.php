<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_workspace_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'avatar',
    ];

    public function getAvatarAttribute(): string
    {
        return "https://unavatar.io/{$this->email}";
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    /**
     * Check if user is a member of the given workspace
     */
    public function isMemberOfWorkspace(?string $workspaceId): bool
    {
        if (! $workspaceId) {
            return false;
        }

        return $this->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->exists();
    }

    /**
     * Get user's membership for a specific workspace
     */
    public function getMembershipForWorkspace(string $workspaceId): ?WorkspaceMembership
    {
        return $this->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->first();
    }

    /**
     * Check if user can manage resources in a workspace (owner or admin)
     */
    public function hasSomePermissions(array $permissions, ?string $workspaceId): bool
    {
        if (! $workspaceId) {
            return false;
        }

        return $this->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->get()
            ->some(fn ($membership) => collect($permissions)->some(fn ($permission) => in_array($permission, $membership->permissions)));
    }

    public function hasAllPermissions(array $permissions, string $workspaceId): bool
    {
        return $this->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->get()
            ->every(fn ($membership) => collect($permissions)->every(fn ($permission) => in_array($permission, $membership->permissions)));
    }
}
