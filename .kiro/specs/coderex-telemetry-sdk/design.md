# Design Document

## Overview

The Code Rex Telemetry SDK is a Composer package that provides privacy-first telemetry tracking for WordPress plugins. The SDK follows a modular architecture with clear separation of concerns: consent management, event tracking, data dispatch, and background reporting. It integrates with OpenPanel as the analytics backend and enforces a consent-first model where no data is transmitted without explicit user approval.

The SDK is designed to be lightweight, easy to integrate, and extensible. Plugin developers can add telemetry to their plugins with just a few lines of code, while the SDK handles all the complexity of consent management, event formatting, secure transmission, and background scheduling.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Plugin                          │
│  (Creator LMS, WPFunnels, WPVR, etc.)                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ new Client($apiKey, $pluginName, __FILE__)
                     │ $client->track('event', [...])
                     │
┌────────────────────▼────────────────────────────────────────┐
│              CodeRex\Telemetry\Client                        │
│  - Initialization                                            │
│  - Public API (track method)                                 │
│  - Consent check delegation                                  │
└─────┬──────────────────────────────────┬────────────────────┘
      │                                  │
      │                                  │
┌─────▼──────────────────┐    ┌─────────▼──────────────────┐
│  ConsentManager        │    │  EventDispatcher           │
│  - Admin notice UI     │    │  - Event normalization     │
│  - Opt-in/out storage  │    │  - Driver delegation       │
│  - Consent checking    │    │  - Payload validation      │
└────────────────────────┘    └─────────┬──────────────────┘
                                        │
                              ┌─────────▼──────────────────┐
                              │  DriverInterface           │
                              │  - send(event, properties) │
                              └─────────┬──────────────────┘
                                        │
                              ┌─────────▼──────────────────┐
                              │  OpenPanelDriver           │
                              │  - HTTPS API calls         │
                              │  - Authentication          │
                              │  - Error handling          │
                              └────────────────────────────┘
                                        │
                                        │ HTTPS
                                        ▼
                              ┌────────────────────────────┐
                              │     OpenPanel API          │
                              └────────────────────────────┘
```

### Component Responsibilities

**Client**: Main entry point for plugin developers. Handles initialization, stores configuration (API key, plugin name, plugin file), and provides the public `track()` method. Delegates consent checking to ConsentManager and event dispatching to EventDispatcher.

**ConsentManager**: Manages the consent lifecycle. Displays admin notice on first activation, handles user opt-in/opt-out actions, stores consent state in wp_options, and provides methods to check current consent status.

**EventDispatcher**: Normalizes event data, adds system information, validates payloads, and delegates actual transmission to the appropriate driver. Acts as an abstraction layer between the Client and the analytics platform.

**DeactivationHandler**: Hooks into WordPress plugin deactivation. When a plugin is deactivated and user has consented, displays a modal asking for deactivation reason and sends the feedback to OpenPanel.

**DriverInterface**: Defines the contract for analytics platform drivers. Ensures that different analytics platforms can be supported in the future without changing core SDK code.

**OpenPanelDriver**: Implements DriverInterface for OpenPanel. Handles authentication, request formatting, HTTPS transmission, and error handling specific to OpenPanel's API.

**Utils**: Provides utility functions for gathering system information (PHP version, WordPress version, MySQL version, server software), sanitizing data, and other helper operations.

## Components and Interfaces

### Client Class

```php
namespace CodeRex\Telemetry;

class Client {
    private string $apiKey;
    private string $pluginName;
    private string $pluginFile;
    private string $pluginVersion;
    private ConsentManager $consentManager;
    private EventDispatcher $dispatcher;
    private DeactivationHandler $deactivationHandler;
    
