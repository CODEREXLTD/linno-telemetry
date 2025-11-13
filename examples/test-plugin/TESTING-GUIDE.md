# Testing Guide for CodeRex Telemetry SDK

This guide provides detailed instructions for testing all aspects of the CodeRex Telemetry SDK using the test plugin.

## Quick Start

### 1. Install Dependencies

From the root of the telemetry package:

```bash
composer install
```

### 2. Copy Test Plugin to WordPress

```bash
# Manual copy
cp -r examples/test-plugin /path/to/wordpress/wp-content/plugins/

# Or use the setup script
cd examples/test-plugin
./setup.sh /path/to/wordpress
```

### 3. Configure API Key

Edit `test-telemetry-plugin.php` and replace:
```php
$api_key = 'test-api-key-replace-with-real-key';
```

With your actual OpenPanel API key.

### 4. Activate Plugin

1. Log into WordPress admin
2. Navigate to Plugins → Installed Plugins
3. Find "Test Telemetry Plugin"
4. Click "Activate"

---

## Test Scenarios

### Scenario 1: First-Time Activation with Consent

**Objective**: Verify the complete consent flow and install event tracking.

**Steps**:

1. Ensure the plugin is not activated and no consent option exists in database
2. Activate the plugin from WordPress admin
3. Observe the admin notice at the top of the page

**Expected Results**:
- Admin notice appears with clear explanation of data collection
- Notice includes "Allow" and "No thanks" buttons
- Notice is styled consistently with WordPress admin

**Steps (continued)**:

4. Open browser DevTools → Network tab
5. Click the "Allow" button
6. Observe the AJAX request

**Expected Results**:
- Notice disappears immediately
- AJAX request to `admin-ajax.php` succeeds (200 status)
- Request includes nonce for security
- No JavaScript errors in console

**Steps (continued)**:

7. Navigate to "Telemetry Test" menu
8. Check the consent status

**Expected Results**:
- Consent status shows "Granted ✓" in green
- Debug section shows SDK is initialized

**Steps (continued)**:

9. Check OpenPanel dashboard for events

**Expected Results**:
- `telemetry_installed` event appears
- Event payload includes:
  - `site_url`: Your WordPress site URL
  - `plugin_name`: "Test Telemetry Plugin"
  - `plugin_version`: "1.0.0"
  - `php_version`: Your PHP version
  - `wp_version`: Your WordPress version
  - `mysql_version`: Your MySQL version
  - `server_software`: Your server software
  - `install_time`: ISO 8601 timestamp
  - `timestamp`: ISO 8601 timestamp

---

### Scenario 2: Custom Event Tracking

**Objective**: Verify custom events can be tracked with properties.

**Prerequisites**: Consent must be granted.

**Steps**:

1. Navigate to "Telemetry Test" menu
2. In the "Test Custom Event" section:
   - Event Name: `user_clicked_button`
   - Event Properties: `{"button_id": "save", "page": "settings", "action": "save_settings"}`
3. Click "Send Test Event"

**Expected Results**:
- Success message appears: "Event tracked successfully!"
- No errors in browser console

**Steps (continued)**:

4. Check OpenPanel dashboard

**Expected Results**:
- `user_clicked_button` event appears
- Event includes custom properties: `button_id`, `page`, `action`
- Event includes all system info (PHP version, WP version, etc.)
- Event includes standard fields: `site_url`, `plugin_name`, `plugin_version`, `timestamp`

**Test Variations**:

A. **Empty Properties**:
   - Send event with no properties
   - Verify event still includes system info

B. **Complex Properties**:
   - Send event with nested JSON: `{"user": {"id": 123, "role": "admin"}, "settings": {"theme": "dark"}}`
   - Verify nested structure is preserved

C. **Invalid Event Name**:
   - Send event with special characters: `test-event@#$%`
   - Verify event name is sanitized (alphanumeric + underscores only)

---

### Scenario 3: Weekly System Info Reporting

