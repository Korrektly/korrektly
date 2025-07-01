import { router } from "@inertiajs/react";
import { useEffect } from "react";

/**
 * Legacy workspace settings page that redirects to the new overview page.
 * The workspace settings have been split into Overview and Members pages.
 */
export default function WorkspaceSettingsLegacy() {
    useEffect(() => {
        router.visit(route("settings.workspace"));
    }, []);

    return null;
}