    public function __construct(string $apiKey, string $pluginName, string $pluginFile);
    public function track(string $event, array $properties = []): bool;
    public function init(): void;
    private function setupHooks(): void;
    private function scheduleBackgroundReporting(): void;
}
```

**Responsibilities:**
- Initialize all SDK components
- Set up WordPress hooks for activation, deactivation, and cron
- Provide public API for tracking events
- Schedule weekly background reporting via WP-Cron
- Store plugin metadata (name, version, file path)

**Key Methods:**
- `__construct()`: Accepts API key, plugin name, and plugin file path. Extracts plugin version from plugin headers. Initializes ConsentManager, EventDispatcher, and DeactivationHandler.
- `init()`: Sets up WordPress hooks and schedules background tasks. Should be called after instantiation.
- `track()`: Public method for sending custom events. Checks consent before dispatching.
- `setupHooks()`: Registers WordPress action hooks for activation notice and cron jobs.
- `scheduleBackgroundReporting()`: Creates WP-Cron job for weekly system info reporting.

### ConsentManager Class

```php
namespace CodeRex\Telemetry;

class ConsentManager {
    private const OPTION_KEY = 'coderex_telemetry_opt_in';
    private const NOTICE_DISMISSED_KEY = 'coderex_telemetry_notice_dismissed';
    
    public function hasConsent(): bool;
    public function grantConsent(): void;
    public function revokeConsent(): void;
    public function shouldShowNotice(): bool;
    public function displayAdminNotice(): void;
    public function handleConsentAction(): void;
    private function dismissNotice(): void;
}
```

**Responsibilities:**
- Check if user has granted consent
- Store and retrieve consent state from wp_options
- Display admin notice with consent options
- Handle AJAX requests for consent actions
- Track whether notice has been dismissed

**Key Methods:**
- `hasConsent()`: Returns true if `coderex_telemetry_opt_in` is 'yes'
- `grantConsent()`: Sets option to 'yes' and triggers install event
- `revokeConsent()`: Sets option to 'no'
- `shouldShowNotice()`: Returns true if consent not set and notice not dismissed
- `displayAdminNotice()`: Renders HTML for admin notice with Allow/No thanks buttons
- `handleConsentAction()`: Processes AJAX request when user clicks consent buttons

### EventDispatcher Class

```php
namespace CodeRex\Telemetry;

class EventDispatcher {
    private DriverInterface $driver;
    private string $pluginName;
    private string $pluginVersion;
    
    public function __construct(DriverInterface $driver, string $pluginName, string $pluginVersion);
    public function dispatch(string $event, array $properties = []): bool;
    private function normalizePayload(string $event, array $properties): array;
    private function addSystemInfo(array $payload): array;
    private function validatePayload(array $payload): bool;
}
```

**Responsibilities:**
- Normalize event data into consistent format
- Add system information to all events
- Validate payload structure
- Delegate transmission to driver
- Handle dispatch errors gracefully

**Key Methods:**
- `dispatch()`: Main method for sending events. Normalizes, validates, and sends via driver.
- `normalizePayload()`: Ensures consistent event structure with required fields.
- `addSystemInfo()`: Adds PHP version, WP version, MySQL version, server software to payload.
- `validatePayload()`: Checks that required fields are present and properly formatted.

### DeactivationHandler Class

```php
namespace CodeRex\Telemetry;

class DeactivationHandler {
    private string $pluginFile;
    private string $pluginName;
    private string $pluginVersion;
    private ConsentManager $consentManager;
    private EventDispatcher $dispatcher;
    
    public function __construct(
        string $pluginFile,
        string $pluginName,
        string $pluginVersion,
        ConsentManager $consentManager,
        EventDispatcher $dispatcher
    );
    public function register(): void;
    public function handleDeactivation(): void;
    public function enqueueModalAssets(): void;
    public function renderModal(): void;
    public function handleReasonSubmission(): void;
}
```

**Responsibilities:**
- Hook into plugin deactivation
- Display modal for deactivation reason (only if consented)
- Capture and send deactivation feedback
- Provide predefined reason categories

**Key Methods:**
- `register()`: Hooks into WordPress deactivation action
- `handleDeactivation()`: Checks consent and displays modal if appropriate
- `enqueueModalAssets()`: Loads CSS/JS for deactivation modal
- `renderModal()`: Renders HTML for reason selection modal
- `handleReasonSubmission()`: Processes AJAX submission and sends event

**Deactivation Reason Categories:**
- Temporary deactivation
- Missing features
- Found better alternative
- Plugin not working
- Too expensive (for premium plugins)
- Other (with text field)

### DriverInterface

```php
namespace CodeRex\Telemetry\Drivers;

