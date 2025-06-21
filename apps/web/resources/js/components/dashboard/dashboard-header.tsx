import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import type { App } from "@/types/apps";
import { generateAppColor } from "./utils";
import { useState } from "react";
import CreateAppModal from "../apps/create-app-modal";

interface DashboardHeaderProps {
    apps: App[];
    selectedAppId: string;
    onAppChange: (appId: string) => void;
    dateRange: string;
    onDateRangeChange: (range: string) => void;
    onAppCreated?: () => void;
}

export default function DashboardHeader({
    apps,
    selectedAppId,
    onAppChange,
    dateRange,
    onDateRangeChange,
    onAppCreated,
}: DashboardHeaderProps) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    const handleAppCreated = () => {
        setIsCreateModalOpen(false);
        onAppCreated?.();
    };

    return (
        <div className="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div className="space-y-1">
                <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">
                    Overview
                </h2>
                <p className="text-sm text-muted-foreground sm:text-base">
                    Track installations and usage patterns across your
                    applications
                </p>
            </div>

            <div className="flex flex-col space-y-2 md:flex-row md:items-center md:space-x-2 md:space-y-0">
                <Button
                    onClick={() => setIsCreateModalOpen(true)}
                    size="sm"
                    className="gap-2 w-full md:w-auto"
                >
                    <Plus className="h-4 w-4" />
                    Create App
                </Button>

                <div className="flex space-x-2">
                    <Select value={selectedAppId} onValueChange={onAppChange}>
                        <SelectTrigger className="flex-1 sm:w-[180px]">
                            <SelectValue placeholder="Select app">
                                {selectedAppId === "all" ? (
                                    "All Apps"
                                ) : (
                                    <div className="flex items-center gap-2 truncate">
                                        <div
                                            className="h-3 w-3 rounded-full border border-border flex-shrink-0"
                                            style={{
                                                backgroundColor:
                                                    generateAppColor(
                                                        apps.find(
                                                            (app) =>
                                                                app.id.toString() ===
                                                                selectedAppId,
                                                        )?.name || "",
                                                        apps.find(
                                                            (app) =>
                                                                app.id.toString() ===
                                                                selectedAppId,
                                                        )?.url,
                                                    ),
                                            }}
                                        />
                                        <span className="truncate">
                                            {apps.find(
                                                (app) =>
                                                    app.id.toString() ===
                                                    selectedAppId,
                                            )?.name || "Unknown App"}
                                        </span>
                                    </div>
                                )}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Apps</SelectItem>
                            {apps.map((app) => (
                                <SelectItem
                                    key={app.id}
                                    value={app.id.toString()}
                                >
                                    <div className="flex items-center gap-2 truncate">
                                        <div
                                            className="h-3 w-3 rounded-full border border-border flex-shrink-0"
                                            style={{
                                                backgroundColor:
                                                    generateAppColor(
                                                        app.name,
                                                        app.url,
                                                    ),
                                            }}
                                        />
                                        <span className="truncate">
                                            {app.name}
                                        </span>
                                    </div>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={dateRange} onValueChange={onDateRangeChange}>
                        <SelectTrigger className="flex-1 sm:w-[160px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="today">Today</SelectItem>
                            <SelectItem value="7d">Last 7 days</SelectItem>
                            <SelectItem value="30d">Last 30 days</SelectItem>
                            <SelectItem value="90d">Last 3 months</SelectItem>
                            <SelectItem value="year">Last year</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <CreateAppModal
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
                onAppCreated={handleAppCreated}
            />
        </div>
    );
}
