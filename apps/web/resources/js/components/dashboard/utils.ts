/**
 * Generate a consistent color for an app based on its name and URL
 */
export function generateAppColor(name: string, url?: string | null): string {
    const str = `${name}-${url || ""}`;
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }

    // Generate HSL color for better consistency
    const hue = Math.abs(hash) % 360;
    const saturation = 65 + (Math.abs(hash) % 20); // 65-85%
    const lightness = 45 + (Math.abs(hash) % 15); // 45-60%

    return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
}

/**
 * Format date range text for display
 */
export function formatDateRangeText(dateRange: string): string {
    switch (dateRange) {
        case "today":
            return "today";
        case "7d":
            return "last 7 days";
        case "30d":
            return "last 30 days";
        case "90d":
            return "last 3 months";
        case "year":
            return "last year";
        default:
            return dateRange;
    }
}

/**
 * Get the selected app name or "All Apps"
 */
export function getSelectedAppName(
    apps: Array<{ id: number; name: string }>,
    selectedAppId: string,
): string {
    if (selectedAppId === "all") {
        return "All Apps";
    }

    const app = apps.find((app) => app.id.toString() === selectedAppId);
    return app?.name || "Unknown App";
}
