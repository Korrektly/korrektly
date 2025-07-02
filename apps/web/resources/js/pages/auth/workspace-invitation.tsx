import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Head, Link } from "@inertiajs/react";
import { Calendar, Crown, Mail, Shield, User, Users } from "lucide-react";
import React from "react";

interface Invitation {
    token: string;
    email: string;
    workspace_name: string;
    role: string;
    inviter_name: string;
    expires_at: string;
}

interface Props {
    invitation: Invitation;
    userExists: boolean;
    loginUrl: string;
    registerUrl: string;
}

export default function WorkspaceInvitation({ invitation, userExists, loginUrl, registerUrl }: Props) {
    const getRoleIcon = (role: string) => {
        switch (role) {
            case "owner":
                return <Crown className="h-3 w-3 text-yellow-600" />;
            case "admin":
                return <Shield className="h-3 w-3 text-blue-600" />;
            default:
                return <User className="h-3 w-3 text-gray-600" />;
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

    return (
        <>
            <Head title="Join Workspace">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750">
                    <main className="flex w-full max-w-md flex-col">
                        <div className="rounded-lg bg-white p-6 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                            {/* Invitation Header */}
                            <div className="text-center mb-6">
                                <div className="mx-auto h-12 w-12 bg-[#f53003] bg-opacity-10 rounded-full flex items-center justify-center mb-4 dark:bg-[#FF4433] dark:bg-opacity-10">
                                    <Users className="h-6 w-6 text-[#f53003] dark:text-[#FF4433]" />
                                </div>
                                <h1 className="text-xl font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">You're Invited!</h1>
                                <p className="text-[#706f6c] dark:text-[#A1A09A]">{invitation.inviter_name} has invited you to join</p>
                            </div>

                            {/* Workspace Details */}
                            <div className="bg-[#f8f8f7] rounded-md p-4 mb-6 dark:bg-[#1C1C1A]">
                                <div className="flex items-center justify-between mb-3">
                                    <h2 className="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{invitation.workspace_name}</h2>
                                    <Badge
                                        variant={getRoleBadgeVariant(invitation.role) as "default" | "secondary" | "outline"}
                                        className="flex items-center gap-1 capitalize"
                                    >
                                        {getRoleIcon(invitation.role)}
                                        {invitation.role}
                                    </Badge>
                                </div>

                                <div className="space-y-2 text-sm">
                                    <div className="flex items-center text-[#706f6c] dark:text-[#A1A09A]">
                                        <Mail className="h-4 w-4 mr-2" />
                                        <span>{invitation.email}</span>
                                    </div>
                                    <div className="flex items-center text-[#706f6c] dark:text-[#A1A09A]">
                                        <User className="h-4 w-4 mr-2" />
                                        <span>Invited by {invitation.inviter_name}</span>
                                    </div>
                                    <div className="flex items-center text-[#706f6c] dark:text-[#A1A09A]">
                                        <Calendar className="h-4 w-4 mr-2" />
                                        <span>Expires {new Date(invitation.expires_at).toLocaleDateString()}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="space-y-4">
                                <div className="text-center mb-4">
                                    <h3 className="text-base font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Accept your invitation</h3>
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        {userExists
                                            ? "Sign in to your existing account to join the workspace"
                                            : "Sign in or create a new account to join the workspace"}
                                    </p>
                                </div>

                                <div className="space-y-3">
                                    <Button
                                        asChild
                                        className="w-full bg-[#1b1b18] text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                                    >
                                        <Link href={loginUrl}>{userExists ? "Sign In & Join Workspace" : "I have an account"}</Link>
                                    </Button>

                                    {!userExists && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="w-full border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-[#f8f8f7] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1C1C1A]"
                                        >
                                            <Link href={registerUrl}>Create Account & Join Workspace</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="mt-6 pt-4 border-t border-[#e3e3e0] dark:border-[#3E3E3A] text-center">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    If you did not expect to receive this invitation, you can safely ignore it.
                                </p>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
