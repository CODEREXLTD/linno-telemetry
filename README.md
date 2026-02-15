# CodeRex Telemetry SDK

Privacy-first telemetry SDK for Code Rex WordPress plugins.

## Overview

The CodeRex Telemetry SDK is a Composer package that provides privacy-first telemetry tracking for WordPress plugins. It enforces user consent, standardizes event payloads, and integrates directly with OpenPanel analytics platform.

## Compliance and Development Guidelines (MUST READ)

The SDK's core purpose is to handle data transmission securely and ethically. Developers using this SDK **must** adhere to strict consent and disclosure requirements.

*   **Internal Compliance Mandates:** For a complete list of requirements regarding PII collection, opt-in placement, and WordPress.org submission rules, please see our detailed **[Privacy Implementation Guideline](PRIVACY_GUIDELINE.md)**.
    *(This document details mandatory steps for GDPR/WP.org compliance when implementing the SDK.)*


## Features

-   **Privacy-First**: Enforces user consent before sending most data (lifecycle events do not require consent).
-   **Easy Integration**: Simple API with just a few lines of code.
-   **Automatic Lifecycle Events**: Automatically tracks plugin activation and deactivation (including reason).
-   **Automatic PLG Tracking**: Define triggers once, library automatically tracks setup, first strike, and KUI events.
-   **Threshold-Based KUI**: Automatically track when users hit usage thresholds (e.g., 2 orders per week).
-   **Custom Events**: Track plugin-specific events with custom properties.
-   **Asynchronous Sending**: Events are queued and sent via WP-Cron to prevent performance impact.
-   **WordPress Native**: Uses WordPress APIs and follows WordPress coding standards.
-   **Secure**: HTTPS-only transmission, nonce verification, input sanitization.
-   **Internationalized**: All user-facing strings are translatable.

## Requirements

-   PHP 7.4 or higher
-   WordPress 5.0 or higher

## Installation

### Step 1: Configure Composer

Add the VCS repository to your `composer.json`:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/CODEREXLTD/coderex-telemetry"
  }
]
```

### Step 2: Install via Composer

In your WordPress plugin directory, run:

```bash
composer require coderexltd/telemetry:dev-master
```

### Step 3: Require Autoloader

In your main plugin file, require the Composer autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

That's it! You're ready to use the SDK.

## Quick Start

Here's a complete example of integrating the SDK into your WordPress plugin:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Description: An awesome WordPress plugin with telemetry
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: my-awesome-plugin
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Require Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use CodeRex\Telemetry\Client;

// Initialize the telemetry client
$telemetry_client = new Client(
    'your-openpanel-client-id-here',    // Your OpenPanel API Key
    'your-openpanel-secret-key-here',   // Your OpenPanel API Secret
    'My Awesome Plugin',                // Human-readable plugin name
    __FILE__,                           // Path to the main plugin file
    'my-awesome-plugin'                 // Text domain for i18n
);

// Define automatic triggers for PLG events (recommended)
// The SDK will automatically track these events based on your configuration
$telemetry_client->define_triggers([
    // Setup: Fire when user completes setup wizard
    // Developer fires: do_action('my_plugin_setup_complete')
    'setup' => 'my_plugin_setup_complete',
    
    // First Strike: Fire when user experiences core value for first time
    // Developer fires: do_action('my_plugin_first_funnel_created')
    'first_strike' => 'my_plugin_first_funnel_created',
    
    // KUI (Key Usage Indicators): Fire when user gets sufficient value
    'kui' => [
        // Threshold-based: fires when condition is met (e.g., 2 orders per week)
        'order_received' => [
            'hook' => 'woocommerce_order_created',
            'threshold' => ['count' => 2, 'period' => 'week'],
            'callback' => function( $order_id ) {
                return ['order_id' => $order_id];
            }
        ],
        // Simple hook-based: fires every time the hook is triggered
        'funnel_published' => [
            'hook' => 'my_plugin_funnel_published'
        ]
    ]
]);

// Initialize all hooks for consent, deactivation, and triggers
$telemetry_client->init();
```

### Alternative: Fluent API

For more control, use the fluent API:

```php
$telemetry_client->triggers()
    ->on_setup('my_plugin_setup_complete')
    ->on_first_strike('my_plugin_first_funnel_created')
    ->on_kui('order_received', [
        'hook' => 'woocommerce_order_created',
        'threshold' => ['count' => 2, 'period' => 'week'],
        'callback' => function( $order_id ) {
            return ['order_id' => $order_id];
        }
    ])
    ->on('custom_event', 'my_custom_hook', function( $data ) {
        return ['custom_data' => $data];
    });

$telemetry_client->init();
```

### What Happens Next?

1.  **Automatic Activation Tracking**: The SDK tracks `plugin_activated` upon plugin activation (no consent needed).
2.  **User Consent Notice**: For new installations, an admin notice will ask the user for consent to track usage data.
3.  **User Choice**: If the user allows, PLG events (setup, first strike, KUI) and custom events will be tracked automatically based on your trigger configuration. If not, only non-consent events are tracked.
4.  **Deactivation Feedback**: Upon deactivation, a modal will prompt the user for a reason, which is tracked (no consent needed).
5.  **Asynchronous Sending**: All events (requiring consent or not) are added to a local queue and sent to OpenPanel in batches via a daily WP-Cron job.

## Trigger System

The SDK provides a unified way to configure automatic event tracking. Developers define **when** to trigger events, and the library handles the rest.

### Setup Trigger

Fires once when the user completes your plugin's setup wizard.

```php
// In your plugin, fire this action when setup is complete:
do_action('my_plugin_setup_complete');
```

### First Strike Trigger

Fires once when the user experiences the core value of your product for the first time.

```php
// In your plugin, fire this action on first core value moment:
do_action('my_plugin_first_funnel_created');
```

### KUI (Key Usage Indicator) Trigger

Fires when the user gets sufficient value from your plugin. Supports two modes:

**Threshold-Based** (recommended):
```php
// Track when user receives 2+ orders per week
'kui' => [
    'order_received' => [
        'hook' => 'woocommerce_order_created',
        'threshold' => ['count' => 2, 'period' => 'week']
    ]
]
```

**Simple Hook-Based**:
```php
// Track every time the hook fires
'kui' => [
    'funnel_published' => [
        'hook' => 'my_plugin_funnel_published'
    ]
]
```

### Custom Event Triggers

Track any custom event:

```php
$telemetry_client->triggers()
    ->on('page_created', 'my_plugin_page_created', function( $page_id ) {
        return ['page_id' => $page_id, 'type' => get_post_type( $page_id )];
    });
```

## Events Not Requiring Consent

The SDK automatically tracks these events **without requiring user consent**:

-   **`plugin_activated`**: When the plugin is activated.
    -   Includes: `site_url`, `activation_time`.
-   **`plugin_deactivated`**: When the plugin is deactivated.
    -   Includes: `site_url`, `deactivation_time`, `reason_id`, `reason_info`.

**Why no opt-in required?** These lifecycle events are essential for understanding plugin adoption and uninstallation reasons. They are designed to contain no personal data.

## Data Collected (with User Consent)

With user consent, the SDK collects:

-   Site URL
-   Plugin name and version
-   Event timestamps
-   Unique site profile ID (anonymous)
-   Custom event properties (as defined by developer)

**No sensitive personal data** is collected beyond what is strictly necessary for anonymous usage analytics and product improvement, and only with explicit user consent.

## License
GPL-2.0-or-later

## Support
For support, please contact support@coderex.co