**Objective**: Verify background reporting via WP-Cron.

**Prerequisites**: Consent must be granted.

**Steps**:

1. Navigate to "Telemetry Test" menu
2. Check "Next scheduled run" under "Test Weekly Cron"
3. Note the scheduled time

**Expected Results**:
- A future date/time is displayed
- Time is approximately 1 week from now (or custom interval if filter is used)

**Steps (continued)**:

4. Click "Trigger Weekly Report" button

**Expected Results**:
- Success message appears: "Weekly cron triggered manually!"
- No errors in browser console or PHP logs

**Steps (continued)**:

5. Check OpenPanel dashboard

**Expected Results**:
- `system_info` event appears
- Event includes all system information:
  - `php_version`
  - `wp_version`
  - `mysql_version`
  - `server_software`
  - `site_url`
  - `plugin_name`
  - `plugin_version`
  - `timestamp`
- Event includes custom fields from filter:
  - `test_plugin_active`: true
  - `active_theme`: Your theme name

**Automatic Cron Test**:

6. Wait for the scheduled cron time (or use WP-CLI to trigger)
7. Check OpenPanel for automatic `system_info` event

---

### Scenario 4: Deactivation with Feedback

**Objective**: Verify deactivation modal and feedback collection.

**Prerequisites**: Consent must be granted.

**Steps**:

1. Navigate to Plugins → Installed Plugins
2. Find "Test Telemetry Plugin"
3. Click "Deactivate" link
4. Observe the modal

**Expected Results**:
- Modal appears immediately (plugin does NOT deactivate)
- Modal has semi-transparent overlay
- Modal content is centered and styled
- Modal includes:
  - Title: "Quick Feedback"
  - Explanation text
  - Radio buttons for reason categories
  - Textarea for additional comments
  - "Submit & Deactivate" button
  - "Skip & Deactivate" button

**Steps (continued)**:

5. Select reason: "Missing features I need"
6. Enter text: "Would like better analytics dashboard and export features"
7. Open browser DevTools → Network tab
8. Click "Submit & Deactivate"

**Expected Results**:
- AJAX request to `admin-ajax.php` succeeds
- Modal closes
- Plugin deactivates
- Page reloads showing plugin as inactive

**Steps (continued)**:

9. Check OpenPanel dashboard

**Expected Results**:
- `plugin_deactivated` event appears
- Event includes:
  - `reason_category`: "missing_features"
  - `reason_text`: "Would like better analytics dashboard and export features"
  - All system info
  - `site_url`, `plugin_name`, `plugin_version`, `timestamp`

**Test Variation - Skip**:

1. Reactivate plugin and grant consent
2. Click "Deactivate"
3. In modal, click "Skip & Deactivate"
4. Verify plugin deactivates without sending event (or with empty reason)

---

### Scenario 5: Consent Denial

**Objective**: Verify no data is sent when consent is denied.

**Steps**:

