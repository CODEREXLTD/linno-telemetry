<?php
/**
 * Global helper functions for CodeRex Telemetry SDK
 *
 * @package CodeRex\Telemetry
 * @since 1.0.0
 */

if (!function_exists('coderex_telemetry')) {
    /**
     * Get the singleton instance of the Telemetry Client
     *
     * This function returns the global Client instance. The Client must be
     * initialized elsewhere in the plugin before calling this function.
     *
     * @return \CodeRex\Telemetry\Client|null The Client instance or null if not initialized
     * @since 1.0.0
     */
    function coderex_telemetry() {
        global $coderex_telemetry_client;
        return $coderex_telemetry_client ?? null;
    }
}

if (!function_exists('coderex_telemetry_track')) {
    /**
     * Track a telemetry event
     *
     * This is a convenience function that calls the track() method on the
     * global Client instance. If the Client is not initialized or consent
     * is not granted, the event will not be sent.
     *
     * @param string $event The event name (alphanumeric and underscores only)
     * @param array  $properties Optional array of event properties
     *
     * @return bool True if event was sent successfully, false otherwise
     * @since 1.0.0
     */
    function coderex_telemetry_track(string $event, array $properties = []): bool {
        $client = coderex_telemetry();

        if ($client === null) {
            return false;
        }
        
        return $client->track($event, $properties);
    }
}

if (!function_exists('coderex_telemetry_generate_profile_id')) {
    /**
     * Generate a UUID v4 for profile identification
     *
     * This function generates a unique identifier that can be used as a profileId
     * in the __identify property when tracking events with user profiles.
     *
     * @return string UUID v4 string
     * @since 1.0.0
     */
    function coderex_telemetry_generate_profile_id(): string {
        return \CodeRex\Telemetry\Helpers\Utils::generateProfileId();
    }
}
