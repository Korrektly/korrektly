<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->owner = User::factory()->create();

    // Create owner membership
    WorkspaceMembership::factory()
        ->forWorkspaceAndUser($this->workspace, $this->owner)
        ->owner()
        ->create();
    $this->owner->update(['current_workspace_id' => $this->workspace->id]);

    $this->inviteeEmail = 'invitee@example.com';
});

test('cannot create second invitation for same workspace while one is pending', function () {
    // Create a pending invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    // Try to create another invitation for the same email and workspace
    $response = $this->actingAs($this->owner)
        ->post(route('settings.workspace.invite'), [
            'email' => $this->inviteeEmail,
            'role' => 'admin',
        ]);

    $response->assertSessionHasErrors(['email']);
    expect(WorkspaceInvitation::where('email', $this->inviteeEmail)->count())->toBe(1);
});

test('can create invitation for same email in different workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $otherOwner = User::factory()->create();

    WorkspaceMembership::factory()
        ->forWorkspaceAndUser($otherWorkspace, $otherOwner)
        ->owner()
        ->create();
    $otherOwner->update(['current_workspace_id' => $otherWorkspace->id]);

    // Create invitation in first workspace
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    // Should be able to create invitation for same email in different workspace
    $response = $this->actingAs($otherOwner)
        ->post(route('settings.workspace.invite'), [
            'email' => $this->inviteeEmail,
            'role' => 'member',
        ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect(WorkspaceInvitation::where('email', $this->inviteeEmail)->count())->toBe(2);
});

test('can recreate invitation after previous one was accepted', function () {
    // Create an accepted invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subDays(1), // Already accepted
    ]);

    // Should be able to create new invitation since previous was accepted
    $response = $this->actingAs($this->owner)
        ->post(route('settings.workspace.invite'), [
            'email' => $this->inviteeEmail,
            'role' => 'admin',
        ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect(WorkspaceInvitation::where('email', $this->inviteeEmail)->count())->toBe(2);
});

test('can recreate invitation after previous one expired', function () {
    // Create an expired invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->subDays(1), // Expired
        'accepted_at' => null,
    ]);

    // Should be able to create new invitation since previous expired
    $response = $this->actingAs($this->owner)
        ->post(route('settings.workspace.invite'), [
            'email' => $this->inviteeEmail,
            'role' => 'admin',
        ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect(WorkspaceInvitation::where('email', $this->inviteeEmail)->count())->toBe(2);
});

test('cannot create invitation if user is already a member', function () {
    $existingUser = User::factory()->create(['email' => $this->inviteeEmail]);

    // Make user a member of the workspace
    WorkspaceMembership::factory()
        ->forWorkspaceAndUser($this->workspace, $existingUser)
        ->member()
        ->create();

    $response = $this->actingAs($this->owner)
        ->post(route('settings.workspace.invite'), [
            'email' => $this->inviteeEmail,
            'role' => 'admin',
        ]);

    $response->assertSessionHasErrors(['email']);
    expect(WorkspaceInvitation::where('email', $this->inviteeEmail)->count())->toBe(0);
});

test('pending invitation can be accepted', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    expect($invitation->isValid())->toBeTrue();
    expect($invitation->isExpired())->toBeFalse();
    expect($invitation->isAccepted())->toBeFalse();
});

test('expired invitation cannot be accepted', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->subDays(1),
        'accepted_at' => null,
    ]);

    expect($invitation->isValid())->toBeFalse();
    expect($invitation->isExpired())->toBeTrue();
    expect($invitation->isAccepted())->toBeFalse();
});

test('accepted invitation cannot be accepted again', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subHours(1),
    ]);

    expect($invitation->isValid())->toBeFalse();
    expect($invitation->isExpired())->toBeFalse();
    expect($invitation->isAccepted())->toBeTrue();
});

test('token generation has retry limit', function () {
    // Mock the Str::random to always return the same value to force collisions
    \Illuminate\Support\Str::createRandomStringsUsing(fn () => 'duplicate-token');

    // Create an invitation with the duplicate token
    WorkspaceInvitation::factory()->create([
        'token' => 'duplicate-token',
    ]);

    // Should throw exception after max attempts
    expect(fn () => WorkspaceInvitation::generateToken())
        ->toThrow(\RuntimeException::class, 'Unable to generate unique token after 5 attempts');

    // Reset random string generation
    \Illuminate\Support\Str::createRandomStringsNormally();
});

