import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import AppDetailsLayout from "@/layouts/app-details-layout";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import type { AggregationData, App, GrowthMetrics, Installation, InstallationAggregateResponse, InstallationListResponse } from "@/types/apps";
import { Head } from "@inertiajs/react";
import axios from "axios";
import { useEffect, useState } from "react";
import { toast } from "sonner";

import AppChart from "@/components/apps/app-chart";
import AppStats from "@/components/apps/app-stats";

interface AppOverviewProps {
    app: App;
}

export default function AppOverview({ app }: AppOverviewProps) {
    const [installations, setInstallations] = useState<Installation[]>([]);
    const [aggregations, setAggregations] = useState<AggregationData[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [dateRange, setDateRange] = useState("7d");
    const [totalCount, setTotalCount] = useState(0);
    const [growth, setGrowth] = useState<GrowthMetrics>({
        installations_growth: 0,
        active_users_growth: 0,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Apps", href: "/dashboard" },
        { title: app.name, href: `/apps/${app.id}` },
    ];

    const getDateRangeValues = () => {
        const endDate = new Date();
        const startDate = new Date();

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

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);

            const { startDate, endDate } = getDateRangeValues();

            const listParams = new URLSearchParams({
                mode: "list",
                app_id: app.id.toString(),
                start_date: startDate.toISOString(),
                end_date: endDate.toISOString(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            });

            const aggregateParams = new URLSearchParams({
                mode: "aggregate",
                app_id: app.id.toString(),
                start_date: startDate.toISOString(),
                end_date: endDate.toISOString(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                group_by: "day",
            });

            const [listResponse, aggregateResponse] = await Promise.all([
                axios.get<InstallationListResponse>(route("api.installations.index") + `?${listParams}`),
                axios.get<InstallationAggregateResponse>(route("api.installations.index") + `?${aggregateParams}`),
            ]);

            setInstallations(listResponse.data.installations);
            setTotalCount(listResponse.data.meta.total_count);
            setGrowth(
                listResponse.data.growth || {
                    installations_growth: 0,
                    active_users_growth: 0,
                },
            );
            setAggregations(aggregateResponse.data.aggregations);
        } catch (error: unknown) {
            const message = (error as { response?: { data?: { message?: string } } })?.response?.data?.message || "Failed to fetch app data";
            setError(message);
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [dateRange]);

    const getActiveInstallations = () => {
        const now = new Date();
        const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);
        return installations.filter((installation) => new Date(installation.last_seen_at) > oneDayAgo).length;
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={`${app.name} - Overview`} />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <Alert variant="destructive" className="mx-auto max-w-2xl">
                        <AlertDescription className="flex items-center justify-between">
                            <span>{error}</span>
                            <Button onClick={fetchData} variant="outline" size="sm">
                                Retry
                            </Button>
                        </AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${app.name} - Overview`} />
            <AppDetailsLayout app={app} dateRange={dateRange} onDateRangeChange={setDateRange} activeTab="overview">
                <div className="space-y-6">
                    {/* App Statistics */}
                    <AppStats
                        app={app}
                        dateRange={dateRange}
                        totalInstallations={totalCount}
                        activeInstallations={getActiveInstallations()}
                        growth={growth}
                        loading={loading}
                    />

                    {/* App Chart */}
                    <AppChart app={app} dateRange={dateRange} aggregations={aggregations} loading={loading} />
                </div>
            </AppDetailsLayout>
        </AppLayout>
    );
}
