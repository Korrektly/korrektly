<?php

use App\Models\App;
use App\Models\Installation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('installations.1.test-identifier');
    RateLimiter::clear('installations.2.test-identifier');
});

describe('Public Installation Store Endpoint', function () {
    test('can create new installation via public API', function () {
        $workspace = Workspace::factory()->create();
        $app = App::factory()->forWorkspace($workspace)->create();

        $response = $this->postJson('/api/v1/installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier',
            'version' => '1.0.0',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'installation' => [
                    'id',
                    'app_id',
                    'identifier',
                    'version',
                    'last_seen_at',
                    'created_at',
                    'updated_at',
                    'app' => [
                        'id',
                        'name',
                        'workspace_id',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier',
            'version' => '1.0.0',
        ]);
    });

    test('updates existing installation when called with same app_id and identifier', function () {
        $workspace = Workspace::factory()->create();
        $app = App::factory()->forWorkspace($workspace)->create();

        $installation = Installation::factory()->forApp($app)->create([
            'identifier' => 'test-identifier',
            'version' => '1.0.0',
        ]);

        $response = $this->postJson('/api/v1/installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier',
            'version' => '2.0.0',
        ]);

        $response->assertStatus(200);

        $installation->refresh();
        expect($installation->version)->toBe('2.0.0');
        expect($installation->last_seen_at)->not->toBeNull();
    });

    test('validates required fields for installation creation', function () {
        $response = $this->postJson('/api/v1/installations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['app_id', 'identifier']);
    });

    test('validates app exists when creating installation', function () {
        $response = $this->postJson('/api/v1/installations', [
            'app_id' => 'non-existent-id',
            'identifier' => 'test-identifier',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['app_id']);
    });

    test('rate limits installation creation by app and identifier', function () {
        $workspace = Workspace::factory()->create();
        $app = App::factory()->forWorkspace($workspace)->create();

        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/installations', [
                'app_id' => $app->id,
                'identifier' => 'test-identifier',
                'version' => "1.0.{$i}",
            ]);
        }

        // 11th request should be rate limited
        $response = $this->postJson('/api/v1/installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier',
            'version' => '1.0.11',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too many installations. Please try again later.',
            ]);
    });

    test('can create installation without optional version field', function () {
        $workspace = Workspace::factory()->create();
        $app = App::factory()->forWorkspace($workspace)->create();

        $response = $this->postJson('/api/v1/installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier-no-version',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('installations', [
            'app_id' => $app->id,
            'identifier' => 'test-identifier-no-version',
            'version' => null,
        ]);
    });
});

