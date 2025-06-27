/**
 * @description Korrektly client options
 * @property {string} appId - The unique ID of the application
 * @property {string} instanceId - The unique ID of the instance
 * @property {string} appVersion - The version of the application
 * @property {boolean} disabled - Fully disable Korrektly or not
 * @property {boolean} debug - Enable debug mode for logging
 */
interface KorrektlyClientOptions {
    appId: string;
    instanceId: string;
    appVersion?: string;
    disabled?: boolean;
    debug?: boolean;
}

interface InstallationBody {
    app_id: string;
    identifier: string;
    version?: string;
}

/**
 * @description Korrektly client
 * @property {string} appId - The unique ID of the application
 * @property {string} instanceId - The unique ID of the instance
 * @property {string} appVersion - The version of the application
 * @property {boolean} disabled - Fully disable Korrektly or not
 * @property {boolean} debug - Enable debug mode for logging
 */
class KorrektlyClient {
    private appId: string;
    private instanceId: string;
    private appVersion?: string;
    private disabled: boolean;
    private debug: boolean;

    private readonly apiUrl = "https://korrektly.com/api/v1/installations";

    constructor(options: KorrektlyClientOptions) {
        this.appId = options.appId;
        this.instanceId = options.instanceId;
        this.appVersion = options.appVersion;
        this.disabled = options.disabled ?? false;
        this.debug = options.debug ?? false;

        this.init();
    }

    /**
     * @description Initialize the Korrektly client
     */
    private init(): void {
        this.trackInstallation();
    }

    /**
     * @description Send installation data to Korrektly
     */
    private async trackInstallation(): Promise<void> {
        if (this.disabled) {
            this.log(`Korrektly is disabled`, "info");
            return;
        }

        const body: InstallationBody = {
            app_id: this.appId,
            identifier: this.instanceId,
        };

        if (this.appVersion) {
            body.version = this.appVersion;
        }

        const response = await fetch(this.apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            this.log(`Failed to install: ${response.status} | Status Text: ${response.statusText} | Body: ${response.body}`, "error");
            return;
        }

        this.log(`Installation data sent successfully | Status: ${response.status} | Status Text: ${response.statusText}`, "info");
        return;
    }

    /**
     * @description Log a message to the console with a timestamp and type
     * @param {string} message - The message to log
     * @param {"info" | "error" | "warn" | "debug"} type - The type of log
     */
    private log(message: string, type: "info" | "error" | "warn" | "debug" = "info") {
        if (!this.debug) {
            return;
        }

        console[type](`(${new Date().toISOString()}) [Korrektly] ${message}`);
    }
}

export { KorrektlyClient };
