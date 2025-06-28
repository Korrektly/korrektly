<?php

use App\Models\App;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->forOwner($this->owner)->create();

    WorkspaceMembership::factory()
        ->forWorkspaceAndUser($this->workspace, $this->owner)
        ->owner()
        ->create();

    $this->owner->update(['current_workspace_id' => $this->workspace->id]);

    // Create a regular member user
    $this->member = User::factory()->create();
    WorkspaceMembership::factory()
        ->forWorkspaceAndUser($this->workspace, $this->member)
        ->member()
        ->create();

    $this->member->update(['current_workspace_id' => $this->workspace->id]);
});

describe('App Index Endpoint', function () {
    test('requires authentication to access apps index', function () {
        $response = $this->getJson('/api/v1/apps');

        $response->assertStatus(401);
    });

    test('can list apps in current workspace', function () {
        App::factory()->forWorkspace($this->workspace)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/apps');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'apps' => [
                    '*' => [
                        'id',
                        'name',
                        'logo',
                        'url',
                        'type',
                        'workspace_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        expect($response->json('apps'))->toHaveCount(3);
    });

    test('only shows apps from current workspace', function () {
        $otherWorkspace = Workspace::factory()->create();

        // Create apps in current workspace
        App::factory()->forWorkspace($this->workspace)->count(2)->create();

        // Create apps in other workspace
        App::factory()->forWorkspace($otherWorkspace)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/apps');

        $response->assertStatus(200);
        expect($response->json('apps'))->toHaveCount(2);

        foreach ($response->json('apps') as $app) {
            expect($app['workspace_id'])->toBe($this->workspace->id);
        }
    });

    test('returns empty array when no apps exist in workspace', function () {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/apps');

        $response->assertStatus(200)
            ->assertJson(['apps' => []]);
    });
});

describe('App Show Endpoint', function () {
    test('requires authentication to view app', function () {
        $app = App::factory()->forWorkspace($this->workspace)->create();

        $response = $this->getJson("/api/v1/apps/{$app->id}");

        $response->assertStatus(401);
    });

    test('can view app in current workspace', function () {
        $app = App::factory()->forWorkspace($this->workspace)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/apps/{$app->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'app' => [
                    'id',
                    'name',
                    'logo',
                    'url',
                    'type',
                    'workspace_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('app.id'))->toBe($app->id);
    });

    test('returns 404 for non-existent app', function () {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/apps/non-existent-id');

        $response->assertStatus(404);
    });

    test('cannot view app from different workspace', function () {
        $otherWorkspace = Workspace::factory()->create();
        $otherApp = App::factory()->forWorkspace($otherWorkspace)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/apps/{$otherApp->id}");

        $response->assertStatus(403);
    });
});

describe('App Store Endpoint', function () {
    test('requires authentication to create app', function () {
        $response = $this->postJson('/api/v1/apps', [
            'name' => 'Test App',
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(401);
    });

    test('can create new app with valid data', function () {
        $appData = [
            'name' => 'Test Application',
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/apps', $appData);

        $response->assertStatus(302); // Redirects to app show page

        $this->assertDatabaseHas('apps', [
            'name' => 'Test Application',
            'url' => 'https://example.com',
            'workspace_id' => $this->workspace->id,
        ]);
    });

    test('automatically assigns app to current workspace', function () {
        $appData = [
            'name' => 'Workspace App',
            'url' => null,
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/apps', $appData);

        $response->assertStatus(302);

        $app = App::where('name', 'Workspace App')->first();
        expect($app->workspace_id)->toBe($this->workspace->id);
    });

    test('validates required fields when creating app', function () {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/apps', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('validates app name length', function () {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/apps', [
                'name' => str_repeat('a', 256), // Too long
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('can create app with null url', function () {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/apps', [
                'name' => 'No URL App',
                'url' => null,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('apps', [
            'name' => 'No URL App',
            'url' => null,
            'workspace_id' => $this->workspace->id,
        ]);
    });

    test('workspace members can create apps', function () {
        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/apps', [
                'name' => 'Member Created App',
                'url' => 'https://member.com',
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('apps', [
            'name' => 'Member Created App',
            'workspace_id' => $this->workspace->id,
        ]);
    });
});

describe('App Update Endpoint', function () {
    beforeEach(function () {
        $this->testApp = App::factory()->forWorkspace($this->workspace)->create([
            'name' => 'Original Name',
            'url' => 'https://original.com',
        ]);
    });

    test('requires authentication to update app', function () {
        $response = $this->putJson("/api/v1/apps/{$this->testApp->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    });

    test('can update app with valid data', function () {
        $updateData = [
            'name' => 'Updated Application Name',
            'url' => 'https://updated.com',
        ];

        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/apps/{$this->testApp->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'app' => [
                    'id',
                    'name',
                    'url',
                    'workspace_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('apps', [
            'id' => $this->testApp->id,
            'name' => 'Updated Application Name',
            'url' => 'https://updated.com',
        ]);

        expect($response->json('app.name'))->toBe('Updated Application Name');
    });

    test('validates required fields when updating app', function () {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/apps/{$this->testApp->id}", [
                'name' => '', // Empty name
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('returns 404 for non-existent app', function () {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/apps/non-existent-id', [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(404);
    });

    test('cannot update app from different workspace', function () {
        $otherWorkspace = Workspace::factory()->create();
        $otherApp = App::factory()->forWorkspace($otherWorkspace)->create();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/apps/{$otherApp->id}", [
                'name' => 'Unauthorized Update',
            ]);

        $response->assertStatus(403);
    });

    test('can update app url to null', function () {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/apps/{$this->testApp->id}", [
                'name' => 'Updated Name',
                'url' => null,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('apps', [
            'id' => $this->testApp->id,
            'url' => null,
        ]);
    });

    test('workspace members can update apps', function () {
        $response = $this->actingAs($this->member)
            ->putJson("/api/v1/apps/{$this->testApp->id}", [
                'name' => 'Member Updated App',
                'url' => 'https://member-updated.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('apps', [
            'id' => $this->testApp->id,
            'name' => 'Member Updated App',
        ]);
    });
});

describe('App Destroy Endpoint', function () {
    beforeEach(function () {
        $this->deleteApp = App::factory()->forWorkspace($this->workspace)->create();
    });

    test('requires authentication to delete app', function () {
        $response = $this->deleteJson("/api/v1/apps/{$this->deleteApp->id}");

        $response->assertStatus(401);
    });

    test('can delete app', function () {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/apps/{$this->deleteApp->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'App deleted successfully',
            ]);

        $this->assertDatabaseMissing('apps', [
            'id' => $this->deleteApp->id,
        ]);
    });

    test('returns 404 for non-existent app', function () {
        $response = $this->actingAs($this->owner)
            ->deleteJson('/api/v1/apps/non-existent-id');

        $response->assertStatus(404);
    });

    test('cannot delete app from different workspace', function () {
        $otherWorkspace = Workspace::factory()->create();
        $otherApp = App::factory()->forWorkspace($otherWorkspace)->create();

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/apps/{$otherApp->id}");

        $response->assertStatus(403);
    });

    test('workspace members can delete apps', function () {
        $response = $this->actingAs($this->member)
            ->deleteJson("/api/v1/apps/{$this->deleteApp->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('apps', [
            'id' => $this->deleteApp->id,
        ]);
    });
});
