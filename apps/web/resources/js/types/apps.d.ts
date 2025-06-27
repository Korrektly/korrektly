export interface App {
    id: number;
    name: string;
    url?: string;
    workspace_id: number;
    created_at: string;
    updated_at: string;
}

export interface Installation {
    id: number;
    app_id: number;
    identifier: string;
    version?: string;
    last_seen_at: string;
    created_at: string;
    updated_at: string;
    app: App;
}

export interface AggregationData {
    period: string;
    total_count: number;
    active_count: number;
    apps: Array<{
        app_id: number;
        app_name: string;
        count: number;
    }>;
}

export interface GrowthMetrics {
    installations_growth: number;
    active_users_growth: number;
    most_adopted_version?: {
        version: string;
        count: number;
        percentage: number;
    };
    current_period?: {
        installations: number;
        active_users: number;
    };
    previous_period?: {
        installations: number;
        active_users: number;
    };
    period_over_period_growth?: number;
    total_periods?: number;
    trend?: "increasing" | "decreasing" | "stable";
}

export interface InstallationListResponse {
    installations: Installation[];
    meta: {
        total_count: number;
        timezone: string;
    };
    growth: GrowthMetrics;
}

export interface InstallationAggregateResponse {
    aggregations: AggregationData[];
    meta: {
        start_date: string;
        end_date: string;
        timezone: string;
        group_by: string;
        total_periods: number;
        duration_days: number;
    };
    growth: GrowthMetrics;
}
