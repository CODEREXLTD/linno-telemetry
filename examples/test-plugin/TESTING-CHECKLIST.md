# Testing Checklist for CodeRex Telemetry SDK

Complete this checklist to verify all functionality of the Telemetry SDK.

## Prerequisites

- [ ] WordPress 5.0+ installed and running
- [ ] PHP 7.4+ available
- [ ] Composer dependencies installed (`composer install`)
- [ ] Test plugin copied to WordPress plugins directory
- [ ] OpenPanel API key configured in plugin file
- [ ] WordPress debug mode enabled (optional but recommended)

## Test Environment Setup

- [ ] Fresh WordPress installation (or test site)
- [ ] No other telemetry plugins active
- [ ] Admin user logged in
- [ ] Browser DevTools ready (for network inspection)

---

## 1. Installation and Activation Tests

### 1.1 Composer Installation
- [ ] Run `composer install` from package root
- [ ] Verify `vendor/autoload.php` exists
- [ ] Verify no Composer errors

### 1.2 Plugin Activation
- [ ] Copy test plugin to `wp-content/plugins/test-plugin/`
- [ ] Navigate to Plugins page in WordPress admin
- [ ] Activate "Test Telemetry Plugin"
- [ ] Verify no PHP errors in logs
- [ ] Verify plugin appears in active plugins list

### 1.3 SDK Initialization
- [ ] Navigate to "Telemetry Test" menu item
- [ ] Verify "SDK Loaded: ✓ Yes" in debug section
- [ ] Verify "Helper Functions: ✓ Available" in debug section
- [ ] Verify "Telemetry Client Initialized" shows green checkmark

---

## 2. Consent Management Tests

### 2.1 Initial Consent Notice
- [ ] Admin notice appears at top of admin pages
- [ ] Notice contains plugin name "Test Telemetry Plugin"
- [ ] Notice explains data collection clearly
- [ ] "Allow" button is visible and styled as primary
- [ ] "No thanks" button is visible and styled as secondary
- [ ] "Learn more" link is present

### 2.2 Granting Consent
- [ ] Click "Allow" button
- [ ] Notice disappears immediately
- [ ] No JavaScript errors in console
- [ ] Navigate to "Telemetry Test" page
- [ ] Verify consent status shows "Granted ✓" in green
- [ ] Check database: `wp_options` table has `test_telemetry_plugin_telemetry_opt_in` = 'yes'

### 2.3 Install Event on Consent
- [ ] Open browser Network tab
- [ ] Grant consent (if not already done)
- [ ] Verify HTTPS request to OpenPanel API
- [ ] Check OpenPanel dashboard for `telemetry_installed` event
- [ ] Verify event contains: site_url, plugin_name, plugin_version
- [ ] Verify event contains: php_version, wp_version, mysql_version, server_software
- [ ] Verify event contains: install_time timestamp

### 2.4 Denying Consent
- [ ] Delete option from database: `DELETE FROM wp_options WHERE option_name = 'test_telemetry_plugin_telemetry_opt_in'`
- [ ] Reload admin page
- [ ] Notice should reappear
- [ ] Click "No thanks" button
- [ ] Notice disappears
- [ ] Verify consent status shows "Denied ✗" in red
- [ ] Check database: option value is 'no'

### 2.5 No Events When Consent Denied
- [ ] Ensure consent is denied (status shows "Denied ✗")
- [ ] Try sending test event from admin page
- [ ] Verify error message: "Event tracking failed"
- [ ] Check OpenPanel dashboard - no new events should appear
- [ ] Check browser Network tab - no API requests should be made

---

## 3. Custom Event Tracking Tests

### 3.1 Basic Event Tracking
- [ ] Ensure consent is granted
- [ ] Navigate to "Telemetry Test" page
- [ ] Enter event name: `test_button_click`
- [ ] Leave properties empty
- [ ] Click "Send Test Event"
- [ ] Verify success message appears
- [ ] Check OpenPanel for `test_button_click` event
- [ ] Verify event contains all system info

