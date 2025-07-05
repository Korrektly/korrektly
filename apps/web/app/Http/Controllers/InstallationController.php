<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Installation;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Installation::class);

        $payload = $request->validate([
            'mode' => 'sometimes|in:list,show,aggregate',
            'app_id' => 'sometimes|uuid',
            'installation_id' => 'sometimes|uuid',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'timezone' => 'sometimes|string',
            'group_by' => 'sometimes|in:hour,day,week,month',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
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
        if (! isset($payload['installation_id'])) {
            return response()->json([
                'message' => 'installation_id is required when mode is show',
            ], 400);
        }

        $installation = Installation::with('app')->find($payload['installation_id']);

        if (! $installation) {
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

            if (! $app) {
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

        // Pagination parameters
        $perPage = $payload['per_page'] ?? 15;
        $currentPage = $payload['page'] ?? 1;

        // Get paginated results
        $paginatedInstallations = $query->with('app')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // For growth calculations, we need all installations (not paginated)
        $allInstallations = $query->with('app')->orderBy('created_at', 'desc')->get();

        // Calculate growth metrics
        $growth = $this->calculatePeriodGrowth($allInstallations, $payload, $timezone, $request);

        // Calculate most adopted version
        $mostAdoptedVersion = $this->calculateMostAdoptedVersion($allInstallations);
        $growth['most_adopted_version'] = $mostAdoptedVersion;

        return response()->json([
            'installations' => $paginatedInstallations->items(),
            'meta' => [
                'current_page' => $paginatedInstallations->currentPage(),
                'last_page' => $paginatedInstallations->lastPage(),
                'per_page' => $paginatedInstallations->perPage(),
                'total' => $paginatedInstallations->total(),
                'from' => $paginatedInstallations->firstItem(),
                'to' => $paginatedInstallations->lastItem(),
                'has_more_pages' => $paginatedInstallations->hasMorePages(),
                'timezone' => $timezone,
                'total_count' => $allInstallations->count(), // Keep for backward compatibility
            ],
            'growth' => $growth,
        ]);
    }

    private function aggregateInstallations(array $payload, string $timezone, Request $request)
    {
        if (! isset($payload['start_date']) || ! isset($payload['end_date'])) {
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

            if (! $app) {
                return response()->json([
                    'message' => 'App not found or does not belong to your workspace',
                ], 404);
            }

            $query->where('app_id', $payload['app_id']);
        }

        // Get all installations and group them using PHP instead of database-specific functions
        $installations = $query->with('app:id,name')->orderBy('created_at')->get();

        // If no installations found, return empty aggregations
        if ($installations->isEmpty()) {
            return response()->json([
                'aggregations' => [],
                'meta' => [
                    'start_date' => $startDate->setTimezone($timezone)->toDateTimeString(),
                    'end_date' => $endDate->setTimezone($timezone)->toDateTimeString(),
                    'timezone' => $timezone,
                    'group_by' => $groupBy,
                    'total_periods' => 0,
                ],
                'growth' => [
                    'installations_growth' => 0,
                    'active_users_growth' => 0,
                    'period_over_period_growth' => 0,
                ],
            ]);
        }

        // Group installations by time period using PHP
        $groupedInstallations = [];
        $activeUsersByPeriod = [];

        foreach ($installations as $installation) {
            $installationDate = Carbon::parse($installation->created_at)->setTimezone($timezone);

            // Format date based on groupBy parameter
            $period = match ($groupBy) {
                'hour' => $installationDate->format('Y-m-d H:00:00'),
                'week' => $installationDate->format('Y-W'),
                'month' => $installationDate->format('Y-m'),
                default => $installationDate->format('Y-m-d'), // day
            };

            if (! isset($groupedInstallations[$period])) {
                $groupedInstallations[$period] = [];
                $activeUsersByPeriod[$period] = 0;
            }

            if (! isset($groupedInstallations[$period][$installation->app_id])) {
                $groupedInstallations[$period][$installation->app_id] = [
                    'app_id' => $installation->app_id,
                    'app_name' => $installation->app->name ?? null,
                    'count' => 0,
                ];
            }

            $groupedInstallations[$period][$installation->app_id]['count']++;

            // Count active users (last seen within the period or recently)
            $lastSeenDate = Carbon::parse($installation->last_seen_at)->setTimezone($timezone);
            $periodStart = Carbon::parse($period)->setTimezone($timezone);
            $periodEnd = match ($groupBy) {
                'hour' => $periodStart->copy()->addHour(),
                'week' => $periodStart->copy()->addWeek(),
                'month' => $periodStart->copy()->addMonth(),
                default => $periodStart->copy()->addDay(),
            };

            if ($lastSeenDate->between($periodStart, $periodEnd) || $lastSeenDate->diffInDays(now()) <= 7) {
                $activeUsersByPeriod[$period]++;
            }
        }

        // Convert to the expected format
        $aggregations = [];
        foreach ($groupedInstallations as $period => $apps) {
            $totalCount = array_sum(array_column($apps, 'count'));
            $aggregations[] = [
                'period' => $period,
                'total_count' => $totalCount,
                'active_count' => $activeUsersByPeriod[$period] ?? 0,
                'apps' => array_values($apps),
            ];
        }

        // Sort by period
        usort($aggregations, function ($a, $b) {
            return strcmp($a['period'], $b['period']);
        });

        // Calculate growth metrics
        $growth = $this->calculateAggregationGrowth($aggregations, $startDate, $endDate);

        return response()->json([
            'aggregations' => $aggregations,
            'meta' => [
                'start_date' => $startDate->setTimezone($timezone)->toDateTimeString(),
                'end_date' => $endDate->setTimezone($timezone)->toDateTimeString(),
                'timezone' => $timezone,
                'group_by' => $groupBy,
                'total_periods' => count($aggregations),
            ],
            'growth' => $growth,
        ]);
    }

    private function calculatePeriodGrowth($installations, array $payload, string $timezone, Request $request)
    {
        if (! isset($payload['start_date']) || ! isset($payload['end_date'])) {
            return [
                'installations_growth' => 0,
                'active_users_growth' => 0,
            ];
        }

        $startDate = Carbon::parse($payload['start_date'])->setTimezone($timezone)->utc();
        $endDate = Carbon::parse($payload['end_date'])->setTimezone($timezone)->utc();
        $durationDays = $startDate->diffInDays($endDate);

        // Get previous period data for comparison
        $previousPeriodStart = $startDate->copy()->subDays($durationDays);
        $previousPeriodEnd = $startDate->copy();

        $previousQuery = Installation::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd]);
        $previousQuery->whereHas('app', function ($q) use ($request) {
            $q->where('workspace_id', $request->user()->current_workspace_id);
        });

        if (isset($payload['app_id'])) {
            $previousQuery->where('app_id', $payload['app_id']);
        }

        $previousInstallations = $previousQuery->get();

        // Calculate growth rates
        $currentCount = $installations->count();
        $previousCount = $previousInstallations->count();
        $installationsGrowth = $previousCount > 0 ? (($currentCount - $previousCount) / $previousCount) * 100 : 0;

        // Calculate active users growth
        $currentActiveUsers = $installations->filter(function ($installation) {
            return Carbon::parse($installation->last_seen_at)->diffInDays(now()) <= 7;
        })->count();

        $previousActiveUsers = $previousInstallations->filter(function ($installation) {
            return Carbon::parse($installation->last_seen_at)->diffInDays(now()) <= 7;
        })->count();

        $activeUsersGrowth = $previousActiveUsers > 0 ? (($currentActiveUsers - $previousActiveUsers) / $previousActiveUsers) * 100 : 0;

        return [
            'installations_growth' => round($installationsGrowth, 2),
            'active_users_growth' => round($activeUsersGrowth, 2),
            'current_period' => [
                'installations' => $currentCount,
                'active_users' => $currentActiveUsers,
            ],
            'previous_period' => [
                'installations' => $previousCount,
                'active_users' => $previousActiveUsers,
            ],
        ];
    }

    private function calculateMostAdoptedVersion($installations)
    {
        if ($installations->isEmpty()) {
            return null;
        }

        // Count versions, treating null/empty as "Unknown"
        $versionCounts = [];
        $totalCount = $installations->count();

        foreach ($installations as $installation) {
            $version = $installation->version ?: 'Unknown';
            $versionCounts[$version] = ($versionCounts[$version] ?? 0) + 1;
        }

        if (empty($versionCounts)) {
            return null;
        }

        // Find the most common version
        arsort($versionCounts);
        $mostCommonVersion = array_key_first($versionCounts);
        $count = $versionCounts[$mostCommonVersion];
        $percentage = round(($count / $totalCount) * 100, 1);

        return [
            'version' => $mostCommonVersion,
            'count' => $count,
            'percentage' => $percentage,
        ];
    }

    private function calculateAggregationGrowth(array $aggregations, Carbon $startDate, Carbon $endDate)
    {
        if (count($aggregations) < 2) {
            return [
                'installations_growth' => 0,
                'active_users_growth' => 0,
                'period_over_period_growth' => 0,
            ];
        }

        // Calculate period-over-period growth (comparing last two periods)
        $lastPeriod = end($aggregations);
        $secondLastPeriod = $aggregations[count($aggregations) - 2];

        $periodOverPeriodGrowth = $secondLastPeriod['total_count'] > 0
            ? (($lastPeriod['total_count'] - $secondLastPeriod['total_count']) / $secondLastPeriod['total_count']) * 100
            : 0;

        // Calculate overall growth (first vs last period)
        $firstPeriod = $aggregations[0];
        $installationsGrowth = $firstPeriod['total_count'] > 0
            ? (($lastPeriod['total_count'] - $firstPeriod['total_count']) / $firstPeriod['total_count']) * 100
            : 0;

        $activeUsersGrowth = $firstPeriod['active_count'] > 0
            ? (($lastPeriod['active_count'] - $firstPeriod['active_count']) / $firstPeriod['active_count']) * 100
            : 0;

        return [
            'installations_growth' => round($installationsGrowth, 2),
            'active_users_growth' => round($activeUsersGrowth, 2),
            'period_over_period_growth' => round($periodOverPeriodGrowth, 2),
            'total_periods' => count($aggregations),
            'trend' => $this->calculateTrend($aggregations),
        ];
    }

    private function calculateTrend(array $aggregations)
    {
        if (count($aggregations) < 3) {
            return 'stable';
        }

        $values = array_column($aggregations, 'total_count');
        $increases = 0;
        $decreases = 0;

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] > $values[$i - 1]) {
                $increases++;
            } elseif ($values[$i] < $values[$i - 1]) {
                $decreases++;
            }
        }

        if ($increases > $decreases) {
            return 'increasing';
        } elseif ($decreases > $increases) {
            return 'decreasing';
        }

        return 'stable';
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'app_id' => 'required|exists:apps,id',
            'identifier' => 'required|string|max:255',
            'version' => 'sometimes|string|max:255',
            'url' => 'sometimes|string',
        ]);

        $installation = Installation::updateOrCreate([
            'app_id' => $payload['app_id'],
            'identifier' => $payload['identifier'],
        ], [
            'url' => $payload['url'] ?? null,
            'last_seen_at' => now(),
            'version' => $payload['version'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent()->setStatusCode($installation->wasRecentlyCreated ? 201 : 200);
    }
}
