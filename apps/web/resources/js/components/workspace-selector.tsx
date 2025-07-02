import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from "@/components/ui/sidebar";
import { SharedData } from "@/types";
import { router } from "@inertiajs/react";
import { CheckIcon, ChevronsUpDown, Loader2, Users } from "lucide-react";
import { useState } from "react";

export function WorkspaceSelector({
    workspaces,
}: {
    workspaces: SharedData["workspaces"];
}) {
    const { state } = useSidebar();
    const [isLoading, setIsLoading] = useState(false);

    const hasWorkspaces = workspaces.all.length > 0;
    const currentWorkspace = workspaces.current;

    if (!hasWorkspaces) {
        return null;
    }

    const isCollapsed = state === "collapsed";

    const handleWorkspaceSwitch = (workspaceId: string) => {
        if (workspaceId === currentWorkspace?.id || isLoading) {
            return;
        }

        setIsLoading(true);

        router.post(
            "/workspace/switch",
            {
                workspace_id: workspaceId,
            },
            {
                preserveState: false,
                onFinish: () => {
                    setIsLoading(false);
                },
            },
        );
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton size="sm" className="ring-border bg-background rounded-lg ring-1 md:h-8" disabled={isLoading}>
                            <div className="flex aspect-square size-4 items-center justify-center rounded-lg">
                                {currentWorkspace?.logo ? (
                                    <img src={currentWorkspace.logo} alt={currentWorkspace.name} className="size-4 rounded-lg border-2" />
                                ) : (
                                    <Users className="size-4" />
                                )}
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{currentWorkspace?.name || "Select a workspace"}</span>
                                {isLoading && <span className="text-xs text-muted-foreground">Switching...</span>}
                            </div>
                            {isLoading ? <Loader2 className="ml-auto size-4 animate-spin" /> : <ChevronsUpDown className="ml-auto size-4" />}
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="max-h-48 w-[--radix-dropdown-menu-trigger-width] min-w-56 overflow-auto rounded-lg"
                        side={isCollapsed ? "right" : "bottom"}
                        align="start"
                        sideOffset={4}
                    >
                        {workspaces.all.map((workspace) => (
                            <DropdownMenuItem
                                key={workspace.id}
                                onClick={() => handleWorkspaceSwitch(workspace.id)}
                                disabled={isLoading || workspace.id === currentWorkspace?.id}
                                className="cursor-pointer"
                            >
                                <div className="flex w-full items-center justify-between">
                                    <span className="truncate">{workspace.name}</span>
                                    {currentWorkspace?.id === workspace.id ? (
                                        <CheckIcon className="h-4 w-4 text-green-600" />
                                    ) : isLoading ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : null}
                                </div>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