### 3.2 Event with Custom Properties
- [ ] Enter event name: `test_with_properties`
- [ ] Enter JSON properties: `{"user_action": "save", "page": "settings", "count": 5}`
- [ ] Click "Send Test Event"
- [ ] Verify success message
- [ ] Check OpenPanel event contains custom properties
- [ ] Verify custom properties are merged with system info

### 3.3 Event Name Sanitization
- [ ] Enter event name with special chars: `test-event@#$%name!`
- [ ] Send event
- [ ] Verify event name is sanitized (alphanumeric + underscores only)
- [ ] Check OpenPanel for sanitized event name

### 3.4 Invalid JSON Properties
- [ ] Enter event name: `test_invalid_json`
- [ ] Enter invalid JSON: `{this is not valid json}`
- [ ] Send event
- [ ] Verify event still sends (should wrap in raw_data)
- [ ] Check OpenPanel event structure

### 3.5 Post Published Event
- [ ] Create a new post in WordPress
- [ ] Publish the post
- [ ] Check OpenPanel for `post_published` event
- [ ] Verify event contains: post_id, post_type
- [ ] Verify event contains all system info

---

## 4. Weekly System Info Reporting Tests

### 4.1 Cron Job Scheduling
- [ ] Ensure consent is granted
- [ ] Navigate to "Telemetry Test" page
- [ ] Verify "Next scheduled run" shows a future date/time
- [ ] Note the scheduled time

### 4.2 Manual Cron Trigger
- [ ] Click "Trigger Weekly Report" button
- [ ] Verify success message appears
- [ ] Check OpenPanel for `system_info` event
- [ ] Verify event contains: php_version, wp_version, mysql_version, server_software
- [ ] Verify event contains: site_url, plugin_name, plugin_version, timestamp

### 4.3 Custom System Info Filter
- [ ] Check OpenPanel `system_info` event
- [ ] Verify custom fields added by filter: `test_plugin_active`, `active_theme`
- [ ] Verify filter is working correctly

### 4.4 Custom Interval Filter
- [ ] Check code in plugin file for `coderex_telemetry_report_interval` filter
- [ ] Verify it's set to 'weekly' (or change to 'hourly' for testing)
- [ ] If changed to hourly, wait and verify cron runs hourly

### 4.5 Cron Unscheduling on Consent Revoke
- [ ] Grant consent (if not already)
- [ ] Verify cron is scheduled
- [ ] Revoke consent (set option to 'no')
- [ ] Check if cron is unscheduled (implementation dependent)

---

## 5. Deactivation Flow Tests

### 5.1 Deactivation Modal Display
- [ ] Ensure consent is granted
- [ ] Navigate to Plugins page
- [ ] Click "Deactivate" link for Test Telemetry Plugin
- [ ] Verify modal appears (plugin should NOT deactivate immediately)
- [ ] Verify modal has semi-transparent overlay
- [ ] Verify modal content is centered and styled properly

### 5.2 Deactivation Reason Categories
- [ ] Verify all reason options are present:
  - [ ] "It's a temporary deactivation"
  - [ ] "Missing features I need"
  - [ ] "Found a better alternative"
  - [ ] "Plugin not working as expected"
  - [ ] "Other"
- [ ] Verify radio buttons are functional
- [ ] Verify textarea appears for additional comments

### 5.3 Submit Deactivation with Reason
- [ ] Select reason: "Missing features I need"
- [ ] Enter text: "Need better reporting dashboard"
- [ ] Click "Submit & Deactivate"
- [ ] Verify modal closes
- [ ] Verify plugin deactivates
- [ ] Check OpenPanel for `plugin_deactivated` event
- [ ] Verify event contains: reason_category, reason_text
- [ ] Verify event contains all system info

