import { Activity, Download, Smartphone, TrendingUp, Package } from "lucide-react";
import StatCard from "../stat-card";
import { formatDateRangeText, getSelectedAppName } from "./utils";
import type { App, GrowthMetrics } from "@/types/apps";

interface DashboardStatsProps {
    apps: App[];
    selectedAppId: string;
    dateRange: string;
    totalInstallations: number;
    activeInstallations: number;
    totalApps: number;
    growth: GrowthMetrics;
    loading: boolean;
}

export default function DashboardStats({
    apps,
    selectedAppId,
    dateRange,
    totalInstallations,
    activeInstallations,
    totalApps,
    growth,
    loading,
}: DashboardStatsProps) {
    const appName = getSelectedAppName(apps, selectedAppId);
    const periodText = formatDateRangeText(dateRange);

    const isAllApps = selectedAppId === "all";

    if (loading) {
        return (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="h-32 animate-pulse rounded-lg bg-muted" />
                ))}
            </div>
        );
    }

    // Determine which growth metric to show in the growth card
    const getGrowthValue = () => {
        if (growth.installations_growth !== 0) {
            return growth.installations_growth;
        }
        return growth.active_users_growth;
    };

    const getGrowthDescription = () => {
        const durationText = `vs previous ${periodText}`;

        if (growth.trend) {
            return `${appName} • ${durationText} • ${growth.trend}`;
        }
        return `${appName} • ${durationText}`;
    };

    const growthValue = getGrowthValue();

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
                title={isAllApps ? "Total Apps" : "Most Adopted Version"}
                value={isAllApps ? totalApps.toLocaleString() : growth.most_adopted_version?.version || "Unknown"}
                description={
                    isAllApps
                        ? undefined
                        : growth.most_adopted_version
                          ? `${growth.most_adopted_version.count} installations (${growth.most_adopted_version.percentage}%)`
                          : `${appName} • no version data`
                }
                icon={isAllApps ? Smartphone : Package}
            />

            <StatCard
                title="Total Installations"
                value={totalInstallations.toLocaleString()}
                description={`${appName} • ${periodText}`}
                icon={Download}
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
                description={`${appName} • ${periodText}`}
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
                title="Overall Growth"
                value={`${growthValue > 0 ? "+" : ""}${growthValue.toFixed(1)}%`}
                description={getGrowthDescription()}
                icon={TrendingUp}
                trend={{
                    value: growthValue,
                    isPositive: growthValue >= 0,
                }}
            />
        </div>
    );
}
