# Event Catalog

This document lists all default events tracked by the Code Rex Telemetry SDK, along with their payload structures and examples.

## Default Events

The SDK automatically tracks three types of events:

1. **Install Event** - Sent when a user opts in to telemetry
2. **Deactivation Event** - Sent when a user deactivates the plugin (with reason)
3. **System Info Event** - Sent weekly to track environment changes

## Event Payload Structure

All events follow a consistent structure:

```php
[
    'event' => 'event_name',           // Event identifier
    'properties' => [
        // Standard properties (included in all events)
        'site_url' => 'string',        // Full site URL
        'plugin_name' => 'string',     // Human-readable plugin name
        'plugin_version' => 'string',  // Semantic version (e.g., "1.2.3")
        'php_version' => 'string',     // PHP version (e.g., "8.0.0")
        'wp_version' => 'string',      // WordPress version (e.g., "6.4.0")
        'mysql_version' => 'string',   // MySQL/MariaDB version
        'server_software' => 'string', // Server software info
        'timestamp' => 'string',       // ISO 8601 format
        
        // Optional: Profile identification (if provided)
        '__identify' => [
            'profileId' => 'string',   // Required: UUID v4
            'email' => 'string',       // Optional: User email
            'firstName' => 'string',   // Optional: First name
            'lastName' => 'string',    // Optional: Last name
            'avatar' => 'string',      // Optional: Avatar URL
        ],
        
        // Event-specific properties
        // ...
    ]
]
```

### Profile Identification

**Automatic Site-Level Profile**: The SDK automatically adds a `__identify` property to every event with a site-level profile ID. This is a UUID v4 that uniquely identifies the WordPress installation. No personal information is collected.

**Optional User Information**: You can optionally add user-specific information to the `__identify` property. The site profile ID will be used if you don't provide a custom `profileId`.

**Example - Automatic (default)**:
```php
coderex_telemetry_track('premium_upgrade', [
    'plan' => 'annual',
    'price' => 99.00,
]);
// Automatically includes:
// '__identify' => ['profileId' => '550e8400-e29b-41d4-a716-446655440000']
```

**Example - With User Information**:
```php
coderex_telemetry_track('premium_upgrade', [
    'plan' => 'annual',
    'price' => 99.00,
    '__identify' => [
        // profileId is optional - site profile ID used if not provided
        'email' => 'user@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
    ]
]);
```

**Important**: When adding user information (email, name), you are collecting personal information. Ensure this is disclosed in your privacy policy and consent notice.