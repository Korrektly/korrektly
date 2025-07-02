import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { router } from "@inertiajs/react";
import { type PropsWithChildren } from "react";

import AppHeader from "@/components/apps/app-header";
import type { App } from "@/types/apps";

interface AppDetailsLayoutProps extends PropsWithChildren {
    app: App;
    dateRange?: string;
    onDateRangeChange?: (range: string) => void;
    activeTab: string;
}

const getTabHref = (appId: number, tab: string) => {
    switch (tab) {
        case "overview":
            return `/apps/${appId}`;
        case "installations":
            return `/apps/${appId}/installations`;
        case "integration":
            return `/apps/${appId}/integration`;
        default:
            return `/apps/${appId}`;
    }
};

export default function AppDetailsLayout({ app, dateRange, onDateRangeChange, activeTab, children }: AppDetailsLayoutProps) {
    const handleTabChange = (value: string) => {
        const href = getTabHref(app.id, value);
        router.visit(href);
    };

    return (
        <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-8 py-6">
            {/* App Header */}
            <AppHeader app={app} dateRange={dateRange} onDateRangeChange={onDateRangeChange} />

            {/* Tabs */}
            <Tabs value={activeTab} onValueChange={handleTabChange}>
                <TabsList className="grid w-full grid-cols-3">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="installations">Installations</TabsTrigger>
                    <TabsTrigger value="integration">Integration</TabsTrigger>
                </TabsList>
            </Tabs>

            {/* Content */}
            <div className="mt-6">{children}</div>
        </div>
    );
}
