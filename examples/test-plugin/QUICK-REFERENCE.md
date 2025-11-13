# Quick Reference Card

## Installation (30 seconds)

```bash
# 1. Install dependencies (from SDK root)
composer install

# 2. Verify installation
php examples/test-plugin/verify-installation.php

# 3. Create symlink to WordPress (recommended)
ln -s $(pwd)/examples/test-plugin /path/to/wordpress/wp-content/plugins/test-telemetry-plugin

# OR copy to WordPress (standalone)
cp -r examples/test-plugin /path/to/wordpress/wp-content/plugins/test-telemetry-plugin
cd /path/to/wordpress/wp-content/plugins/test-telemetry-plugin
composer require coderexltd/telemetry

# 4. Configure API key in test-telemetry-plugin.php
# 5. Activate in WordPress admin
```

## Essential Test Scenarios

### ✓ Consent Flow (2 minutes)
1. Activate plugin → Notice appears
2. Click "Allow" → Install event sent
3. Check OpenPanel for `telemetry_installed`

### ✓ Custom Event (1 minute)
1. Go to "Telemetry Test" menu
2. Enter event name and properties
3. Click "Send Test Event"
4. Check OpenPanel for event

### ✓ Weekly Cron (1 minute)
1. Go to "Telemetry Test" menu
2. Click "Trigger Weekly Report"
3. Check OpenPanel for `system_info`

### ✓ Deactivation (2 minutes)
1. Click "Deactivate" on plugin
2. Modal appears with reason form
3. Submit reason
4. Check OpenPanel for `plugin_deactivated`

### ✓ Consent Denial (1 minute)
1. Reset consent in database
2. Click "No thanks" on notice
3. Try sending event → Should fail
4. Check OpenPanel → No events

## Expected Events

| Event | Trigger | Frequency |
|-------|---------|-----------|
| `telemetry_installed` | Consent granted | Once |
| `test_custom_event` | Manual test form | On demand |
| `post_published` | Post published | Per post |
| `system_info` | Weekly cron | Weekly |
| `plugin_deactivated` | Deactivation | On deactivate |

## Event Payload Structure

```json
{
  "event": "event_name",
  "properties": {
    "site_url": "https://example.com",
    "plugin_name": "Test Telemetry Plugin",
    "plugin_version": "1.0.0",
    "php_version": "8.1.0",
    "wp_version": "6.4.0",
    "mysql_version": "8.0.30",
    "server_software": "Apache/2.4.54",
    "timestamp": "2025-11-12T10:30:00Z",
    // ... custom properties
  }
}
```

## Code Examples

### Basic Tracking
```php
coderex_telemetry_track('button_clicked', [
    'button_id' => 'save',
    'page' => 'settings'
]);
```

### Using Client Instance
```php
$telemetry = new \CodeRex\Telemetry\Client(
    'your-api-key',
    'Your Plugin Name',
    __FILE__
);
$telemetry->init();
$telemetry->track('event_name', ['key' => 'value']);
```

### Custom Interval
```php
add_filter('coderex_telemetry_report_interval', function($interval) {
    return 'daily'; // or 'hourly', 'twicedaily'
});
```

### Custom System Info
```php
add_filter('coderex_telemetry_system_info', function($info) {
    $info['custom_field'] = 'value';
    return $info;
});
```

## Debugging Commands

### Check Consent Status
```sql
SELECT * FROM wp_options WHERE option_name LIKE '%telemetry%';
```

### Reset Consent
```sql
DELETE FROM wp_options WHERE option_name LIKE '%telemetry%';
```

### Check Cron Jobs (WP-CLI)
```bash
wp cron event list
wp cron event run coderex_telemetry_weekly_report
```

### View Error Logs
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

## Security Checklist

- [ ] Nonces present in all AJAX requests
- [ ] Input sanitized (event names, properties)
- [ ] Output escaped (HTML, attributes, URLs)
- [ ] HTTPS enforced for API calls
- [ ] API key not exposed in frontend
- [ ] Capability checks for admin pages

## Common Issues

| Issue | Solution |
|-------|----------|
| SDK not loading | Run `composer install` |
| Events not sending | Check consent is granted |
| Notice not appearing | Clear cache, check database |
| Modal not showing | Verify consent is 'yes' |
| API errors | Check API key and endpoint |

## File Locations

```
examples/test-plugin/
├── test-telemetry-plugin.php    # Main plugin file
├── README.md                     # Full documentation
├── TESTING-GUIDE.md             # Detailed test scenarios
├── TESTING-CHECKLIST.md         # Complete checklist
├── QUICK-REFERENCE.md           # This file
├── SUMMARY.md                   # Overview
├── setup.sh                     # Setup script
└── verify-installation.php      # Verification script
```

## Admin Interface

**Location**: WordPress Admin → Telemetry Test

**Features**:
- Consent status display
- Custom event testing form
- Manual cron trigger
- System information display
- Testing checklist
- Debug information

## PHP Version Testing

```bash
# Test with different PHP versions
docker run -v $(pwd):/app -w /app php:7.4-cli php -v
docker run -v $(pwd):/app -w /app php:8.0-cli php -v
docker run -v $(pwd):/app -w /app php:8.1-cli php -v
docker run -v $(pwd):/app -w /app php:8.2-cli php -v
```

## Requirements Coverage

✓ All 9 requirement categories tested:
1. Package Installation (1.1-1.5)
2. Consent Management (2.1-2.6)
3. Install Event (3.1-3.8)
4. Deactivation Event (4.1-4.9)
5. Weekly System Info (5.1-5.8)
6. Developer API (6.1-6.6)
7. OpenPanel Integration (7.1-7.6)
8. Security & Privacy (8.1-8.7)
9. Documentation (9.1-9.7)

## Support Resources

- **Integration Guide**: `/docs/integration.md`
- **Event Catalog**: `/docs/event-catalog.md`
- **Privacy Policy**: `/docs/privacy.md`
- **Example Code**: `/examples/creator-lms-integration.php`

## Time Estimates

- **Quick Test**: 10 minutes (consent + 1 event)
- **Basic Test**: 30 minutes (all essential scenarios)
- **Full Test**: 2-3 hours (complete checklist)
- **PHP Versions**: +1 hour (test all versions)

## Success Criteria

✓ All events appear in OpenPanel
✓ No PHP errors or warnings
✓ No JavaScript console errors
✓ Security measures verified
✓ Works on PHP 7.4 - 8.2
✓ Documentation is accurate

---

**Ready to test?** Run the verification script:
```bash
php examples/test-plugin/verify-installation.php
```
