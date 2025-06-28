<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceMembership extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
    ];

    protected $appends = [
        'permissions',
    ];

    // Relationships
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Accessors
    public function getPermissionsAttribute()
    {
        return config('workspace.roles.' . strtolower($this->role) . '.permissions', []);
    }

    public function isOwner()
    {
        return $this->role === 'owner';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isMember()
    {
        return $this->role === 'member';
    }

    public function hasAnyPermissions(array $permissions)
    {
        return collect($permissions)->some(fn($permission) => in_array($permission, $this->permissions));
    }
}
