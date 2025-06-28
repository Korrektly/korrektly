<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\Installation;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting test data seeding...');

        // Ensure roles exist (they should be created by migration)
        $this->ensureRolesExist();

        // Create workspace owners
        $this->command->info('👤 Creating workspace owners...');
        $owners = User::factory()->count(3)->create();

        foreach ($owners as $owner) {
            // Create workspace for each owner
            $workspace = Workspace::factory()->forOwner($owner)->create();

            // Create owner membership
            WorkspaceMembership::factory()
                ->forWorkspaceAndUser($workspace, $owner)
                ->owner()
                ->create();

            // Set current workspace
            $owner->update(['current_workspace_id' => $workspace->id]);

            $this->command->info("🏢 Created workspace: {$workspace->name} for {$owner->name}");

            // Add team members to workspace
            $this->createTeamMembers($workspace);

            // Create apps for the workspace
            $this->createAppsForWorkspace($workspace);
        }

        // Create some users without workspaces
        $this->command->info('👥 Creating standalone users...');
        User::factory()->count(5)->withoutCurrentWorkspace()->create();

        $this->command->info('✅ Test data seeding completed!');
        $this->printSummary();
    }

    /**
     * Ensure required roles exist in the database
     */
    private function ensureRolesExist(): void
    {
        $roles = ['owner', 'admin', 'member'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $this->command->info('🔑 Ensured roles exist');
    }

    /**
     * Create team members for a workspace
     */
    private function createTeamMembers(Workspace $workspace): void
    {
        // Create 2-3 admins
        $admins = User::factory()->count(rand(2, 3))->create();
        foreach ($admins as $admin) {
            WorkspaceMembership::factory()
                ->forWorkspaceAndUser($workspace, $admin)
                ->admin()
                ->create();

            $admin->update(['current_workspace_id' => $workspace->id]);
        }

        // Create 3-7 members
        $members = User::factory()->count(rand(3, 7))->create();
        foreach ($members as $member) {
            WorkspaceMembership::factory()
                ->forWorkspaceAndUser($workspace, $member)
                ->member()
                ->create();

            $member->update(['current_workspace_id' => $workspace->id]);
        }

        $this->command->info("👥 Added {$admins->count()} admins and {$members->count()} members to {$workspace->name}");
    }

    /**
     * Create apps and installations for a workspace
     */
    private function createAppsForWorkspace(Workspace $workspace): void
    {
        // Create 2-5 apps per workspace
        $appCount = rand(2, 5);
        $apps = collect();

        for ($i = 0; $i < $appCount; $i++) {
            $appType = ['web', 'mobile', 'api', 'desktop'][array_rand(['web', 'mobile', 'api', 'desktop'])];

            $app = App::factory()
                ->forWorkspace($workspace)
                ->{$appType}()
                ->create();

            $apps->push($app);

            // Create 1-10 installations per app
            $installationCount = rand(1, 10);

            for ($j = 0; $j < $installationCount; $j++) {
                $installationType = rand(1, 100) <= 70 ? 'recentlyActive' : 'inactive';

                Installation::factory()
                    ->forApp($app)
                    ->{$installationType}()
                    ->create();
            }

            $this->command->info("📱 Created {$appType} app: {$app->name} with {$installationCount} installations");
        }
    }

    /**
     * Print summary of created test data
     */
    private function printSummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 Test Data Summary:');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users', User::count()],
                ['Workspaces', Workspace::count()],
                ['Workspace Memberships', WorkspaceMembership::count()],
                ['Apps', App::count()],
                ['Installations', Installation::count()],
                ['Roles', Role::count()],
            ]
        );

        $this->command->info('');
        $this->command->info('🎯 You can now test your application with realistic data!');
        $this->command->info('💡 Try: php artisan tinker');
        $this->command->info('   Then: User::with(\'workspaceMemberships.workspace\')->get()');
    }
}
