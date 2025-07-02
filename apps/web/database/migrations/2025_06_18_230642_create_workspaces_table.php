<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('logo')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index(['owner_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('current_workspace_id')->nullable()->constrained('workspaces');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->text('name')->primary();
            $table->timestamps();
        });

        Schema::create('workspace_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('role');
            $table->foreign('role')->references('name')->on('roles');
            $table->timestamps();

            $table->index(['workspace_id', 'user_id', 'role']);
        });

        DB::table('roles')->insert([
            ['name' => 'owner'],
            ['name' => 'admin'],
            ['name' => 'member'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
            $table->dropColumn('current_workspace_id');
        });
        Schema::table('database_connections', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
        });
        Schema::dropIfExists('workspace_memberships');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('roles');
    }
};