### 5.4 Skip Deactivation Reason
- [ ] Reactivate plugin
- [ ] Grant consent again
- [ ] Click "Deactivate"
- [ ] In modal, click "Skip & Deactivate"
- [ ] Verify plugin deactivates without sending event (or sends with empty reason)

### 5.5 Deactivation Without Consent
- [ ] Reactivate plugin
- [ ] Deny consent (click "No thanks")
- [ ] Navigate to Plugins page
- [ ] Click "Deactivate"
- [ ] Verify modal does NOT appear
- [ ] Verify plugin deactivates immediately
- [ ] Verify no deactivation event sent to OpenPanel

---

## 6. Security Tests

### 6.1 Nonce Verification
- [ ] Open browser DevTools → Network tab
- [ ] Send test event from admin page
- [ ] Check request payload includes `_wpnonce` field
- [ ] Verify request succeeds (200 status)
- [ ] Try to replay request with old nonce (should fail)

### 6.2 Capability Checks
- [ ] Log in as non-admin user (subscriber/contributor)
- [ ] Try to access "Telemetry Test" menu
- [ ] Verify access is denied (menu not visible or permission error)
- [ ] Try to access admin page directly via URL
- [ ] Verify permission check prevents access

### 6.3 Input Sanitization
- [ ] Enter event name: `<script>alert('XSS')</script>`
- [ ] Send event
- [ ] Verify script tags are removed/sanitized
- [ ] Check OpenPanel event name is safe
- [ ] Enter properties with HTML: `{"test": "<b>bold</b>"}`
- [ ] Verify HTML is sanitized in stored data

### 6.4 Output Escaping
- [ ] View page source of "Telemetry Test" page
- [ ] Search for dynamic content (site URL, versions, etc.)
- [ ] Verify all output uses `esc_html()`, `esc_attr()`, or `esc_url()`
- [ ] Check consent notice HTML source
- [ ] Verify all variables are properly escaped

### 6.5 HTTPS Enforcement
- [ ] Open Network tab in DevTools
- [ ] Send test event
- [ ] Verify API request URL starts with `https://`
- [ ] Check for mixed content warnings in console (should be none)
- [ ] Verify API key is in Authorization header, not URL

### 6.6 API Key Security
- [ ] View page source of admin pages
- [ ] Search for API key string
- [ ] Verify API key is NOT exposed in HTML
- [ ] Check JavaScript files for API key
- [ ] Verify API key only used in server-side code

---

## 7. PHP Version Compatibility Tests

### 7.1 PHP 7.4
- [ ] Switch to PHP 7.4
- [ ] Run `php -v` to confirm version
- [ ] Activate plugin
- [ ] Send test event
- [ ] Verify no errors in logs
- [ ] Check all functionality works

### 7.2 PHP 8.0
- [ ] Switch to PHP 8.0
- [ ] Run `php -v` to confirm version
- [ ] Activate plugin
- [ ] Send test event
- [ ] Verify no deprecation warnings
- [ ] Check all functionality works

### 7.3 PHP 8.1
- [ ] Switch to PHP 8.1
- [ ] Run `php -v` to confirm version
- [ ] Activate plugin
- [ ] Send test event
- [ ] Verify no deprecation warnings
- [ ] Check all functionality works

### 7.4 PHP 8.2
- [ ] Switch to PHP 8.2
- [ ] Run `php -v` to confirm version
- [ ] Activate plugin
- [ ] Send test event
- [ ] Verify no deprecation warnings
- [ ] Check all functionality works

---

## 8. Error Handling Tests

### 8.1 Invalid API Key
- [ ] Set API key to empty string or invalid value
- [ ] Try to initialize Client
- [ ] Verify exception is thrown
- [ ] Check error is logged appropriately

### 8.2 Network Timeout
- [ ] Temporarily block OpenPanel domain in hosts file
- [ ] Send test event
- [ ] Verify request times out gracefully (5 seconds)
- [ ] Verify error is logged
- [ ] Verify user sees appropriate error message

