import { describe, it, expect, beforeEach, afterEach, mock, spyOn } from "bun:test";
import { KorrektlyClient } from "../src/index.ts";

// Mock fetch globally
const mockFetch = mock(() =>
    Promise.resolve({
        ok: true,
        status: 200,
        statusText: "OK",
        body: null,
    }),
);

// @ts-ignore
global.fetch = mockFetch;

const apiUrl = "https://korrektly.com/api/v1/installations";

describe("KorrektlyClient", () => {
    let consoleSpy: {
        info: ReturnType<typeof spyOn>;
        error: ReturnType<typeof spyOn>;
        warn: ReturnType<typeof spyOn>;
        debug: ReturnType<typeof spyOn>;
    };

    beforeEach(() => {
        mockFetch.mockClear();
        consoleSpy = {
            info: spyOn(console, "info").mockImplementation(() => {
                /* mock implementation */
            }),
            error: spyOn(console, "error").mockImplementation(() => {
                /* mock implementation */
            }),
            warn: spyOn(console, "warn").mockImplementation(() => {
                /* mock implementation */
            }),
            debug: spyOn(console, "debug").mockImplementation(() => {
                /* mock implementation */
            }),
        };
    });

    afterEach(() => {
        consoleSpy.info.mockRestore();
        consoleSpy.error.mockRestore();
        consoleSpy.warn.mockRestore();
        consoleSpy.debug.mockRestore();
    });

    it("should be defined", () => {
        expect(KorrektlyClient).toBeDefined();
    });

    describe("Constructor", () => {
        it("should initialize with required options", () => {
            const client = new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
            });

            expect(client).toBeInstanceOf(KorrektlyClient);
            expect(mockFetch).toHaveBeenCalledTimes(1);
        });

        it("should initialize with all options", () => {
            const client = new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                appVersion: "2.0.0",
                disabled: false,
                debug: true,
            });

            expect(client).toBeInstanceOf(KorrektlyClient);
            expect(mockFetch).toHaveBeenCalledTimes(1);
        });

        it("should use default values for optional parameters", () => {
            const client = new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
            });

            expect(client).toBeInstanceOf(KorrektlyClient);
            // Should call trackInstallation by default (not disabled)
            expect(mockFetch).toHaveBeenCalledTimes(1);
        });

        it("should not make API call when disabled", () => {
            const client = new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                disabled: true,
                debug: true,
            });

            expect(client).toBeInstanceOf(KorrektlyClient);
            expect(mockFetch).not.toHaveBeenCalled();
            expect(consoleSpy.info).toHaveBeenCalledWith(expect.stringContaining("[Korrektly] Korrektly is disabled"));
        });
    });

    describe("Installation tracking", () => {
        it("should send correct data to API endpoint", () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                status: 200,
                statusText: "OK",
                body: null,
            });

            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                appVersion: "1.5.0",
            });

            expect(mockFetch).toHaveBeenCalledWith(apiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    app_id: "test-app-id",
                    identifier: "test-instance-id",
                    version: "1.5.0",
                }),
            });
        });

        it("should log success when API call succeeds", async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                status: 201,
                statusText: "Created",
                body: null,
            });

            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                debug: true,
            });

            // Wait for async operation
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(consoleSpy.info).toHaveBeenCalledWith(
                expect.stringContaining("[Korrektly] Installation data sent successfully | Status: 201 | Status Text: Created"),
            );
        });

        it("should log error when API call fails", async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 400,
                statusText: "Bad Request",
                body: null,
            });

            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                debug: true,
            });

            // Wait for async operation
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(consoleSpy.error).toHaveBeenCalledWith(expect.stringContaining("[Korrektly] Failed to install: 400 | Status Text: Bad Request"));
        });
    });

    describe("Logging", () => {
        it("should not log when debug is disabled", () => {
            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                disabled: true,
                debug: false,
            });

            expect(consoleSpy.info).not.toHaveBeenCalled();
        });

        it("should log with correct timestamp format", () => {
            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                disabled: true,
                debug: true,
            });

            expect(consoleSpy.info).toHaveBeenCalledWith(expect.stringMatching(/^\(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\) \[Korrektly\]/));
        });

        it("should use correct console methods for different log types", async () => {
            // Test error logging
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                statusText: "Internal Server Error",
                body: null,
            });

            new KorrektlyClient({
                appId: "test-app-id",
                instanceId: "test-instance-id",
                debug: true,
            });

            // Wait for async operation
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(consoleSpy.error).toHaveBeenCalled();
        });
    });

    describe("Edge cases", () => {
        it("should handle empty strings in required fields", () => {
            expect(() => {
                new KorrektlyClient({
                    appId: "",
                    instanceId: "",
                });
            }).not.toThrow();
        });

        it("should handle special characters in IDs", () => {
            const client = new KorrektlyClient({
                appId: "test-app-id-123!@#",
                instanceId: "test-instance-id-456$%^",
                appVersion: "1.0.0-beta.1",
            });

            expect(client).toBeInstanceOf(KorrektlyClient);
            expect(mockFetch).toHaveBeenCalledWith(
                apiUrl,
                expect.objectContaining({
                    body: JSON.stringify({
                        app_id: "test-app-id-123!@#",
                        identifier: "test-instance-id-456$%^",
                        version: "1.0.0-beta.1",
                    }),
                }),
            );
        });
    });
});
