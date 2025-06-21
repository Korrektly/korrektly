import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ResponsiveDialog } from "@/components/ui/responsive-dialog";
import { toast } from "sonner";
import axios from "axios";
import { Loader2 } from "lucide-react";

interface CreateAppModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAppCreated: () => void;
}

export default function CreateAppModal({
    open,
    onOpenChange,
    onAppCreated,
}: CreateAppModalProps) {
    const [name, setName] = useState("");
    const [url, setUrl] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<{ name?: string; url?: string }>({});

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        // Clear previous errors
        setErrors({});

        // Basic validation
        const newErrors: { name?: string; url?: string } = {};
        if (!name.trim()) {
            newErrors.name = "App name is required";
        }
        if (url && !isValidUrl(url)) {
            newErrors.url = "Please enter a valid URL";
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setLoading(true);

        try {
            await axios.post(route("api.apps.store"), {
                name: name.trim(),
                url: url.trim() || null,
            });

            toast.success("App created successfully!");

            // Reset form
            setName("");
            setUrl("");
            setErrors({});

            // Notify parent and close modal
            onAppCreated();
        } catch (error: unknown) {
            const message =
                (error as { response?: { data?: { message?: string } } })
                    ?.response?.data?.message || "Failed to create app";

            // Handle validation errors
            const errorResponse = error as {
                response?: {
                    data?: { errors?: { name?: string; url?: string } };
                };
            };
            if (errorResponse?.response?.data?.errors) {
                setErrors(errorResponse.response.data.errors);
            } else {
                toast.error(message);
            }
        } finally {
            setLoading(false);
        }
    };

    const isValidUrl = (string: string) => {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    };

    const handleClose = () => {
        if (!loading) {
            setName("");
            setUrl("");
            setErrors({});
            onOpenChange(false);
        }
    };

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={handleClose}
            title="Create New App"
            description="Add a new application to track installations and analytics."
            footer={
                <div className="flex justify-end gap-2 w-full">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleClose}
                        disabled={loading}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        form="create-app-form"
                        disabled={loading}
                        className="gap-2"
                    >
                        {loading && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        Create App
                    </Button>
                </div>
            }
        >
            <form
                id="create-app-form"
                onSubmit={handleSubmit}
                className="space-y-4"
            >
                <div className="space-y-2">
                    <Label htmlFor="app-name">App Name *</Label>
                    <Input
                        id="app-name"
                        placeholder="Enter app name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        disabled={loading}
                        className={errors.name ? "border-destructive" : ""}
                    />
                    {errors.name && (
                        <p className="text-sm text-destructive">
                            {errors.name}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="app-url">App URL</Label>
                    <Input
                        id="app-url"
                        placeholder="https://example.com"
                        value={url}
                        onChange={(e) => setUrl(e.target.value)}
                        disabled={loading}
                        className={errors.url ? "border-destructive" : ""}
                    />
                    {errors.url && (
                        <p className="text-sm text-destructive">{errors.url}</p>
                    )}
                </div>
            </form>
        </ResponsiveDialog>
    );
}