### 8.3 API Error Response
- [ ] Use invalid API key (wrong format but not empty)
- [ ] Send test event
- [ ] Verify API returns error
- [ ] Check error is logged with details
- [ ] Verify graceful failure (no fatal errors)

### 8.4 Missing Plugin File
- [ ] Pass invalid file path to Client constructor
- [ ] Verify plugin version defaults to '0.0.0'
- [ ] Verify warning is logged
- [ ] Verify SDK continues to function

---

## 9. Data Validation Tests

### 9.1 Required Fields Present
- [ ] Send test event
- [ ] Capture request payload (Network tab)
- [ ] Verify payload contains: event, properties
- [ ] Verify properties contains: site_url, plugin_name, plugin_version
- [ ] Verify properties contains: php_version, wp_version, mysql_version, server_software, timestamp

### 9.2 Timestamp Format
- [ ] Check timestamp in event payload
- [ ] Verify format is ISO 8601: `YYYY-MM-DDTHH:MM:SSZ`
- [ ] Verify timestamp is current (within a few seconds)

### 9.3 Version Strings
- [ ] Check php_version format (e.g., "8.1.0")
- [ ] Check wp_version format (e.g., "6.4.0")
- [ ] Check mysql_version format (e.g., "8.0.30")
- [ ] Check plugin_version format (e.g., "1.0.0")

---

## 10. Integration Tests

### 10.1 Multiple Plugins Using SDK
- [ ] Create second test plugin using same SDK
- [ ] Activate both plugins
- [ ] Verify each has separate consent option
- [ ] Verify events from both plugins are tracked separately
- [ ] Verify no conflicts between plugins

### 10.2 WordPress Multisite
- [ ] Install WordPress multisite (if available)
- [ ] Activate plugin on network
- [ ] Test on multiple sites
- [ ] Verify consent is per-site
- [ ] Verify events include correct site_url for each site

---

## 11. Performance Tests

### 11.1 Page Load Impact
- [ ] Measure admin page load time without plugin
- [ ] Activate plugin
- [ ] Measure admin page load time with plugin
- [ ] Verify minimal impact (< 100ms difference)

### 11.2 Event Tracking Performance
- [ ] Send 10 events in quick succession
- [ ] Verify all events are sent
- [ ] Verify no blocking or delays
- [ ] Check server load during event sending

### 11.3 Cron Job Performance
- [ ] Trigger weekly cron manually
- [ ] Measure execution time
- [ ] Verify completes in reasonable time (< 5 seconds)
- [ ] Check server resources during execution

---

## 12. Documentation Verification

### 12.1 README Accuracy
- [ ] Follow README installation steps exactly
- [ ] Verify all steps work as documented
- [ ] Check all code examples are correct
- [ ] Verify all links work

### 12.2 Integration Guide
- [ ] Review `/docs/integration.md`
- [ ] Verify code examples match actual implementation
- [ ] Test each code snippet
- [ ] Check for any outdated information

### 12.3 Event Catalog
- [ ] Review `/docs/event-catalog.md`
- [ ] Verify all listed events are implemented
- [ ] Check payload structures match actual events
- [ ] Verify examples are accurate

### 12.4 Privacy Documentation
- [ ] Review `/docs/privacy.md`
- [ ] Verify data collection claims match implementation
- [ ] Check that no undocumented data is collected
- [ ] Verify consent requirements are accurate

---

## Test Results Summary

### Environment Details
- WordPress Version: _______________
- PHP Version: _______________
- MySQL Version: _______________
- Server Software: _______________
- Test Date: _______________

### Pass/Fail Summary
- Total Tests: _______________
- Passed: _______________
- Failed: _______________
- Skipped: _______________

### Issues Found
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

### Notes
_______________________________________________
_______________________________________________
_______________________________________________

---

## Sign-off

Tested by: _______________
Date: _______________
Signature: _______________

**All critical tests passed and SDK is ready for production use: [ ] YES [ ] NO**
