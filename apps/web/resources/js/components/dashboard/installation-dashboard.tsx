import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import axios from "axios";
import { toast } from "sonner";
import type {
    App,
    Installation,
    AggregationData,
    InstallationListResponse,
    InstallationAggregateResponse,
    GrowthMetrics,
} from "@/types/apps";

import DashboardHeader from "./dashboard-header";
import DashboardStats from "./dashboard-stats";
import DashboardChart from "./dashboard-chart";

interface ChartDataPoint {
    date: string;
    [key: string]: string | number;
}

export default function InstallationDashboard() {
    const [apps, setApps] = useState<App[]>([]);
    const [selectedAppId, setSelectedAppId] = useState<string>("all");
    const [installations, setInstallations] = useState<Installation[]>([]);
    const [aggregations, setAggregations] = useState<AggregationData[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [dateRange, setDateRange] = useState("7d");
    const [totalCount, setTotalCount] = useState(0);
    const [growth, setGrowth] = useState<GrowthMetrics>({
        installations_growth: 0,
        active_users_growth: 0,
        duration_days: 0,
    });

    // Fetch apps for the dropdown
    const fetchApps = async () => {
        try {
            const response = await axios.get(route("api.apps.index"));
            setApps(response.data.apps);
        } catch (error: unknown) {
            const message =
                (error as { response?: { data?: { message?: string } } })
                    ?.response?.data?.message || "Failed to fetch apps";
            toast.error(message);
        }
    };

    const getDateRangeValues = () => {
        const endDate = new Date();
        const startDate = new Date();

        // Set date range based on selection
        switch (dateRange) {
            case "today":
                startDate.setHours(0, 0, 0, 0);
                endDate.setHours(23, 59, 59, 999);
                break;
            case "7d":
                startDate.setDate(endDate.getDate() - 7);
                break;
            case "30d":
                startDate.setDate(endDate.getDate() - 30);
                break;
            case "90d":
                startDate.setDate(endDate.getDate() - 90);
                break;
            case "year":
                startDate.setFullYear(endDate.getFullYear() - 1);
                break;
            default:
                startDate.setDate(endDate.getDate() - 7);
        }

        return { startDate, endDate };
    };

    const fetchStats = async () => {
        try {
            setLoading(true);
            setError(null);

            const { startDate, endDate } = getDateRangeValues();

            // Fetch installations list
            const listParams = new URLSearchParams({
                mode: "list",
                start_date: startDate.toISOString(),
                end_date: endDate.toISOString(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                ...(selectedAppId !== "all" && { app_id: selectedAppId }),
            });

            // Fetch aggregated data (always use daily grouping)
            const aggregateParams = new URLSearchParams({
                mode: "aggregate",
                start_date: startDate.toISOString(),
                end_date: endDate.toISOString(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                group_by: "day",
                ...(selectedAppId !== "all" && { app_id: selectedAppId }),
            });

            const [listResponse, aggregateResponse] = await Promise.all([
                axios.get<InstallationListResponse>(
                    route("api.installations.index") + `?${listParams}`,
                ),
                axios.get<InstallationAggregateResponse>(
                    route("api.installations.index") + `?${aggregateParams}`,
                ),
            ]);

            setInstallations(listResponse.data.installations);
            setTotalCount(listResponse.data.meta.total_count);
            setGrowth(
                listResponse.data.growth || {
                    installations_growth: 0,
                    active_users_growth: 0,
                    duration_days: 0,
                },
            );
            setAggregations(aggregateResponse.data.aggregations);
        } catch (error: unknown) {
            const message =
                (error as { response?: { data?: { message?: string } } })
                    ?.response?.data?.message || "Failed to fetch statistics";
            setError(message);
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchApps();
    }, []);

    useEffect(() => {
        fetchStats();
    }, [selectedAppId, dateRange]);

    const getUniqueApps = () => {
        const uniqueApps = new Map<number, string>();
        installations.forEach((installation) => {
            uniqueApps.set(installation.app_id, installation.app.name);
        });
        return uniqueApps.size;
    };

    const getActiveInstallations = () => {
        const now = new Date();
        const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);
        return installations.filter(
            (installation) => new Date(installation.last_seen_at) > oneDayAgo,
        ).length;
    };

    // Prepare chart data for the chart component
    const getChartData = (): ChartDataPoint[] => {
        if (selectedAppId !== "all") {
            return aggregations.map((data) => ({
                date: data.period,
                [`app_${selectedAppId}`]: data.total_count,
            }));
        }

        // Multi-app chart data - ensure consistent data points for all apps
        const allAppIds = Array.from(
            new Set(
                aggregations.flatMap((data) =>
                    data.apps.map((app) => app.app_id),
                ),
            ),
        );

        const chartData: ChartDataPoint[] = aggregations.map((data) => {
            const point: ChartDataPoint = {
                date: data.period,
                total: data.total_count,
            };

            // Initialize all app counts to 0
            allAppIds.forEach((appId) => {
                point[`app_${appId}`] = 0;
            });

            // Set actual counts
            data.apps.forEach((app) => {
                point[`app_${app.app_id}`] = app.count;
            });

            return point;
        });

        return chartData;
    };

    if (error) {
        return (
            <Alert variant="destructive" className="mx-auto max-w-2xl">
                <AlertDescription className="flex items-center justify-between">
                    <span>{error}</span>
                    <Button onClick={fetchStats} variant="outline" size="sm">
                        Retry
                    </Button>
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="flex-1 space-y-6 p-8 pt-6">
            <DashboardHeader
                key={`header-${selectedAppId}-${dateRange}`}
                apps={apps}
                selectedAppId={selectedAppId}
                onAppChange={setSelectedAppId}
                dateRange={dateRange}
                onDateRangeChange={setDateRange}
                onAppCreated={fetchApps}
            />

            <DashboardStats
                key={`${selectedAppId}-${dateRange}`}
                apps={apps}
                selectedAppId={selectedAppId}
                dateRange={dateRange}
                totalInstallations={totalCount}
                activeInstallations={getActiveInstallations()}
                totalApps={selectedAppId !== "all" ? 1 : getUniqueApps()}
                growth={growth}
                loading={loading}
            />

            <DashboardChart
                key={`chart-${selectedAppId}-${dateRange}`}
                apps={apps}
                selectedAppId={selectedAppId}
                dateRange={dateRange}
                chartData={getChartData()}
                loading={loading}
            />
        </div>
    );
}
