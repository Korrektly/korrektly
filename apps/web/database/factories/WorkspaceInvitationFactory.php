<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkspaceInvitation>
 */
class WorkspaceInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['member', 'admin']),
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    /**
     * Create an invitation for a specific workspace
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Create an invitation invited by a specific user
     */
    public function invitedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_by' => $user->id,
        ]);
    }

    /**
     * Create an invitation with a specific email
     */
    public function forEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Create an invitation with a specific role
     */
    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Create a pending invitation (not expired, not accepted)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);
    }

    /**
     * Create an expired invitation
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
            'accepted_at' => null,
        ]);
    }

    /**
     * Create an accepted invitation
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(7),
            'accepted_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    /**
     * Create an invitation that expires soon
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(fake()->numberBetween(1, 24)),
            'accepted_at' => null,
        ]);
    }

    /**
     * Create an invitation with a specific token
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $token,
        ]);
    }
}
