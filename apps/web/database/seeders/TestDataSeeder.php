<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\Installation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();

        // Create or get the test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create a workspace for the test user
        $workspace = Workspace::firstOrCreate([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
            'timezone' => 'UTC',
        ]);

        // Create workspace membership
        WorkspaceMembership::firstOrCreate([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        // Set the user's current workspace
        $user->update(['current_workspace_id' => $workspace->id]);

        // Generate random apps with faker
        $appTypes = ['web', 'mobile', 'desktop', 'extension', 'api'];
        $appCount = rand(5, 10);
        $apps = [];

        for ($i = 0; $i < $appCount; $i++) {
            $type = $faker->randomElement($appTypes);
            $companyName = $faker->company();
            $appName = $this->generateAppName($faker, $type, $companyName);
            $url = $this->generateAppUrl($faker, $type, $companyName);

            $apps[] = [
                'name' => $appName,
                'url' => $url,
                'type' => $type,
            ];
        }

        $createdApps = [];
        foreach ($apps as $appData) {
            $app = App::firstOrCreate([
                'name' => $appData['name'],
                'workspace_id' => $workspace->id,
            ], [
                'url' => $appData['url'],
                'type' => $appData['type'],
            ]);
            $createdApps[] = $app;
        }

        // Generate installations with realistic distribution over time
        $this->generateInstallations($createdApps);

        $this->command->info('Test data seeded successfully!');
        $this->command->info('User: test@example.com');
        $this->command->info('Password: password');
        $this->command->info('Created '.count($createdApps).' apps');
        $this->command->info('Generated installations with various patterns');
    }

    private function generateAppName($faker, string $type, string $companyName): string
    {
        $baseNames = [
            'web' => ['Dashboard', 'Portal', 'Hub', 'Console', 'Manager', 'Studio', 'Platform'],
            'mobile' => ['App', 'Mobile', 'Go', 'Pocket', 'Express', 'Lite', 'Pro'],
            'desktop' => ['Desktop', 'Client', 'Suite', 'Workstation', 'Pro', 'Studio'],
            'extension' => ['Extension', 'Helper', 'Assistant', 'Tools', 'Companion'],
            'api' => ['API', 'Service', 'Gateway', 'Connect', 'Bridge', 'Sync'],
        ];

        $baseName = $faker->randomElement($baseNames[$type] ?? ['App']);
        $companyShort = explode(' ', $companyName)[0];

        return match (rand(1, 3)) {
            1 => "{$companyShort} {$baseName}",
            2 => "{$baseName} by {$companyShort}",
            3 => "{$companyShort}{$baseName}",
        };
    }

    private function generateAppUrl($faker, string $type, string $companyName): ?string
    {
        $domain = strtolower(str_replace([' ', '.', ','], '', explode(' ', $companyName)[0]));

        return match ($type) {
            'web' => $faker->randomElement([
                "https://{$domain}.com",
                "https://app.{$domain}.com",
                "https://dashboard.{$domain}.com",
                "https://portal.{$domain}.com",
            ]),
            'mobile' => $faker->randomElement([
                "https://apps.apple.com/app/{$domain}",
                "https://play.google.com/store/apps/details?id=com.{$domain}.app",
                null, // Some mobile apps might not have URLs
            ]),
            'desktop' => $faker->boolean(30) ? "https://{$domain}.com/download" : null,
            'extension' => $faker->randomElement([
                "https://chrome.google.com/webstore/detail/{$domain}",
                "https://addons.mozilla.org/addon/{$domain}",
            ]),
            'api' => $faker->randomElement([
                "https://api.{$domain}.com",
                "https://{$domain}.com/api",
                null,
            ]),
            default => null,
        };
    }

    private function generateInstallations(array $apps): void
    {
        $faker = fake();
        $now = Carbon::now();

        // Generate installations over the last 90 days with realistic patterns
        foreach ($apps as $index => $app) {
            // Randomize installation counts based on app type and random popularity
            $baseCount = match ($app->type) {
                'web' => rand(100, 400),
                'mobile' => rand(50, 300),
                'desktop' => rand(20, 150),
                'extension' => rand(10, 100),
                'api' => rand(5, 50),
                default => rand(20, 200),
            };

            // Add random popularity multiplier
            $popularityMultiplier = $faker->randomFloat(2, 0.3, 2.5);
            $installationCount = (int) ($baseCount * $popularityMultiplier);

            // Create installations with different patterns over time
            for ($i = 0; $i < $installationCount; $i++) {
                // Generate installation dates with realistic distribution
                $daysAgo = $this->getRealisticDaysAgo($app->type, $faker);
                $installationDate = $now->copy()->subDays($daysAgo);

                // Create unique identifier
                $identifier = $faker->unique()->bothify('##########-????-####-????-############');

                // Generate last seen date (some installations are more active than others)
                $lastSeenDate = $this->getLastSeenDate($installationDate, $faker);

                Installation::create([
                    'app_id' => $app->id,
                    'identifier' => $identifier,
                    'created_at' => $installationDate,
                    'updated_at' => $installationDate,
                    'last_seen_at' => $lastSeenDate,
                ]);
            }
        }
    }

    private function getRealisticDaysAgo(string $appType, $faker): int
    {
        // Different app types have different installation patterns
        return match ($appType) {
            'web' => $faker->biasedNumberBetween(0, 90, function ($x) {
                // Web apps tend to have more recent installations (growth pattern)
                return 1 - ($x / 90) ** 0.5;
            }),
            'mobile' => $faker->biasedNumberBetween(0, 90, function ($x) {
                // Mobile apps have steady growth with some seasonal variations
                return 1 - ($x / 90) ** 0.7;
            }),
            'desktop' => $faker->biasedNumberBetween(0, 90, function ($x) {
                // Desktop apps are more consistent over time
                return 1 - ($x / 90);
            }),
            'extension' => $faker->biasedNumberBetween(0, 90, function ($x) use ($faker) {
                // Extensions might be newer, mostly recent installations
                return 1 - ($x / 90) ** $faker->randomFloat(1, 0.3, 0.8);
            }),
            'api' => $faker->biasedNumberBetween(0, 90, function ($x) use ($faker) {
                // APIs might have more sporadic adoption
                return 1 - ($x / 90) ** $faker->randomFloat(1, 0.4, 1.2);
            }),
            default => rand(0, 90),
        };
    }

    private function getLastSeenDate(Carbon $installationDate, $faker): Carbon
    {
        $now = Carbon::now();

        // Randomize activity patterns
        $activePercentage = $faker->numberBetween(60, 80);
        $moderatePercentage = $faker->numberBetween(15, 25);

        // Active installations (seen recently)
        if ($faker->boolean($activePercentage)) {
            $daysBack = $faker->numberBetween(1, 7);
            $startDate = max($installationDate->timestamp, $now->copy()->subDays($daysBack)->timestamp);

            return Carbon::createFromTimestamp($faker->numberBetween($startDate, $now->timestamp));
        }

        // Moderately active installations
        if ($faker->boolean($moderatePercentage)) {
            $daysBack = $faker->numberBetween(7, 30);
            $startDate = max($installationDate->timestamp, $now->copy()->subDays($daysBack)->timestamp);
            $endDate = max($startDate, $now->copy()->subDays(7)->timestamp);

            return Carbon::createFromTimestamp($faker->numberBetween($startDate, $endDate));
        }

        // Inactive installations
        $maxDaysInactive = $faker->numberBetween(30, 60);
        $endDate = max($installationDate->timestamp, $now->copy()->subDays($maxDaysInactive)->timestamp);
        $maxEndDate = min($endDate, $installationDate->copy()->addDays(rand(1, 30))->timestamp);

        return Carbon::createFromTimestamp($faker->numberBetween($installationDate->timestamp, $maxEndDate));
    }
}
