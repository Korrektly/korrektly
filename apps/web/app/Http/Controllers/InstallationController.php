<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Installation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InstallationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Installation::class);

        $payload = $request->validate([
            'mode' => 'sometimes|in:list,show,aggregate',
            'app_id' => 'sometimes|exists:apps,id',
            'installation_id' => 'sometimes|exists:installations,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'timezone' => 'sometimes|string',
            'group_by' => 'sometimes|in:hour,day,week,month',
        ]);

        $mode = $payload['mode'] ?? 'list';
        $timezone = $payload['timezone'] ?? 'UTC';

        switch ($mode) {
            case 'show':
                return $this->showInstallation($payload, $request);

            case 'aggregate':
                return $this->aggregateInstallations($payload, $timezone, $request);

            default: // 'list'
                return $this->listInstallations($payload, $timezone, $request);
        }
    }

    private function showInstallation(array $payload, Request $request)
    {
        if (!isset($payload['installation_id'])) {
            return response()->json([
                'message' => 'installation_id is required when mode is show',
            ], 400);
        }

        $installation = Installation::with('app')->find($payload['installation_id']);

        if (!$installation) {
            return response()->json([
                'message' => 'Installation not found',
            ], 404);
        }

        $this->authorize('view', $installation);

        return response()->json([
            'installation' => $installation,
        ]);
    }

    private function listInstallations(array $payload, string $timezone, Request $request)
    {
        $query = Installation::query();

        // Only show installations from apps in user's current workspace
        $query->whereHas('app', function ($q) use ($request) {
            $q->where('workspace_id', $request->user()->current_workspace_id);
        });

        // Filter by app if provided (ensure it belongs to user's workspace)
        if (isset($payload['app_id'])) {
            $app = App::where('id', $payload['app_id'])
                ->where('workspace_id', $request->user()->current_workspace_id)
                ->first();

            if (!$app) {
                return response()->json([
                    'message' => 'App not found or does not belong to your workspace',
                ], 404);
            }

            $query->where('app_id', $payload['app_id']);
        }

        // Filter by date range if provided
        if (isset($payload['start_date']) && isset($payload['end_date'])) {
            $startDate = Carbon::parse($payload['start_date'])->setTimezone($timezone)->utc();
            $endDate = Carbon::parse($payload['end_date'])->setTimezone($timezone)->utc();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $installations = $query->with('app')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'installations' => $installations,
            'meta' => [
                'total_count' => $installations->count(),
                'timezone' => $timezone,
            ],
        ]);
    }

    private function aggregateInstallations(array $payload, string $timezone, Request $request)
    {
        if (!isset($payload['start_date']) || !isset($payload['end_date'])) {
            return response()->json([
                'message' => 'start_date and end_date are required when mode is aggregate',
            ], 400);
        }

        $groupBy = $payload['group_by'] ?? 'day';

        $startDate = Carbon::parse($payload['start_date'])->setTimezone($timezone)->utc();
        $endDate = Carbon::parse($payload['end_date'])->setTimezone($timezone)->utc();

        $query = Installation::whereBetween('created_at', [$startDate, $endDate]);

        // Only show installations from apps in user's current workspace
        $query->whereHas('app', function ($q) use ($request) {
            $q->where('workspace_id', $request->user()->current_workspace_id);
        });

        // Filter by app if provided (ensure it belongs to user's workspace)
        if (isset($payload['app_id'])) {
            $app = App::where('id', $payload['app_id'])
                ->where('workspace_id', $request->user()->current_workspace_id)
                ->first();

            if (!$app) {
                return response()->json([
                    'message' => 'App not found or does not belong to your workspace',
                ], 404);
            }

            $query->where('app_id', $payload['app_id']);
        }

        // Group by the specified time period
        switch ($groupBy) {
            case 'hour':
                $dateFormat = "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '{$timezone}'), '%Y-%m-%d %H:00:00')";
                break;
            case 'week':
                $dateFormat = "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '{$timezone}'), '%Y-%u')";
                break;
            case 'month':
                $dateFormat = "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '{$timezone}'), '%Y-%m')";
                break;
            default: // day
                $dateFormat = "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '{$timezone}'), '%Y-%m-%d')";
        }

        $installations = $query
            ->select(
                DB::raw("{$dateFormat} as period"),
                DB::raw('COUNT(*) as count'),
                'app_id'
            )
            ->groupBy('period', 'app_id')
            ->orderBy('period')
            ->with('app:id,name')
            ->get()
            ->groupBy('period')
            ->map(function ($group) {
                return [
                    'period' => $group->first()->period,
                    'total_count' => $group->sum('count'),
                    'apps' => $group->map(function ($item) {
                        return [
                            'app_id' => $item->app_id,
                            'app_name' => $item->app->name ?? null,
                            'count' => $item->count,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'aggregations' => $installations,
            'meta' => [
                'start_date' => $startDate->setTimezone($timezone)->toDateTimeString(),
                'end_date' => $endDate->setTimezone($timezone)->toDateTimeString(),
                'timezone' => $timezone,
                'group_by' => $groupBy,
                'total_periods' => $installations->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Installation::class);

        $payload = $request->validate([
            'app_id' => 'required|exists:apps,id',
            'identifier' => 'required|string|max:255',
        ]);

        // Ensure the app belongs to user's workspace (if authenticated)
        if ($request->user()) {
            $app = App::where('id', $payload['app_id'])
                ->where('workspace_id', $request->user()->current_workspace_id)
                ->first();

            if (!$app) {
                return response()->json([
                    'message' => 'App not found or does not belong to your workspace',
                ], 404);
            }
        }

        $key = 'installations.' . $payload['app_id'] . '.' . $payload['identifier'];

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'message' => 'Too many installations. Please try again later.',
            ], 429);
        }

        $installation = Installation::updateOrCreate([
            'app_id' => $payload['app_id'],
            'identifier' => $payload['identifier'],
        ], [
            'last_seen_at' => now(),
        ]);

        RateLimiter::hit($key);

        return response()->json([
            'installation' => $installation->load('app'),
        ], $installation->wasRecentlyCreated ? 201 : 200);
    }
}