describe('Authenticated Installation Index Endpoint', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->forOwner($this->owner)->create();

        WorkspaceMembership::factory()
            ->forWorkspaceAndUser($this->workspace, $this->owner)
            ->owner()
            ->create();

        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        $this->testApp = App::factory()->forWorkspace($this->workspace)->create();
    });

    test('requires authentication to access installations index', function () {
        $response = $this->getJson('/api/v1/installations');

        $response->assertStatus(401);
    });

    test('can list installations in default mode', function () {
        Installation::factory()->forApp($this->testApp)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'installations' => [
                    '*' => [
                        'id',
                        'app_id',
                        'identifier',
                        'version',
                        'last_seen_at',
                        'created_at',
                        'updated_at',
                        'app',
                    ],
                ],
                'meta' => [
                    'total_count',
                    'timezone',
                ],
                'growth' => [
                    'installations_growth',
                    'active_users_growth',
                ],
            ]);

        expect($response->json('installations'))->toHaveCount(3);
    });

    test('can filter installations by app_id', function () {
        $app2 = App::factory()->forWorkspace($this->workspace)->create();

        Installation::factory()->forApp($this->testApp)->count(2)->create();
        Installation::factory()->forApp($app2)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/installations?app_id={$this->testApp->id}");

        $response->assertStatus(200);
        expect($response->json('installations'))->toHaveCount(2);

        foreach ($response->json('installations') as $installation) {
            expect($installation['app_id'])->toBe($this->testApp->id);
        }
    });

    test('returns 404 when filtering by non-existent app', function () {
        // Use a valid UUID format that doesn't exist in the database
        $nonExistentUuid = '12345678-1234-5678-9abc-123456789012';

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/installations?app_id={$nonExistentUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'App not found or does not belong to your workspace',
            ]);
    });

    test('returns 404 when filtering by app from different workspace', function () {
        $otherWorkspace = Workspace::factory()->create();
        $otherApp = App::factory()->forWorkspace($otherWorkspace)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/installations?app_id={$otherApp->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'App not found or does not belong to your workspace',
            ]);
    });

    test('can show specific installation', function () {
        $installation = Installation::factory()->forApp($this->testApp)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/installations?mode=show&installation_id={$installation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'installation' => [
                    'id',
                    'app_id',
                    'identifier',
                    'app',
                ],
            ]);

        expect($response->json('installation.id'))->toBe($installation->id);
    });

    test('returns 400 when show mode missing installation_id', function () {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?mode=show');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'installation_id is required when mode is show',
            ]);
    });

    test('returns 404 when showing non-existent installation', function () {
        // Use a valid UUID format that doesn't exist in the database
        $nonExistentUuid = '87654321-4321-8765-cba9-210987654321';

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/installations?mode=show&installation_id={$nonExistentUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Installation not found',
            ]);
    });

    test('can aggregate installations by day', function () {
        Installation::factory()->forApp($this->testApp)->count(5)->create([
            'created_at' => now()->subDays(1),
        ]);
        Installation::factory()->forApp($this->testApp)->count(3)->create([
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?'.http_build_query([
                'mode' => 'aggregate',
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->toDateString(),
                'group_by' => 'day',
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'aggregations' => [
                    '*' => [
                        'period',
                        'total_count',
                        'active_count',
                        'apps',
                    ],
                ],
                'meta' => [
                    'start_date',
                    'end_date',
                    'timezone',
                    'group_by',
                    'total_periods',
                ],
                'growth',
            ]);
    });

    test('returns 400 when aggregate mode missing required dates', function () {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?mode=aggregate');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'start_date and end_date are required when mode is aggregate',
            ]);
    });

    test('validates query parameters', function () {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?'.http_build_query([
                'mode' => 'invalid',
                'group_by' => 'invalid',
                'app_id' => 'invalid',
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mode', 'group_by', 'app_id']);
    });

    test('only shows installations from user current workspace', function () {
        $otherWorkspace = Workspace::factory()->create();
        $otherApp = App::factory()->forWorkspace($otherWorkspace)->create();

        // Create installations in both workspaces
        Installation::factory()->forApp($this->testApp)->count(2)->create();
        Installation::factory()->forApp($otherApp)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations');

        $response->assertStatus(200);
        expect($response->json('installations'))->toHaveCount(2);
    });

    test('can filter installations by date range', function () {
        Installation::factory()->forApp($this->testApp)->create([
            'created_at' => now()->subDays(10),
        ]);
        Installation::factory()->forApp($this->testApp)->create([
            'created_at' => now()->subDays(5),
        ]);
        Installation::factory()->forApp($this->testApp)->create([
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?'.http_build_query([
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
        expect($response->json('installations'))->toHaveCount(2);
    });

    test('calculates growth metrics correctly', function () {
        // Create installations in current period (2 days ago to today)
        Installation::factory()->forApp($this->testApp)->count(3)->create([
            'created_at' => now()->subDays(1),
            'last_seen_at' => now()->subHours(1),
        ]);

        // Create installations in previous period (4 days ago to 3 days ago)
        // The algorithm calculates previous period as same duration immediately before current period
        // Previous period should be from 4 days ago 00:00:00 to 3 days ago 00:00:00
        Installation::factory()->forApp($this->testApp)->count(2)->create([
            'created_at' => now()->subDays(4)->addHours(6), // 4 days ago + 6 hours, safely within previous period
            'last_seen_at' => now()->subDays(4),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/installations?'.http_build_query([
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);

        $growth = $response->json('growth');
        expect($growth)->toHaveKeys([
            'installations_growth',
            'active_users_growth',
            'current_period',
            'previous_period',
        ]);

        expect($growth['installations_growth'])->toBe(50); // 50% growth from 2 to 3
    });
});