1. If plugin is active, deactivate it
2. Delete consent option from database:
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE '%telemetry_opt_in%';
   ```
3. Activate plugin
4. Admin notice appears
5. Click "No thanks" button

**Expected Results**:
- Notice disappears
- No API requests in Network tab
- Consent status shows "Denied ✗" in red

**Steps (continued)**:

6. Navigate to "Telemetry Test" menu
7. Try to send a test event

**Expected Results**:
- Error message appears: "Event tracking failed. Check consent status."
- No API requests in Network tab
- No events appear in OpenPanel

**Steps (continued)**:

8. Try to trigger weekly cron

**Expected Results**:
- Button works but no event is sent
- No API requests in Network tab
- No events appear in OpenPanel

**Steps (continued)**:

9. Deactivate plugin

**Expected Results**:
- Modal does NOT appear
- Plugin deactivates immediately
- No deactivation event sent

---

### Scenario 6: Post Publishing Event

**Objective**: Verify automatic event tracking on WordPress actions.

**Prerequisites**: Consent must be granted.

**Steps**:

1. Navigate to Posts → Add New
2. Create a new post:
   - Title: "Test Post for Telemetry"
   - Content: "This is a test post"
3. Open browser DevTools → Network tab
4. Click "Publish"

**Expected Results**:
- Post is published successfully
- API request to OpenPanel appears in Network tab

**Steps (continued)**:

5. Check OpenPanel dashboard

**Expected Results**:
- `post_published` event appears
- Event includes:
  - `post_id`: The ID of the published post
  - `post_type`: "post"
  - All system info
  - Standard fields

---

### Scenario 7: Security Testing

#### 7.1 Nonce Verification

**Steps**:

1. Open browser DevTools → Network tab
2. Send a test event from admin page
3. Click on the AJAX request
4. View request payload

**Expected Results**:
- Request includes `_wpnonce` parameter
- Request succeeds with 200 status

**Steps (continued)**:

5. Copy the request as cURL
6. Wait 5 minutes
7. Replay the exact same request

**Expected Results**:
- Request fails (nonce expired or already used)
- Error message about invalid nonce

#### 7.2 Input Sanitization

**Steps**:

1. Try to send event with malicious name:
   - Event Name: `<script>alert('XSS')</script>`
2. Send event
3. Check OpenPanel event name

**Expected Results**:
- Script tags are removed
- Event name is sanitized (e.g., `scriptalertXSSscript`)

**Steps (continued)**:

4. Try properties with HTML:
   - Properties: `{"test": "<b>bold</b><script>alert('xss')</script>"}`
5. Send event
6. Check OpenPanel event properties

**Expected Results**:
- HTML tags are sanitized or escaped
- No script execution

#### 7.3 Output Escaping

**Steps**:

1. Navigate to "Telemetry Test" page
2. Right-click → View Page Source
3. Search for dynamic content (site URL, PHP version, etc.)

**Expected Results**:
- All dynamic content is properly escaped
- No raw variables in HTML
- All uses of `esc_html()`, `esc_attr()`, `esc_url()` are correct

#### 7.4 Capability Checks

**Steps**:

1. Log out of admin account
2. Log in as a Subscriber or Contributor
3. Try to access "Telemetry Test" menu

**Expected Results**:
- Menu is not visible
- Direct URL access shows permission error

#### 7.5 HTTPS Enforcement

**Steps**:

1. Open browser DevTools → Network tab
2. Send test event
3. Check the API request URL

**Expected Results**:
- URL starts with `https://`
- No mixed content warnings in console
- API key is in Authorization header, not URL

---

### Scenario 8: PHP Version Compatibility

**Objective**: Verify SDK works across PHP 7.4 - 8.2.

**For each PHP version (7.4, 8.0, 8.1, 8.2)**:

**Steps**:

1. Switch to target PHP version:
   ```bash
   # Example with Homebrew on macOS
   brew unlink php && brew link php@7.4
   php -v
   ```

2. Restart web server (Apache/Nginx)

3. Activate plugin in WordPress

4. Check PHP error logs for any errors or warnings

**Expected Results**:
- No fatal errors
- No deprecation warnings
- No notices or warnings

**Steps (continued)**:

5. Send test event from admin page

**Expected Results**:
- Event sends successfully
- No errors in logs

6. Trigger weekly cron

**Expected Results**:
- Cron executes successfully
- No errors in logs

7. Test deactivation flow

**Expected Results**:
- Modal appears and works correctly
- No errors in logs

**Repeat for all PHP versions**: 7.4, 8.0, 8.1, 8.2

---

### Scenario 9: Error Handling

#### 9.1 Invalid API Key

**Steps**:

1. Edit plugin file
2. Set API key to empty string: `$api_key = '';`
3. Try to activate plugin

**Expected Results**:
- Plugin activation fails or shows error
- Error message indicates API key is required
- Error is logged in PHP error log

#### 9.2 Network Timeout

**Steps**:

