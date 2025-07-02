import { NavFooter } from "@/components/nav-footer";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from "@/components/ui/sidebar";
import { type NavItem, SharedData } from "@/types";
import { Link, usePage } from "@inertiajs/react";
import { BookOpen, Folder, LayoutGrid, Settings } from "lucide-react";
import AppLogo from "./app-logo";
import { NavApps } from "./nav-apps";
import { WorkspaceSelector } from "./workspace-selector";

const mainNavItems: NavItem[] = [
    {
        title: "Dashboard",
        href: "/dashboard",
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: "Workspace Settings",
        href: "/settings/workspace",
        icon: Settings,
    },
    {
        title: "Repository",
        href: "https://github.com/laravel/react-starter-kit",
        icon: Folder,
    },
    {
        title: "Documentation",
        href: "https://laravel.com/docs/starter-kits#react",
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { workspaces } = usePage<SharedData>().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                {workspaces.enabled && <WorkspaceSelector workspaces={workspaces} />}
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />

                <NavApps />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
