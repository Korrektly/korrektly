import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import AppDetailsLayout from "@/layouts/app-details-layout";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import type { App } from "@/types/apps";
import { Head } from "@inertiajs/react";
import { Copy } from "lucide-react";
import { toast } from "sonner";

interface AppIntegrationProps {
    app: App;
}

export default function AppIntegration({ app }: AppIntegrationProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Apps", href: "/dashboard" },
        { title: app.name, href: `/apps/${app.id}` },
        { title: "Integration", href: `/apps/${app.id}/integration` },
    ];

    const copyToClipboard = async (text: string) => {
        try {
            await navigator.clipboard.writeText(text);
            toast.success("Copied to clipboard!");
        } catch {
            toast.error("Failed to copy to clipboard");
        }
    };

    const exampleApiRequest = `curl -X POST "${window.location.origin}/api/installations" \\
  -H "Content-Type: application/json" \\
  -d '{
    "app_id": "${app.id}",
    "user_identifier": "user123",
    "platform": "web",
    "version": "1.0.0"
  }'`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${app.name} - Integration`} />
            <AppDetailsLayout app={app} activeTab="integration">
                <div className="space-y-6">
                    {/* App ID Section */}
                    <Card className="p-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold">App Configuration</h3>
                            </div>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">App ID</label>
                                        <div className="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" className="font-mono text-sm px-3 py-1">
                                                {app.id}
                                            </Badge>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => copyToClipboard(app.id.toString())}
                                                className="h-8 w-8 p-0"
                                            >
                                                <Copy className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">App Name</label>
                                        <div className="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" className="text-sm px-3 py-1">
                                                {app.name}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* API Documentation Section */}
                    <Card className="p-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold">API Integration</h3>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground mb-2 block">Example API Request</label>
                                    <div className="relative">
                                        <pre className="bg-muted p-4 rounded-md text-sm overflow-x-auto font-mono">
                                            <code>{exampleApiRequest}</code>
                                        </pre>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => copyToClipboard(exampleApiRequest)}
                                            className="absolute top-2 right-2 h-8 w-8 p-0"
                                        >
                                            <Copy className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    <p>
                                        Use this API endpoint to track installations for your app. Replace the placeholder values with your actual
                                        data.
                                    </p>
                                    <ul className="mt-2 space-y-1 list-disc list-inside">
                                        <li>
                                            <code className="text-xs bg-muted px-1 py-0.5 rounded">app_id</code>: Your app's unique identifier
                                        </li>
                                        <li>
                                            <code className="text-xs bg-muted px-1 py-0.5 rounded">user_identifier</code>: Unique identifier for the
                                            user
                                        </li>
                                        <li>
                                            <code className="text-xs bg-muted px-1 py-0.5 rounded">platform</code>: Platform where the app is
                                            installed (web, mobile, desktop)
                                        </li>
                                        <li>
                                            <code className="text-xs bg-muted px-1 py-0.5 rounded">version</code>: Version of your app
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </AppDetailsLayout>
        </AppLayout>
    );
}