interface DriverInterface {
    public function send(string $event, array $properties): bool;
    public function setApiKey(string $apiKey): void;
    public function getLastError(): ?string;
}
```

**Responsibilities:**
- Define contract for analytics platform drivers
- Ensure consistent interface across different platforms
- Support error reporting

### OpenPanelDriver Class

```php
namespace CodeRex\Telemetry\Drivers;

class OpenPanelDriver implements DriverInterface {
    private const API_ENDPOINT = 'https://openpanel.coderex.co/api/track';
    private string $apiKey;
    private ?string $lastError = null;
    
    public function send(string $event, array $properties): bool;
    public function setApiKey(string $apiKey): void;
    public function getLastError(): ?string;
    private function makeRequest(array $payload): bool;
    private function buildHeaders(): array;
    private function handleResponse($response): bool;
}
```

**Responsibilities:**
- Send events to OpenPanel via HTTPS
- Handle authentication with API key
- Format requests according to OpenPanel API spec
- Handle errors and timeouts gracefully
- Log errors for debugging

**Key Methods:**
- `send()`: Main method for transmitting events to OpenPanel
- `makeRequest()`: Uses wp_remote_post() to send HTTPS request
- `buildHeaders()`: Constructs authentication and content-type headers
- `handleResponse()`: Processes response and extracts errors if any

**OpenPanel API Format:**
```json
{
  "event": "event_name",
  "properties": {
    "site_url": "https://example.com",
    "plugin_name": "Creator LMS",
    "plugin_version": "1.2.3",
    "php_version": "8.0.0",
    "wp_version": "6.4.0",
    "mysql_version": "8.0.30",
    "server_software": "Apache/2.4.54",
    "timestamp": "2025-11-12T10:30:00Z"
  }
}
```

### Utils Class

```php
namespace CodeRex\Telemetry\Helpers;

class Utils {
    public static function getPhpVersion(): string;
    public static function getWordPressVersion(): string;
    public static function getMySqlVersion(): string;
    public static function getServerSoftware(): string;
    public static function getSiteUrl(): string;
    public static function getCurrentTimestamp(): string;
    public static function sanitizeEventName(string $event): string;
    public static function sanitizeProperties(array $properties): array;
    public static function getPluginVersion(string $pluginFile): string;
}
```

**Responsibilities:**
- Gather system information
- Sanitize user input
- Provide utility functions for common operations
- Extract plugin metadata

## Data Models

### Event Payload Structure

All events sent to OpenPanel follow this structure:

```php
[
    'event' => 'event_name',           // Required: string, alphanumeric with underscores
    'properties' => [
        'site_url' => 'string',        // Required: full site URL
        'plugin_name' => 'string',     // Required: human-readable plugin name
        'plugin_version' => 'string',  // Required: semantic version
        'php_version' => 'string',     // Required: PHP version
        'wp_version' => 'string',      // Required: WordPress version
        'mysql_version' => 'string',   // Required: MySQL/MariaDB version
        'server_software' => 'string', // Required: server software info
        'timestamp' => 'string',       // Required: ISO 8601 format
        // ... custom properties
    ]
]
```

### Install Event

```php
[
    'event' => 'telemetry_installed',
    'properties' => [
        'site_url' => 'https://example.com',
        'plugin_name' => 'Creator LMS',
        'plugin_version' => '1.2.3',
        'php_version' => '8.0.0',
        'wp_version' => '6.4.0',
        'mysql_version' => '8.0.30',
        'server_software' => 'Apache/2.4.54',
        'install_time' => '2025-11-12T10:30:00Z',
        'timestamp' => '2025-11-12T10:30:00Z'
    ]
]
```

### Deactivation Event

```php
[
    'event' => 'plugin_deactivated',
    'properties' => [
        'site_url' => 'https://example.com',
        'plugin_name' => 'Creator LMS',
        'plugin_version' => '1.2.3',
        'php_version' => '8.0.0',
        'wp_version' => '6.4.0',
        'mysql_version' => '8.0.30',
        'server_software' => 'Apache/2.4.54',
        'reason_category' => 'missing_features',
        'reason_text' => 'Need better quiz functionality',
        'timestamp' => '2025-11-12T10:30:00Z'
    ]
]
```

### Weekly System Info Event

```php
[
    'event' => 'system_info',
    'properties' => [
        'site_url' => 'https://example.com',
        'plugin_name' => 'Creator LMS',
        'plugin_version' => '1.2.3',
        'php_version' => '8.0.0',
        'wp_version' => '6.4.0',
        'mysql_version' => '8.0.30',
        'server_software' => 'Apache/2.4.54',
        'timestamp' => '2025-11-12T10:30:00Z'
    ]
]
```

### Consent State Storage

Stored in WordPress wp_options table:

```php
// Option: coderex_telemetry_opt_in
// Values: 'yes' | 'no' | null (not set)

