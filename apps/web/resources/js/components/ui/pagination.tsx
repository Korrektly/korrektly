import { ChevronLeft, ChevronRight, MoreHorizontal } from "lucide-react";
import * as React from "react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    total: number;
    from: number | null;
    to: number | null;
    onPageChange: (page: number) => void;
    className?: string;
}

function Pagination({
    currentPage,
    lastPage,
    total,
    from,
    to,
    onPageChange,
    className,
}: PaginationProps) {
    const getVisiblePages = () => {
        const delta = 2;
        const range = [];
        const rangeWithDots = [];

        for (let i = Math.max(2, currentPage - delta); i <= Math.min(lastPage - 1, currentPage + delta); i++) {
            range.push(i);
        }

        if (currentPage - delta > 2) {
            rangeWithDots.push(1, "...");
        } else {
            rangeWithDots.push(1);
        }

        rangeWithDots.push(...range);

        if (currentPage + delta < lastPage - 1) {
            rangeWithDots.push("...", lastPage);
        } else if (lastPage > 1) {
            rangeWithDots.push(lastPage);
        }

        return rangeWithDots;
    };

    if (lastPage <= 1) return null;

    const visiblePages = getVisiblePages();

    return (
        <div className={cn("flex items-center justify-between", className)}>
            <div className="flex-1 text-sm text-muted-foreground">
                {from && to && (
                    <p>
                        Showing <span className="font-medium">{from}</span> to{" "}
                        <span className="font-medium">{to}</span> of{" "}
                        <span className="font-medium">{total}</span> results
                    </p>
                )}
            </div>
            <div className="flex items-center space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                    className="flex items-center gap-1"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Previous
                </Button>
                
                <div className="flex items-center space-x-1">
                    {visiblePages.map((page, index) => (
                        <React.Fragment key={index}>
                            {page === "..." ? (
                                <Button variant="ghost" size="sm" disabled className="h-8 w-8 p-0">
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            ) : (
                                <Button
                                    variant={currentPage === page ? "default" : "outline"}
                                    size="sm"
                                    onClick={() => onPageChange(page as number)}
                                    className="h-8 w-8 p-0"
                                >
                                    {page}
                                </Button>
                            )}
                        </React.Fragment>
                    ))}
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={currentPage >= lastPage}
                    className="flex items-center gap-1"
                >
                    Next
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

export { Pagination, type PaginationProps }; 