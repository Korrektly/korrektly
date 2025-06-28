<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Installation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Installation>
 */
class InstallationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => Str::uuid(),
            'app_id' => App::factory(),
            'last_seen_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'version' => fake()->semver(),
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * Create a recently active installation
     */
    public function recentlyActive(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_seen_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Create an inactive installation
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_seen_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    /**
     * Create installation for a specific app
     */
    public function forApp(App $app): static
    {
        return $this->state(fn(array $attributes) => [
            'app_id' => $app->id,
        ]);
    }

    /**
     * Create installation with a specific version
     */
    public function withVersion(string $version): static
    {
        return $this->state(fn(array $attributes) => [
            'version' => $version,
        ]);
    }

    /**
     * Create installation with IPv6 address
     */
    public function withIPv6(): static
    {
        return $this->state(fn(array $attributes) => [
            'ip_address' => fake()->ipv6(),
        ]);
    }
}