// Option: coderex_telemetry_notice_dismissed
// Values: '1' | null (not set)
```

## Error Handling

### Consent Not Granted

When `track()` is called but consent is not granted:
- Return `false` immediately
- Do not attempt to send data
- Do not log or throw errors (silent failure)

### Network Errors

When OpenPanel API is unreachable:
- Log error using `error_log()` for debugging
- Store last error in driver for retrieval
- Return `false` from `send()` method
- Do not retry automatically (fail fast)

### Invalid Payload

When event payload fails validation:
- Log validation error with details
- Return `false` from `dispatch()` method
- Do not send malformed data to OpenPanel

### API Key Missing

When Client is instantiated without API key:
- Throw `InvalidArgumentException` in constructor
- Prevent SDK initialization with clear error message

### Plugin File Invalid

When plugin file path doesn't exist or is invalid:
- Log warning but continue initialization
- Use fallback version '0.0.0' if version cannot be extracted

## Testing Strategy

### Unit Tests

**Client Tests:**
- Test initialization with valid parameters
- Test track() method with consent granted
- Test track() method with consent denied
- Test hook registration
- Test cron scheduling

**ConsentManager Tests:**
- Test hasConsent() with different option values
- Test grantConsent() sets correct option value
- Test revokeConsent() sets correct option value
- Test shouldShowNotice() logic
- Test consent action handling

**EventDispatcher Tests:**
- Test payload normalization
- Test system info addition
- Test payload validation
- Test dispatch with valid payload
- Test dispatch with invalid payload

**OpenPanelDriver Tests:**
- Mock wp_remote_post() responses
- Test successful API call
- Test failed API call
- Test authentication header construction
- Test error handling

**Utils Tests:**
- Test system info gathering functions
- Test sanitization functions
- Test plugin version extraction

### Integration Tests

**End-to-End Consent Flow:**
1. Activate plugin
2. Display admin notice
3. Click "Allow"
4. Verify install event sent
5. Verify consent stored

**End-to-End Deactivation Flow:**
1. Grant consent
2. Deactivate plugin
3. Display reason modal
4. Submit reason
5. Verify deactivation event sent

**Background Reporting:**
1. Grant consent
2. Trigger cron manually
3. Verify system info event sent
4. Verify cron rescheduled

### Manual Testing Checklist

- [ ] Install SDK via Composer in test plugin
- [ ] Activate test plugin and verify notice appears
- [ ] Click "Allow" and verify install event in OpenPanel
- [ ] Call track() method and verify custom event in OpenPanel
- [ ] Wait for weekly cron or trigger manually, verify system info event
- [ ] Deactivate plugin and verify reason modal appears
- [ ] Submit reason and verify deactivation event in OpenPanel
- [ ] Click "No thanks" on consent notice and verify no events sent
- [ ] Test with PHP 7.4, 8.0, 8.1, 8.2

## WordPress Integration

### Hooks Used

**Actions:**
- `admin_notices`: Display consent notice
- `admin_init`: Handle consent AJAX actions
- `admin_footer`: Render deactivation modal
- `wp_ajax_coderex_telemetry_consent`: Handle consent submission
- `wp_ajax_coderex_telemetry_deactivation`: Handle deactivation reason submission
- `coderex_telemetry_weekly_report`: Custom cron action for background reporting

**Filters:**
- `coderex_telemetry_report_interval`: Allow plugins to customize reporting frequency (default: 'weekly')
- `coderex_telemetry_api_endpoint`: Allow override of OpenPanel endpoint (for testing)
- `coderex_telemetry_system_info`: Allow plugins to add custom system info

### WP-Cron Setup

```php
// Schedule weekly reporting
if (!wp_next_scheduled('coderex_telemetry_weekly_report')) {
    $interval = apply_filters('coderex_telemetry_report_interval', 'weekly');
    wp_schedule_event(time(), $interval, 'coderex_telemetry_weekly_report');
}

