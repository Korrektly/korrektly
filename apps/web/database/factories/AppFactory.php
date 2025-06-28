<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\App>
 */
class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appNames = [
            'Dashboard',
            'Analytics',
            'CRM',
            'E-commerce',
            'Blog',
            'Portfolio',
            'Admin Panel',
            'API Gateway',
            'Monitoring',
            'Chat App',
        ];

        return [
            'name' => fake()->randomElement($appNames).' '.fake()->word(),
            'logo' => fake()->imageUrl(100, 100, 'technics', true),
            'url' => fake()->url(),
            'type' => fake()->randomElement(['web', 'mobile', 'desktop', 'api']),
            'workspace_id' => Workspace::factory(),
        ];
    }

    /**
     * Create a web app
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'web',
            'url' => fake()->url(),
        ]);
    }

    /**
     * Create a mobile app
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mobile',
            'url' => null,
        ]);
    }

    /**
     * Create an API app
     */
    public function api(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'api',
            'url' => fake()->url().'/api',
        ]);
    }

    /**
     * Create a desktop app
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'desktop',
            'url' => null,
        ]);
    }

    /**
     * Create app for a specific workspace
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }
}
