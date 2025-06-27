import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import type { Installation } from "@/types/apps";

interface AppInstallationsTableProps {
    installations: Installation[];
    loading: boolean;
}

export default function AppInstallationsTable({
    installations,
    loading,
}: AppInstallationsTableProps) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString(undefined, {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            timeZoneName: "short",
        });
    };

    if (loading) {
        return (
            <Card>
                <CardHeader>
                    <div className="h-6 w-48 animate-pulse rounded bg-muted" />
                    <div className="h-4 w-64 animate-pulse rounded bg-muted" />
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        {[...Array(5)].map((_, i) => (
                            <Skeleton key={i} className="h-12 w-full" />
                        ))}
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Installation Logs</CardTitle>
                <CardDescription>
                    Latest installations for this app ({installations.length}{" "}
                    records)
                </CardDescription>
            </CardHeader>
            <CardContent>
                {installations.length === 0 ? (
                    <div className="text-center py-8 text-muted-foreground">
                        No installations found for the selected period.
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Identifier</TableHead>
                                    <TableHead>Version</TableHead>
                                    <TableHead>First Seen</TableHead>
                                    <TableHead>Last Seen</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {installations.map((installation) => {
                                    const lastSeen = new Date(
                                        installation.last_seen_at,
                                    );
                                    const isActive =
                                        lastSeen >
                                        new Date(
                                            Date.now() - 24 * 60 * 60 * 1000,
                                        );

                                    return (
                                        <TableRow key={installation.id}>
                                            <TableCell className="font-medium">
                                                {installation.identifier}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {installation.version ||
                                                        "Unknown"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    installation.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    installation.last_seen_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        isActive
                                                            ? "default"
                                                            : "secondary"
                                                    }
                                                >
                                                    {isActive
                                                        ? "Active"
                                                        : "Inactive"}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