test('token generation succeeds with unique tokens', function () {
    $token1 = WorkspaceInvitation::generateToken();
    $token2 = WorkspaceInvitation::generateToken();

    expect($token1)->not->toBe($token2);
    expect(strlen($token1))->toBe(64);
    expect(strlen($token2))->toBe(64);
});

test('workspace creation succeeds with unique slugs', function () {
    $user1 = User::factory()->create(['name' => 'John Doe']);
    $user2 = User::factory()->create(['name' => 'John Doe']);

    // Trigger workspace creation for both users
    event(new \Illuminate\Auth\Events\Registered($user1));
    event(new \Illuminate\Auth\Events\Registered($user2));

    $workspace1 = $user1->fresh()->currentWorkspace;
    $workspace2 = $user2->fresh()->currentWorkspace;

    expect($workspace1)->not->toBeNull();
    expect($workspace2)->not->toBeNull();
    expect($workspace1->slug)->not->toBe($workspace2->slug);
});

test('workspace creation handles slug collisions gracefully', function () {
    $user1 = User::factory()->create(['name' => 'John Doe']);
    $user2 = User::factory()->create(['name' => 'John Doe']);

    // Create a workspace manually with a specific slug
    $existingWorkspace = Workspace::factory()->create([
        'slug' => 'john-doe-workspace-abc12',
        'owner_id' => $user1->id,
    ]);

    // Trigger workspace creation for the second user
    // This should generate a different slug due to the random suffix
    event(new \Illuminate\Auth\Events\Registered($user2));

    $workspace2 = $user2->fresh()->currentWorkspace;

    expect($workspace2)->not->toBeNull();
    expect($workspace2->slug)->not->toBe($existingWorkspace->slug);
    expect($workspace2->slug)->toContain('john-doe-workspace');
});

test('pending scope only returns valid invitations', function () {
    // Create various invitations
    $pending = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $expired = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->subDays(1),
        'accepted_at' => null,
    ]);

    $accepted = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subHours(1),
    ]);

    $pendingInvitations = WorkspaceInvitation::pending()->get();

    expect($pendingInvitations)->toHaveCount(1);
    expect($pendingInvitations->first()->id)->toBe($pending->id);
});

test('expired scope only returns expired invitations', function () {
    $pending = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $expired = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->subDays(1),
        'accepted_at' => null,
    ]);

    $accepted = WorkspaceInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subHours(1),
    ]);

    $expiredInvitations = WorkspaceInvitation::expired()->get();

    expect($expiredInvitations)->toHaveCount(1);
    expect($expiredInvitations->first()->id)->toBe($expired->id);
});

test('validation rule passes when no existing invitation exists', function () {
    $rules = [
        'email' => [
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('workspace_invitations')->where(function ($query) {
                return $query->where('workspace_id', $this->workspace->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now());
            }),
        ],
    ];

    $validator = \Illuminate\Support\Facades\Validator::make(['email' => $this->inviteeEmail], $rules);

    expect($validator->passes())->toBeTrue();
});

test('validation rule fails when pending invitation exists for same workspace and email', function () {
    // Create a pending invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $rules = [
        'email' => [
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('workspace_invitations')->where(function ($query) {
                return $query->where('workspace_id', $this->workspace->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now());
            }),
        ],
    ];

    $validator = \Illuminate\Support\Facades\Validator::make(['email' => $this->inviteeEmail], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('validation rule passes when invitation exists but is accepted', function () {
    // Create an accepted invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subHours(1),
    ]);

    $rules = [
        'email' => [
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('workspace_invitations')->where(function ($query) {
                return $query->where('workspace_id', $this->workspace->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now());
            }),
        ],
    ];

    $validator = \Illuminate\Support\Facades\Validator::make(['email' => $this->inviteeEmail], $rules);

    expect($validator->passes())->toBeTrue();
});

test('validation rule passes when invitation exists but is expired', function () {
    // Create an expired invitation
    WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => $this->inviteeEmail,
        'expires_at' => now()->subDays(1),
        'accepted_at' => null,
    ]);

    $rules = [
        'email' => [
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('workspace_invitations')->where(function ($query) {
                return $query->where('workspace_id', $this->workspace->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now());
            }),
        ],
    ];

    $validator = \Illuminate\Support\Facades\Validator::make(['email' => $this->inviteeEmail], $rules);

    expect($validator->passes())->toBeTrue();
});