1. Block OpenPanel domain in hosts file:
   ```bash
   sudo nano /etc/hosts
   # Add: 127.0.0.1 openpanel.coderex.co
   ```

2. Send test event

**Expected Results**:
- Request times out after 5 seconds
- Error message appears
- Error is logged
- No fatal errors or hanging

3. Remove hosts file entry

#### 9.3 API Error Response

**Steps**:

1. Use invalid API key format (not empty, but wrong)
2. Send test event

**Expected Results**:
- API returns error response
- Error is logged with details
- User sees error message
- No fatal errors

---

## Debugging Tips

### Enable WordPress Debug Mode

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs

```bash
# WordPress debug log
tail -f /path/to/wordpress/wp-content/debug.log

# PHP error log (location varies)
tail -f /var/log/php/error.log
```

### Check Database Options

```sql
-- View consent status
SELECT * FROM wp_options WHERE option_name LIKE '%telemetry%';

-- Reset consent
DELETE FROM wp_options WHERE option_name LIKE '%telemetry%';
```

### Check Cron Jobs

```bash
# Using WP-CLI
wp cron event list

# Trigger specific cron
wp cron event run coderex_telemetry_weekly_report
```

### Inspect Network Requests

1. Open DevTools → Network tab
2. Filter by "XHR" or "Fetch"
3. Look for requests to `admin-ajax.php` and OpenPanel API
4. Check request/response payloads

---

## Common Issues and Solutions

### Issue: SDK Not Loading

**Symptoms**: "SDK Loaded: ✗ No" in debug section

**Solutions**:
- Verify `composer install` was run
- Check `vendor/autoload.php` exists
- Verify namespace is correct in code
- Check PHP error logs for autoload errors

### Issue: Events Not Sending

**Symptoms**: No events appear in OpenPanel

**Solutions**:
- Check consent status (must be "Granted")
- Verify API key is correct
- Check network tab for failed requests
- Review PHP error logs
- Verify OpenPanel API endpoint is accessible

### Issue: Consent Notice Not Appearing

**Symptoms**: No admin notice after activation

**Solutions**:
- Clear browser cache
- Check database - option should not exist
- Verify you're logged in as admin
- Check that `admin_notices` hook is firing
- Review PHP error logs

### Issue: Deactivation Modal Not Showing

**Symptoms**: Plugin deactivates immediately without modal

**Solutions**:
- Ensure consent is granted
- Check browser console for JavaScript errors
- Verify assets are being enqueued
- Clear browser cache
- Check that consent is actually "yes" in database

---

## Test Results Template

Use this template to document your test results:

```
Test Date: _______________
Tester: _______________
Environment:
- WordPress Version: _______________
- PHP Version: _______________
- MySQL Version: _______________
- Server: _______________

Scenario 1: First-Time Activation
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 2: Custom Event Tracking
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 3: Weekly System Info
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 4: Deactivation with Feedback
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 5: Consent Denial
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 6: Post Publishing Event
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 7: Security Testing
- [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 8: PHP Compatibility
- PHP 7.4: [ ] PASS [ ] FAIL
- PHP 8.0: [ ] PASS [ ] FAIL
- PHP 8.1: [ ] PASS [ ] FAIL
- PHP 8.2: [ ] PASS [ ] FAIL
Notes: _________________________________

Scenario 9: Error Handling
- [ ] PASS [ ] FAIL
Notes: _________________________________

Overall Result: [ ] PASS [ ] FAIL
```

---

## Next Steps After Testing

Once all tests pass:

1. Review and update documentation based on findings
2. Fix any issues discovered during testing
3. Update CHANGELOG.md with test results
4. Tag a release version
5. Publish to Packagist (if public)
6. Notify plugin developers of availability

---

## Support

For issues or questions:
- Review main documentation in `/docs`
- Check integration guide: `/docs/integration.md`
- Review event catalog: `/docs/event-catalog.md`
- Check privacy policy: `/docs/privacy.md`
