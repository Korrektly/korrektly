import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import type { App } from "@/types/apps";
import { ExternalLink } from "lucide-react";

interface AppHeaderProps {
    app: App;
    dateRange?: string;
    onDateRangeChange?: (value: string) => void;
}

export default function AppHeader({ app, dateRange, onDateRangeChange }: AppHeaderProps) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString(undefined, {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            timeZoneName: "short",
        });
    };

    return (
        <div className="flex items-center justify-between">
            <div className="space-y-1">
                <div className="flex items-center gap-3">
                    <h1 className="text-3xl font-bold tracking-tight">{app.name}</h1>
                    {app.url && (
                        <Button variant="outline" size="sm" asChild>
                            <a href={app.url} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="size-4" />
                            </a>
                        </Button>
                    )}
                </div>
                <p className="text-muted-foreground">Created {formatDate(app.created_at)}</p>
            </div>

            {/* Date Range Filter - conditionally rendered */}
            {dateRange && onDateRangeChange && (
                <Select value={dateRange} onValueChange={onDateRangeChange}>
                    <SelectTrigger className="w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="today">Today</SelectItem>
                        <SelectItem value="7d">Last 7 days</SelectItem>
                        <SelectItem value="30d">Last 30 days</SelectItem>
                        <SelectItem value="90d">Last 90 days</SelectItem>
                        <SelectItem value="year">Last year</SelectItem>
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}
