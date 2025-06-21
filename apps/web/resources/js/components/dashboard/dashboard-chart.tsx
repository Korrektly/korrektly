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
import {
    generateAppColor,
    formatDateRangeText,
    getSelectedAppName,
} from "./utils";
import type { App } from "@/types/apps";

interface ChartDataPoint {
    date: string;
    [key: string]: string | number;
}

interface DashboardChartProps {
    apps: App[];
    selectedAppId: string;
    dateRange: string;
    chartData: ChartDataPoint[];
    loading: boolean;
}

export default function DashboardChart({
    apps,
    selectedAppId,
    dateRange,
    chartData,
    loading,
}: DashboardChartProps) {
    const appName = getSelectedAppName(apps, selectedAppId);
    const periodText = formatDateRangeText(dateRange);

    const isMultipleApps = selectedAppId === "all";
    const hasData = chartData.length > 0;

    // Create chart config dynamically based on apps
    const chartConfig: ChartConfig = {};

    if (isMultipleApps) {
        apps.forEach((app) => {
            chartConfig[`app_${app.id}`] = {
                label: app.name,
                color: generateAppColor(app.name, app.url),
            };
        });
    } else {
        const selectedApp = apps.find(
            (app) => app.id.toString() === selectedAppId,
        );
        if (selectedApp) {
            chartConfig[`app_${selectedApp.id}`] = {
                label: selectedApp.name,
                color: generateAppColor(selectedApp.name, selectedApp.url),
            };
        }
    }

    const dataKeys = Object.keys(chartConfig);

    // Generate dummy data for empty state
    const dummyData = Array.from({ length: 7 }, (_, i) => {
        const date = new Date();
        date.setDate(date.getDate() - (6 - i));
        const point: ChartDataPoint = {
            date: date.toISOString().split("T")[0],
        };

        if (dataKeys.length > 0) {
            dataKeys.forEach((key) => {
                point[key] = Math.floor(Math.random() * 50) + 10;
            });
        } else {
            point["dummy"] = Math.floor(Math.random() * 50) + 10;
        }

        return point;
    });

    // Use dummy config if no real data
    const displayConfig =
        dataKeys.length > 0
            ? chartConfig
            : {
                  dummy: {
                      label: "Installations",
                      color: "#8884d8",
                  },
              };

    const displayDataKeys = dataKeys.length > 0 ? dataKeys : ["dummy"];
    const displayData = hasData ? chartData : dummyData;

    if (loading) {
        return (
            <Card>
                <CardHeader>
                    <div className="h-6 w-48 animate-pulse rounded bg-muted" />
                    <div className="h-4 w-64 animate-pulse rounded bg-muted" />
                </CardHeader>
                <CardContent className="pt-6">
                    <div className="h-[280px] animate-pulse rounded bg-muted" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Installation Trends</CardTitle>
                <CardDescription>
                    {appName} • {periodText}
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <div className="relative">
                    <ChartContainer
                        config={displayConfig}
                        className={`h-[380px] w-full ${!hasData ? "opacity-30" : ""}`}
                    >
                        <AreaChart
                            accessibilityLayer
                            data={displayData}
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
                                tickFormatter={(value) => value.slice(5)}
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

                            {/* Generate gradient definitions */}
                            <defs>
                                {isMultipleApps || !hasData ? (
                                    displayDataKeys.map((dataKey) => (
                                        <linearGradient
                                            key={dataKey}
                                            id={`gradient-${dataKey}`}
                                            x1="0"
                                            y1="0"
                                            x2="0"
                                            y2="1"
                                        >
                                            <stop
                                                offset="5%"
                                                stopColor={
                                                    displayConfig[dataKey]
                                                        ?.color
                                                }
                                                stopOpacity={0.4}
                                            />
                                            <stop
                                                offset="95%"
                                                stopColor={
                                                    displayConfig[dataKey]
                                                        ?.color
                                                }
                                                stopOpacity={0.1}
                                            />
                                        </linearGradient>
                                    ))
                                ) : (
                                    <linearGradient
                                        id="gradient-single"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor={
                                                displayDataKeys[0]
                                                    ? displayConfig[
                                                          displayDataKeys[0]
                                                      ]?.color
                                                    : "#8884d8"
                                            }
                                            stopOpacity={0.3}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor={
                                                displayDataKeys[0]
                                                    ? displayConfig[
                                                          displayDataKeys[0]
                                                      ]?.color
                                                    : "#8884d8"
                                            }
                                            stopOpacity={0.05}
                                        />
                                    </linearGradient>
                                )}
                            </defs>

                            {displayDataKeys.map((dataKey) => (
                                <Area
                                    key={dataKey}
                                    dataKey={dataKey}
                                    type="natural"
                                    fill={
                                        isMultipleApps || !hasData
                                            ? `url(#gradient-${dataKey})`
                                            : "url(#gradient-single)"
                                    }
                                    fillOpacity={1}
                                    stroke={displayConfig[dataKey]?.color}
                                    strokeWidth={2}
                                    stackId="1"
                                />
                            ))}
                        </AreaChart>
                    </ChartContainer>

                    {!hasData && (
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center space-y-2">
                                <h3 className="text-lg font-semibold text-muted-foreground">
                                    No Installation Data
                                </h3>
                                <p className="text-sm text-muted-foreground max-w-sm">
                                    {apps.length === 0
                                        ? "Add an app to your workspace to start tracking installations"
                                        : "Install or configure your app to see trends and analytics here"}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
