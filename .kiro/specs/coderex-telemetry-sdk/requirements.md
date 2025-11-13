# Requirements Document

## Introduction

The Code Rex Telemetry SDK is a reusable Composer package that provides privacy-first telemetry capabilities for all Code Rex plugins (Creator LMS, WPFunnels, Mail Mint, WPFM, WPVR, Cart Lift, etc.). The SDK enforces user consent, standardizes event payloads, and integrates directly with OpenPanel (internally hosted by Code Rex). It supports PHP 7.4+ and uses PSR-4 autoloading to enable easy integration across the plugin ecosystem.

## Glossary

- **Telemetry SDK**: The software development kit that provides telemetry tracking functionality
- **OpenPanel**: The analytics backend platform used to collect and analyze telemetry data
- **Consent Manager**: The component responsible for managing user opt-in/opt-out preferences
- **Event Dispatcher**: The component that sends telemetry events to OpenPanel
- **Deactivation Handler**: The component that captures plugin deactivation reasons
- **Client**: The main class that plugin developers instantiate to use the SDK
- **Driver**: An interface implementation that handles communication with a specific analytics platform
- **System Info**: Environment data including PHP version, WordPress version, MySQL version, and server software
- The code should follow WordPress coding standard, PHPDoc, WP security best practices. PHPDoc format 

```
/**
* Send tracking data to AppSero server
*
* @param bool $override Whether to override the tracking allowed check.
*
* @return void
* @since 1.0.0
*/
```
- As many plugin will use it, anything that is save by the package should use the plugins folder name that uses the package. lets say plugin folder name is wpvr. then the option name would be wpvr_telemetry_opt_in. If used as standalone plugin for testing purpose and development, the option name would be coderex_telemetry_opt_in.

## Requirements

### Requirement 1: Package Installation and Autoloading

**User Story:** As a plugin developer, I want to install the telemetry SDK via Composer, so that I can quickly add telemetry to my plugin without manual file management.

#### Acceptance Criteria

1. WHEN a developer runs `composer require coderexltd/telemetry`, THE Telemetry SDK SHALL be installed with all dependencies
2. THE Telemetry SDK SHALL use PSR-4 autoloading with namespace `CodeRex\Telemetry`
3. THE Telemetry SDK SHALL support PHP version 7.4 and above
4. THE Telemetry SDK SHALL include a composer.json file with proper package metadata and autoload configuration
5. WHERE the SDK is installed, THE Telemetry SDK SHALL be accessible via the `CodeRex\Telemetry\Client` class


### Requirement 2: Consent Management

**User Story:** As a WordPress site administrator, I want to explicitly opt-in to telemetry data collection, so that my privacy preferences are respected and no data is sent without my permission.

#### Acceptance Criteria

1. WHEN a plugin using the SDK is activated for the first time, THE Telemetry SDK SHALL display an admin notice explaining telemetry collection
2. THE Telemetry SDK SHALL provide "Allow" and "No thanks" buttons in the consent notice
3. WHEN the administrator clicks "Allow", THE Telemetry SDK SHALL store `coderex_telemetry_opt_in` with value `yes` in wp_options
4. WHEN the administrator clicks "No thanks", THE Telemetry SDK SHALL store `coderex_telemetry_opt_in` with value `no` in wp_options
5. THE Telemetry SDK SHALL NOT send any telemetry data WHEN `coderex_telemetry_opt_in` is `no` or not set
6. THE Telemetry SDK SHALL send telemetry data only WHEN `coderex_telemetry_opt_in` is `yes`

### Requirement 3: Install Event Tracking

**User Story:** As a product manager, I want to track when users opt-in to telemetry, so that I can understand plugin adoption and environment distribution.

#### Acceptance Criteria

1. WHEN a user opts in to telemetry, THE Telemetry SDK SHALL send an install event to OpenPanel
2. THE Telemetry SDK SHALL include site_url in the install event payload
3. THE Telemetry SDK SHALL include event name `telemetry_installed` in the install event payload
4. THE Telemetry SDK SHALL include php_version in the install event payload
5. THE Telemetry SDK SHALL include wp_version in the install event payload
6. THE Telemetry SDK SHALL include mysql_version in the install event payload
7. THE Telemetry SDK SHALL include server_software in the install event payload
8. THE Telemetry SDK SHALL include install_time timestamp in the install event payload

### Requirement 4: Deactivation Event Tracking

**User Story:** As a product manager, I want to understand why users deactivate plugins, so that I can identify areas for improvement and reduce churn.

#### Acceptance Criteria

