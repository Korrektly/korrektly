import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from "recharts";
import { Skeleton } from "@/components/ui/skeleton";
import { generateAppColor, formatDateRangeText } from "../dashboard/utils";
import type { App, AggregationData } from "@/types/apps";

interface AppChartProps {
    app: App;
    dateRange: string;
    aggregations: AggregationData[];
    loading: boolean;
}

interface ChartDataPoint {
    date: string;
    installations: number;
    active_installations: number;
}

export default function AppChart({
    app,
    dateRange,
    aggregations,
    loading,
}: AppChartProps) {
    const periodText = formatDateRangeText(dateRange);

    // Transform aggregation data to chart format
    const chartData: ChartDataPoint[] = aggregations.map((data) => ({
        date: data.period,
        installations: data.total_count,
        active_installations: data.active_count,
    }));

    const hasData =
        chartData.length > 0 &&
        chartData.some(
            (d) => d.installations > 0 || d.active_installations > 0,
        );

    // Chart config for the app
    const chartConfig: ChartConfig = {
        installations: {
            label: "Total Installations",
            color: generateAppColor(app.name, app.url),
        },
        active_installations: {
            label: "Active Installations",
            color: generateAppColor(app.name + "_active", app.url),
        },
    };

    const formatDateShort = (dateString: string) => {
        return new Date(dateString).toLocaleDateString(undefined, {
            month: "short",
            day: "numeric",
        });
    };

    if (loading) {
        return (
            <Card>
                <CardHeader>
                    <div className="h-6 w-48 animate-pulse rounded bg-muted" />
                    <div className="h-4 w-64 animate-pulse rounded bg-muted" />
                </CardHeader>
                <CardContent className="pt-6">
                    <div className="h-[380px] animate-pulse rounded bg-muted" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Installation Trends</CardTitle>
                <CardDescription>
                    {app.name} • {periodText}
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <div className="relative">
                    <ChartContainer
                        config={chartConfig}
                        className={`h-[380px] w-full ${!hasData ? "opacity-30" : ""}`}
                    >
                        <AreaChart
                            accessibilityLayer
                            data={chartData}
                            margin={{
                                left: 12,
                                right: 12,
                                top: 20,
                                bottom: 12,
                            }}
                        >
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="date"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                tickFormatter={formatDateShort}
                            />
                            <YAxis
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                domain={["auto", "auto"]}
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent indicator="dot" />
                                }
                            />

                            <defs>
                                <linearGradient
                                    id="fillInstallations"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor={
                                            chartConfig.installations.color
                                        }
                                        stopOpacity={0.8}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor={
                                            chartConfig.installations.color
                                        }
                                        stopOpacity={0.1}
                                    />
                                </linearGradient>
                                <linearGradient
                                    id="fillActiveInstallations"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor={
                                            chartConfig.active_installations
                                                .color
                                        }
                                        stopOpacity={0.8}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor={
                                            chartConfig.active_installations
                                                .color
                                        }
                                        stopOpacity={0.1}
                                    />
                                </linearGradient>
                            </defs>

                            <Area
                                dataKey="installations"
                                type="natural"
                                fill="url(#fillInstallations)"
                                fillOpacity={0.4}
                                stroke={chartConfig.installations.color}
                                strokeWidth={2}
                                stackId="a"
                            />
                            <Area
                                dataKey="active_installations"
                                type="natural"
                                fill="url(#fillActiveInstallations)"
                                fillOpacity={0.4}
                                stroke={chartConfig.active_installations.color}
                                strokeWidth={2}
                                stackId="b"
                            />
                        </AreaChart>
                    </ChartContainer>

                    {!hasData && (
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center space-y-2">
                                <h3 className="text-lg font-semibold text-muted-foreground">
                                    No Installation Data
                                </h3>
                                <p className="text-sm text-muted-foreground max-w-sm">
                                    Install or configure your app to see trends
                                    and analytics here
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
