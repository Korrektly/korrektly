<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->randomNumber(4),
            'logo' => fake()->imageUrl(200, 200, 'business', true),
            'owner_id' => User::factory(),
            'timezone' => fake()->randomElement([
                'UTC',
                'America/New_York',
                'America/Los_Angeles',
                'Europe/London',
                'Europe/Paris',
                'Asia/Tokyo',
                'Australia/Sydney',
            ]),
        ];
    }

    /**
     * Create a workspace with a specific owner
     */
    public function forOwner(User $owner): static
    {
        return $this->state(fn(array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Create a workspace with a specific name and slug
     */
    public function withName(string $name): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->randomNumber(4),
        ]);
    }
}