// Hook callback
add_action('coderex_telemetry_weekly_report', function() {
    // Send system info event
});
```

### Admin Notice HTML

```html
<div class="notice notice-info is-dismissible coderex-telemetry-notice">
    <p>
        <strong>Help us improve [Plugin Name]!</strong>
    </p>
    <p>
        Allow us to collect anonymous usage data to help improve the plugin. 
        We only collect technical information like PHP version, WordPress version, 
        and plugin usage patterns. No personal data is collected.
        <a href="#" class="coderex-telemetry-learn-more">Learn more</a>
    </p>
    <p>
        <button class="button button-primary coderex-telemetry-allow">Allow</button>
        <button class="button coderex-telemetry-deny">No thanks</button>
    </p>
</div>
```

### Deactivation Modal HTML

```html
<div id="coderex-telemetry-deactivation-modal" style="display:none;">
    <div class="coderex-telemetry-modal-overlay"></div>
    <div class="coderex-telemetry-modal-content">
        <h2>Quick Feedback</h2>
        <p>If you have a moment, please let us know why you're deactivating [Plugin Name]:</p>
        <form id="coderex-telemetry-deactivation-form">
            <label>
                <input type="radio" name="reason" value="temporary">
                It's a temporary deactivation
            </label>
            <label>
                <input type="radio" name="reason" value="missing_features">
                Missing features I need
            </label>
            <label>
                <input type="radio" name="reason" value="better_alternative">
                Found a better alternative
            </label>
            <label>
                <input type="radio" name="reason" value="not_working">
                Plugin not working as expected
            </label>
            <label>
                <input type="radio" name="reason" value="other">
                Other
            </label>
            <textarea name="reason_text" placeholder="Please tell us more..."></textarea>
            <div class="actions">
                <button type="submit" class="button button-primary">Submit & Deactivate</button>
                <button type="button" class="button coderex-telemetry-skip">Skip & Deactivate</button>
            </div>
        </form>
    </div>
