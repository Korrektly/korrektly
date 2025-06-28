import { Calendar, Users, Activity, TrendingUp } from "lucide-react";
import StatCard from "../stat-card";
import { formatDateRangeText } from "../dashboard/utils";
import type { App, GrowthMetrics } from "@/types/apps";

interface AppStatsProps {
    app: App;
    dateRange: string;
    totalInstallations: number;
    activeInstallations: number;
    growth: GrowthMetrics;
    loading: boolean;
}

export default function AppStats({ app, dateRange, totalInstallations, activeInstallations, growth, loading }: AppStatsProps) {
    const periodText = formatDateRangeText(dateRange);

    if (loading) {
        return (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="h-32 animate-pulse rounded-lg bg-muted" />
                ))}
            </div>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
                title="Total Installations"
                value={totalInstallations.toLocaleString()}
                description={`${app.name} • ${periodText}`}
                icon={Users}
                trend={
                    growth.installations_growth !== 0
                        ? {
                              value: growth.installations_growth,
                              isPositive: growth.installations_growth >= 0,
                          }
                        : undefined
                }
            />

            <StatCard
                title="Active Installations"
                value={activeInstallations.toLocaleString()}
                description={`${app.name} • ${periodText}`}
                icon={Activity}
                trend={
                    growth.active_users_growth !== 0
                        ? {
                              value: growth.active_users_growth,
                              isPositive: growth.active_users_growth >= 0,
                          }
                        : undefined
                }
            />

            <StatCard
                title="Growth Trend"
                value={growth.trend ? growth.trend.charAt(0).toUpperCase() + growth.trend.slice(1) : "Stable"}
                description={`Period over period: ${growth.period_over_period_growth || 0}%`}
                icon={TrendingUp}
                trend={
                    growth.period_over_period_growth !== undefined && growth.period_over_period_growth !== 0
                        ? {
                              value: growth.period_over_period_growth,
                              isPositive: growth.period_over_period_growth >= 0,
                          }
                        : undefined
                }
            />

            <StatCard
                title="Most Used Version"
                value={growth.most_adopted_version?.version || "Unknown"}
                description={
                    growth.most_adopted_version
                        ? `${growth.most_adopted_version.count} installations (${growth.most_adopted_version.percentage}%)`
                        : `${app.name} • no version data`
                }
                icon={Calendar}
            />
        </div>
    );
}