1. WHEN a plugin is deactivated AND the user has opted in, THE Telemetry SDK SHALL prompt the user for a deactivation reason
2. THE Telemetry SDK SHALL NOT prompt for deactivation reason WHEN the user has not opted in
3. THE Telemetry SDK SHALL send a deactivation event to OpenPanel with the user's reason
4. THE Telemetry SDK SHALL include site_url in the deactivation event payload
5. THE Telemetry SDK SHALL include plugin_name in the deactivation event payload
6. THE Telemetry SDK SHALL include plugin_version in the deactivation event payload
7. THE Telemetry SDK SHALL include reason_category in the deactivation event payload
8. THE Telemetry SDK SHALL include reason_text in the deactivation event payload
9. THE Telemetry SDK SHALL include timestamp in the deactivation event payload

### Requirement 5: Weekly System Info Reporting

**User Story:** As a product manager, I want to receive regular updates about user environments, so that I can track version upgrades and system changes over time.

#### Acceptance Criteria

1. WHEN a user has opted in, THE Telemetry SDK SHALL send system info events once per week via WP-Cron
2. THE Telemetry SDK SHALL include site_url in the system info event payload
3. THE Telemetry SDK SHALL include php_version in the system info event payload
4. THE Telemetry SDK SHALL include wp_version in the system info event payload
5. THE Telemetry SDK SHALL include mysql_version in the system info event payload
6. THE Telemetry SDK SHALL include server_software in the system info event payload
7. THE Telemetry SDK SHALL include timestamp in the system info event payload
8. WHERE a developer applies the `coderex_telemetry_report_interval` filter, THE Telemetry SDK SHALL use the custom interval instead of weekly

### Requirement 6: Developer API

**User Story:** As a plugin developer, I want a simple API to track custom events, so that I can send plugin-specific telemetry data without understanding the SDK internals.

#### Acceptance Criteria

1. THE Telemetry SDK SHALL provide a `Client` class that accepts API key, plugin name, and plugin file path as constructor parameters
2. THE Telemetry SDK SHALL provide a `track()` method on the Client class that accepts event name and properties array
3. WHEN a developer calls `track()`, THE Telemetry SDK SHALL send the event to OpenPanel if consent is granted
4. THE Telemetry SDK SHALL provide a global helper function `coderex_telemetry()` that returns the Client instance
5. THE Telemetry SDK SHALL provide a global helper function `coderex_telemetry_track()` that accepts event name and properties
6. THE Telemetry SDK SHALL allow developers to define their OpenPanel API key in their plugin code

### Requirement 7: OpenPanel Integration

**User Story:** As a system architect, I want all telemetry data sent securely to OpenPanel, so that we have a centralized analytics platform for all Code Rex plugins.

#### Acceptance Criteria

1. THE Telemetry SDK SHALL send all events to OpenPanel via HTTPS
2. THE Telemetry SDK SHALL use the OpenPanel API key provided by the plugin developer
3. THE Telemetry SDK SHALL format event payloads according to OpenPanel's API specification
4. WHEN an event is sent, THE Telemetry SDK SHALL include all required OpenPanel headers and authentication
5. THE Telemetry SDK SHALL implement a DriverInterface for analytics platform abstraction
6. THE Telemetry SDK SHALL provide an OpenPanelDriver implementation of the DriverInterface

### Requirement 8: Security and Privacy

**User Story:** As a WordPress site administrator, I want assurance that no personal data is collected without my consent, so that I can trust the telemetry system respects my privacy.

#### Acceptance Criteria

1. THE Telemetry SDK SHALL NOT send any data WHEN user consent is not granted
2. THE Telemetry SDK SHALL send all data over HTTPS only
3. THE Telemetry SDK SHALL NOT collect user email addresses
4. THE Telemetry SDK SHALL NOT collect usernames
5. THE Telemetry SDK SHALL NOT collect IP addresses
6. THE Telemetry SDK SHALL collect site_url as-is because explicit consent is required
7. THE Telemetry SDK SHALL store the OpenPanel API key in plugin code, not in the SDK package

### Requirement 9: Package Structure and Documentation

**User Story:** As a plugin developer, I want clear documentation and examples, so that I can integrate the SDK quickly and correctly.

#### Acceptance Criteria

1. THE Telemetry SDK SHALL include a README.md file with package overview and quick start guide
2. THE Telemetry SDK SHALL include integration.md documentation explaining how to integrate with plugins
3. THE Telemetry SDK SHALL include event-catalog.md documentation listing all available events
4. THE Telemetry SDK SHALL include privacy.md documentation explaining data collection and privacy practices
5. THE Telemetry SDK SHALL include example integration code in the examples directory
6. THE Telemetry SDK SHALL organize source code in a src directory with proper namespace structure
7. THE Telemetry SDK SHALL include unit tests in a tests directory
