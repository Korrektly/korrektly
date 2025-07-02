import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Pagination } from "@/components/ui/pagination";
import AppDetailsLayout from "@/layouts/app-details-layout";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import type { App, Installation, InstallationListResponse } from "@/types/apps";
import { Head, router } from "@inertiajs/react";
import axios from "axios";
import { useEffect, useState } from "react";
import { toast } from "sonner";

// Import the modular component
import AppInstallationsTable from "@/components/apps/app-installations-table";

interface AppInstallationsProps {
    app: App;
}

export default function AppInstallations({ app }: AppInstallationsProps) {
    const [installations, setInstallations] = useState<Installation[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [paginationMeta, setPaginationMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: null as number | null,
        to: null as number | null,
    });

    // Get initial pagination parameters from URL
    const [currentPage, setCurrentPage] = useState(() => {
        const urlParams = new URLSearchParams(window.location.search);
        return parseInt(urlParams.get("page") || "1");
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Apps", href: "/dashboard" },
        { title: app.name, href: `/apps/${app.id}` },
        { title: "Installations", href: `/apps/${app.id}/installations` },
    ];

    const fetchData = async (page?: number) => {
        try {
            setLoading(true);
            setError(null);

            const requestPage = page || currentPage;

            const listParams = new URLSearchParams({
                mode: "list",
                app_id: app.id.toString(),
                page: requestPage.toString(),
                per_page: paginationMeta.per_page.toString(),
            });

            const listResponse = await axios.get<InstallationListResponse>(route("api.installations.index") + `?${listParams}`);
            setInstallations(listResponse.data.installations);

            // Update pagination metadata - use type assertion for extended meta
            const meta = listResponse.data.meta as typeof listResponse.data.meta & {
                current_page?: number;
                last_page?: number;
                per_page?: number;
                total?: number;
                from?: number | null;
                to?: number | null;
            };
            setPaginationMeta({
                current_page: meta.current_page || 1,
                last_page: meta.last_page || 1,
                per_page: meta.per_page || 15,
                total: meta.total || 0,
                from: meta.from || null,
                to: meta.to || null,
            });
        } catch (error: unknown) {
            const message = (error as { response?: { data?: { message?: string } } })?.response?.data?.message || "Failed to fetch installation data";
            setError(message);
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    const handlePageChange = (page: number) => {
        setCurrentPage(page);

        // Update URL with page parameter
        const url = new URL(window.location.href);
        if (page === 1) {
            url.searchParams.delete("page");
        } else {
            url.searchParams.set("page", page.toString());
        }

        // Update URL without full page reload
        router.visit(url.pathname + url.search, {
            only: [],
            preserveState: true,
            preserveScroll: true,
        });

        fetchData(page);
    };

    useEffect(() => {
        fetchData();
    }, []);

    const retryFetch = () => {
        fetchData();
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={`${app.name} - Installations`} />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <Alert variant="destructive" className="mx-auto max-w-2xl">
                        <AlertDescription className="flex items-center justify-between">
                            <span>{error}</span>
                            <Button onClick={retryFetch} variant="outline" size="sm">
                                Retry
                            </Button>
                        </AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${app.name} - Installations`} />
            <AppDetailsLayout app={app} activeTab="installations">
                <div className="space-y-6">
                    {/* Installation Logs Table */}
                    <AppInstallationsTable installations={installations} loading={loading} />

                    {/* Pagination */}
                    {!loading && paginationMeta.last_page > 1 && (
                        <Pagination
                            currentPage={currentPage}
                            lastPage={paginationMeta.last_page}
                            total={paginationMeta.total}
                            from={paginationMeta.from}
                            to={paginationMeta.to}
                            onPageChange={handlePageChange}
                        />
                    )}
                </div>
            </AppDetailsLayout>
        </AppLayout>
    );
}
