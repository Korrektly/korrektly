import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from "@/components/ui/sidebar";
import { SharedData } from "@/types";
import { Link, usePage } from "@inertiajs/react";
import { PlusIcon } from "lucide-react";
import { useState } from "react";
import CreateAppModal from "./apps/create-app-modal";
import { Button } from "./ui/button";

export function NavApps() {
    const { apps } = usePage<SharedData>().props;
    const sidebar = useSidebar();
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="flex items-center justify-between">
                <span>Applications</span>

                <Button variant="ghost" size="icon" className="size-4 hidden md:block" onClick={() => setCreateOpen(true)}>
                    <PlusIcon />
                </Button>
            </SidebarGroupLabel>
            <SidebarMenu>
                {apps.length > 0 ? (
                    apps.map((app) => (
                        <SidebarMenuItem key={app.id}>
                            <SidebarMenuButton tooltip={app.name} href={route("apps.show", app.id)}>
                                <Avatar className="size-4">
                                    <AvatarImage src={`https://www.google.com/s2/favicons?domain=${app.url}&sz=32`} />
                                    <AvatarFallback>{app.name}</AvatarFallback>
                                </Avatar>
                                {sidebar.open && <span className="truncate">{app.name}</span>}
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))
                ) : (
                    <SidebarMenuItem>
                        <span className="text-muted-foreground">No apps found</span>
                    </SidebarMenuItem>
                )}
            </SidebarMenu>
            <CreateAppModal
                open={createOpen}
                onOpenChange={setCreateOpen}
                onAppCreated={() => {
                    setCreateOpen(false);
                }}
            />
        </SidebarGroup>
    );
}
