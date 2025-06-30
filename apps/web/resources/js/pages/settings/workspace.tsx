import { Head, router, useForm } from "@inertiajs/react";
import { Crown, Mail, MoreVertical, Shield, Trash2, User, UserPlus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import HeadingSmall from "@/components/heading-small";
import InputError from "@/components/input-error";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import AppLayout from "@/layouts/app-layout";
import SettingsLayout from "@/layouts/settings/layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Workspace settings",
        href: "/settings/workspace",
    },
];

interface User {
    id: string;
    user_id: string;
    name: string;
    email: string;
    avatar: string;
    role: string;
    created_at: string;
    is_owner: boolean;
}

interface Invitation {
    id: string;
    email: string;
    role: string;
    invited_by: string;
    expires_at: string;
    created_at: string;
}

interface Workspace {
    id: string;
    name: string;
    slug: string;
    logo: string;
    owner_id: string;
}

interface Props {
    workspace: Workspace;
    members: User[];
    pendingInvitations: Invitation[];
    currentUserRole: string;
    canManage: boolean;
    availableRoles: string[];
}

const getRoleIcon = (role: string) => {
    switch (role) {
        case "owner":
            return <Crown className="h-4 w-4 text-yellow-600" />;
        case "admin":
            return <Shield className="h-4 w-4 text-blue-600" />;
        default:
            return <User className="h-4 w-4 text-gray-600" />;
    }
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case "owner":
            return "default";
        case "admin":
            return "secondary";
        default:
            return "outline";
    }
};

export default function WorkspaceSettings({ workspace, members, pendingInvitations, canManage, availableRoles }: Props) {
    const [isInviteDialogOpen, setIsInviteDialogOpen] = useState(false);

    // Workspace settings form
    const workspaceForm = useForm({
        name: workspace.name,
    });

    // Invitation form
    const inviteForm = useForm({
        email: "",
        role: "member",
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

    const handleInviteUser = (e: React.FormEvent) => {
        e.preventDefault();
        inviteForm.post(route("settings.workspace.invite"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invitation sent successfully");
                inviteForm.reset();
                setIsInviteDialogOpen(false);
            },
            onError: () => {
                toast.error("Failed to send invitation");
            },
        });
    };

    const handleUpdateMemberRole = (memberId: string, newRole: string) => {
        router.patch(
            route("settings.workspace.members.update", { membership: memberId }),
            { role: newRole },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("Member role updated successfully");
                },
                onError: () => {
                    toast.error("Failed to update member role");
                },
            },
        );
    };

    const handleRemoveMember = (memberId: string, memberName: string) => {
        if (confirm(`Are you sure you want to remove ${memberName} from the workspace?`)) {
            router.delete(route("settings.workspace.members.remove", { membership: memberId }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("Member removed successfully");
                },
                onError: () => {
                    toast.error("Failed to remove member");
                },
            });
        }
    };

    const handleCancelInvitation = (invitationId: string) => {
        router.delete(route("settings.workspace.invitations.cancel", { invitation: invitationId }), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invitation cancelled successfully");
            },
            onError: () => {
                toast.error("Failed to cancel invitation");
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workspace settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    {/* Workspace Information */}
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

                    {/* Members Management */}
                    <div className="space-y-6">
                        <div className="flex items-center justify-between">
                            <HeadingSmall title="Members" description="Manage workspace members and their roles" />
                            {canManage && (
                                <Dialog open={isInviteDialogOpen} onOpenChange={setIsInviteDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button>
                                            <UserPlus className="h-4 w-4 mr-2" />
                                            Invite Member
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Invite a new member</DialogTitle>
                                            <DialogDescription>Send an invitation to join this workspace.</DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={handleInviteUser}>
                                            <div className="space-y-4 py-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="email">Email</Label>
                                                    <Input
                                                        id="email"
                                                        type="email"
                                                        value={inviteForm.data.email}
                                                        onChange={(e) => inviteForm.setData("email", e.target.value)}
                                                        placeholder="user@example.com"
                                                        required
                                                    />
                                                    <InputError message={inviteForm.errors.email} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="role">Role</Label>
                                                    <Select value={inviteForm.data.role} onValueChange={(value) => inviteForm.setData("role", value)}>
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {availableRoles.map((role) => (
                                                                <SelectItem key={role} value={role}>
                                                                    <div className="flex items-center gap-2">
                                                                        {getRoleIcon(role)}
                                                                        <span className="capitalize">{role}</span>
                                                                    </div>
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={inviteForm.errors.role} />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button type="button" variant="outline" onClick={() => setIsInviteDialogOpen(false)}>
                                                    Cancel
                                                </Button>
                                                <Button type="submit" disabled={inviteForm.processing}>
                                                    {inviteForm.processing ? "Sending..." : "Send Invitation"}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            )}
                        </div>

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Member</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Joined</TableHead>
                                        {canManage && <TableHead>Actions</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {members.map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    <img src={member.avatar} alt={member.name} className="w-8 h-8 rounded-full" />
                                                    <div>
                                                        <p className="font-medium">{member.name}</p>
                                                        <p className="text-sm text-muted-foreground">{member.email}</p>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getRoleBadgeVariant(member.role)}>
                                                    <div className="flex items-center gap-1">
                                                        {getRoleIcon(member.role)}
                                                        <span className="capitalize">{member.role}</span>
                                                    </div>
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{new Date(member.created_at).toLocaleDateString()}</TableCell>
                                            {canManage && (
                                                <TableCell>
                                                    {!member.is_owner && (
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" size="sm">
                                                                    <MoreVertical className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent>
                                                                {availableRoles
                                                                    .filter((role) => role !== member.role && role !== "owner")
                                                                    .map((role) => (
                                                                        <DropdownMenuItem
                                                                            key={role}
                                                                            onClick={() => handleUpdateMemberRole(member.id, role)}
                                                                        >
                                                                            <div className="flex items-center gap-2">
                                                                                {getRoleIcon(role)}
                                                                                <span>Make {role}</span>
                                                                            </div>
                                                                        </DropdownMenuItem>
                                                                    ))}
                                                                <Separator />
                                                                <DropdownMenuItem
                                                                    onClick={() => handleRemoveMember(member.id, member.name)}
                                                                    className="text-red-600"
                                                                >
                                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                                    Remove
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    )}
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pending Invitations */}
                        {pendingInvitations.length > 0 && (
                            <div className="space-y-4">
                                <HeadingSmall title="Pending invitations" description="Manage pending workspace invitations" />

                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Role</TableHead>
                                                <TableHead>Invited By</TableHead>
                                                <TableHead>Expires</TableHead>
                                                {canManage && <TableHead>Actions</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {pendingInvitations.map((invitation) => (
                                                <TableRow key={invitation.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <Mail className="h-4 w-4 text-muted-foreground" />
                                                            <span>{invitation.email}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline">
                                                            <div className="flex items-center gap-1">
                                                                {getRoleIcon(invitation.role)}
                                                                <span className="capitalize">{invitation.role}</span>
                                                            </div>
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>{invitation.invited_by}</TableCell>
                                                    <TableCell>{new Date(invitation.expires_at).toLocaleDateString()}</TableCell>
                                                    {canManage && (
                                                        <TableCell>
                                                            <Button variant="ghost" size="sm" onClick={() => handleCancelInvitation(invitation.id)}>
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </TableCell>
                                                    )}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
