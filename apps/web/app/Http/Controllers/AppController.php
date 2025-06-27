<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AppController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', App::class);

        // Only show apps from user's current workspace
        $apps = App::where('workspace_id', $request->user()->current_workspace_id)->get();

        return response()->json([
            'apps' => $apps,
        ]);
    }

    public function show(App $app)
    {
        $this->authorize('view', $app);

        return response()->json([
            'app' => $app,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', App::class);

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string',
        ]);

        // Automatically assign to user's current workspace
        $payload['workspace_id'] = $request->user()->current_workspace_id;

        $app = App::create($payload);

        return response()->json([
            'app' => $app,
        ], 201);
    }

    public function update(Request $request, App $app)
    {
        $this->authorize('update', $app);

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string',
        ]);

        $app->update($payload);

        return response()->json([
            'app' => $app->fresh(),
        ]);
    }

    public function destroy(App $app)
    {
        $this->authorize('delete', $app);

        $app->delete();

        return response()->json([
            'message' => 'App deleted successfully',
        ]);
    }
}