</div>
```

## Security Considerations

### Data Transmission

- All API calls use HTTPS only
- API key transmitted in Authorization header
- No sensitive data in URL parameters
- Timeout set to 5 seconds to prevent hanging

### Input Sanitization

- Event names sanitized to alphanumeric + underscores
- Property values sanitized based on type
- URLs validated and escaped
- User-provided text (deactivation reason) sanitized with `sanitize_textarea_field()`

### WordPress Security

- Nonce verification for all AJAX requests
- Capability checks for admin actions
- Escape all output in admin notices and modals
- Use `wp_remote_post()` instead of cURL for WordPress compatibility

### API Key Storage

- API key stored in plugin code, not in database
- API key never exposed in frontend JavaScript
- API key only used in server-side requests

## Performance Considerations

### Async Operations

- All API calls are non-blocking (fire and forget)
- Failed requests don't impact user experience
- No retry logic to prevent performance degradation

### Caching

- Consent state cached in memory during request
- System info gathered once per event dispatch
- Plugin version extracted once during initialization

### Resource Usage

- Minimal database queries (only wp_options reads/writes)
- No custom database tables
- Cron job runs weekly, not on every page load
- Admin notice only shown on admin pages

### Optimization

- Autoload disabled for telemetry options
- Assets only loaded when needed (deactivation modal)
- Early return when consent not granted
- Lazy loading of driver and dispatcher

## Composer Configuration

### composer.json

```json
{
    "name": "coderexltd/telemetry",
    "description": "Privacy-first telemetry SDK for Code Rex WordPress plugins",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "authors": [
        {
            "name": "Code Rex",
            "email": "support@coderex.co"
        }
    ],
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "mockery/mockery": "^1.5"
    },
    "autoload": {
        "psr-4": {
            "CodeRex\\Telemetry\\": "src/"
        },
        "files": [
            "src/helpers.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "CodeRex\\Telemetry\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### Package Installation

Plugins will install the SDK using:

```bash
composer require coderexltd/telemetry
```

### Autoloading in WordPress Plugin

```php
// In plugin main file
require_once __DIR__ . '/vendor/autoload.php';

use CodeRex\Telemetry\Client;

$telemetry = new Client(
    'a4a8da5b-b419-4656-98e9-4a42e9044891',
    'Creator LMS',
    __FILE__
);
$telemetry->init();
```

## Extensibility

### Custom Intervals

Plugins can customize reporting frequency:

```php
add_filter('coderex_telemetry_report_interval', function($interval) {
    return 'daily'; // or 'twicedaily', 'hourly'
});
```

### Custom System Info

Plugins can add custom system information:

```php
add_filter('coderex_telemetry_system_info', function($info) {
    $info['custom_field'] = 'custom_value';
    return $info;
});
```

### Custom Drivers

Future support for other analytics platforms:

```php
use CodeRex\Telemetry\Drivers\DriverInterface;

class CustomDriver implements DriverInterface {
    public function send(string $event, array $properties): bool {
        // Custom implementation
    }
}

// Use custom driver
$driver = new CustomDriver();
$dispatcher = new EventDispatcher($driver, 'Plugin Name', '1.0.0');
```

## Migration and Versioning

### Semantic Versioning

The SDK follows semantic versioning (MAJOR.MINOR.PATCH):
- MAJOR: Breaking changes to public API
- MINOR: New features, backward compatible
- PATCH: Bug fixes, backward compatible

### Backward Compatibility

- Public API methods will not change signatures in minor versions
- New optional parameters can be added
- Deprecated methods will be marked and maintained for one major version
- Breaking changes will be documented in CHANGELOG.md

### Version 1.0.0 Scope

Initial release includes:
- Core Client, ConsentManager, EventDispatcher
- OpenPanelDriver implementation
- Default events (install, deactivation, system info)
- Admin notice and deactivation modal
- Weekly background reporting
- Helper functions
- Basic documentation

## Documentation Structure

### README.md

- Package overview
- Quick start guide
- Installation instructions
- Basic usage example
- Links to detailed documentation

### docs/integration.md

- Step-by-step integration guide
- Code examples for different scenarios
- Configuration options
- Troubleshooting common issues

### docs/event-catalog.md

- List of all default events
- Event payload structures
- Custom event examples
- Best practices for event naming

### docs/privacy.md

- Data collection policy
- What data is collected
- How data is used
- User rights and consent
- GDPR compliance notes

### examples/creator-lms-integration.php

- Complete working example
- Shows initialization
- Shows custom event tracking
- Shows filter usage
