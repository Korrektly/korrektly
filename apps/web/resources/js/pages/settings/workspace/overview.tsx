import { Head, useForm } from "@inertiajs/react";
import { toast } from "sonner";

import HeadingSmall from "@/components/heading-small";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import WorkspaceSettingsLayout from "@/layouts/settings/workspace-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Workspace settings",
        href: "/settings/workspace",
    },
];

interface Workspace {
    id: string;
    name: string;
    slug: string;
    logo: string;
    owner_id: string;
}

interface Props {
    workspace: Workspace;
    canManage: boolean;
}

export default function WorkspaceOverview({ workspace, canManage }: Props) {
    // Workspace settings form
    const workspaceForm = useForm({
        name: workspace.name,
    });

    const handleWorkspaceUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        workspaceForm.patch(route("settings.workspace.update"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Workspace updated successfully");
            },
            onError: () => {
                toast.error("Failed to update workspace");
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workspace settings" />

            <WorkspaceSettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Workspace information" description="Update your workspace name and settings" />

                    <form onSubmit={handleWorkspaceUpdate} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Workspace Name</Label>
                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={workspaceForm.data.name}
                                onChange={(e) => workspaceForm.setData("name", e.target.value)}
                                disabled={!canManage || workspaceForm.processing}
                                required
                                autoComplete="organization"
                                placeholder="Workspace name"
                            />
                            <InputError className="mt-2" message={workspaceForm.errors.name} />
                        </div>

                        {canManage && (
                            <div className="flex items-center gap-4">
                                <Button disabled={workspaceForm.processing}>{workspaceForm.processing ? "Saving..." : "Save"}</Button>

                                {workspaceForm.recentlySuccessful && <p className="text-sm text-neutral-600">Saved</p>}
                            </div>
                        )}
                    </form>
                </div>
            </WorkspaceSettingsLayout>
        </AppLayout>
    );
}
