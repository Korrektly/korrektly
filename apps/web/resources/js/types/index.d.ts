import { LucideIcon } from "lucide-react";
import type { Config } from "ziggy-js";

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Workspace {
    id: string;
    name: string;
    slug: string;
    logo: string;
    owner_id: string;
}

export interface App {
    id: number;
    name: string;
    url: string | null;
    created_at: string;
    updated_at: string;
}

export interface Installation {
    id: number;
    app_id: number;
    identifier: string;
    last_seen_at: string;
    created_at: string;
    updated_at: string;
    app?: App;
}

export interface InstallationAggregation {
    period: string;
    total_count: number;
    apps: {
        app_id: number;
        app_name: string | null;
        count: number;
    }[];
}

export interface InstallationQueryParams {
    mode?: "list" | "show" | "aggregate";
    app_id?: number;
    installation_id?: number;
    start_date?: string;
    end_date?: string;
    timezone?: string;
    group_by?: "hour" | "day" | "week" | "month";
}

export interface InstallationResponse {
    installations?: Installation[];
    installation?: Installation;
    aggregations?: InstallationAggregation[];
    meta?: {
        total_count?: number;
        start_date?: string;
        end_date?: string;
        timezone?: string;
        group_by?: string;
        total_periods?: number;
    };
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    workspaces: {
        enabled: boolean;
        all: Workspace[];
        current: Workspace;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
