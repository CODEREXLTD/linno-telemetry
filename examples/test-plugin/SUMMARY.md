# Test Plugin Summary

## Overview

The Test Telemetry Plugin is a comprehensive testing and demonstration tool for the CodeRex Telemetry SDK. It provides both automated testing capabilities and a user-friendly admin interface for manual testing.

## What's Included

### 1. Main Plugin File
**File**: `test-telemetry-plugin.php`

A fully functional WordPress plugin that:
- Initializes the Telemetry SDK with proper configuration
- Demonstrates all SDK features
- Provides an admin interface for testing
- Includes automatic event tracking examples
- Shows filter usage for customization
- Smart autoloader detection (works both in SDK repo and standalone)

### 2. Documentation

#### README.md
- Installation instructions
- Feature overview
- Testing scenarios
- Debugging tips
- Code examples

#### TESTING-GUIDE.md
- Detailed step-by-step testing instructions
- 9 comprehensive test scenarios
- Expected results for each test
- Debugging tips
- Common issues and solutions

#### TESTING-CHECKLIST.md
- Complete checklist with 12 major test categories
- Over 100 individual test items
- Covers all requirements from the spec
- Includes sign-off section for formal testing

#### SUMMARY.md (this file)
- Quick overview of the test plugin
- File descriptions
- Quick start guide

### 3. Utility Scripts

#### setup.sh
- Automated setup script
- Copies plugin to WordPress installation
- Creates necessary symlinks
- Validates environment

#### verify-installation.php
- Pre-flight verification script
- Checks all dependencies
- Validates SDK installation
- Provides actionable feedback

## Key Features

### Admin Test Interface

Located at **WordPress Admin → Telemetry Test**

**Consent Status Display**
- Shows current opt-in/opt-out status
- Color-coded for easy identification
- Real-time status updates

**Custom Event Testing**
- Form to send test events
- JSON property editor
- Immediate feedback on success/failure
- Network request visibility

**Manual Cron Trigger**
- Button to trigger weekly system info report
- Shows next scheduled run time
- Useful for testing without waiting

**System Information Display**
- Shows all collected system data
- PHP, WordPress, MySQL versions
- Server software information
- Site URL

**Testing Checklist**
- Built-in checklist for quick reference
- Covers all major test scenarios

**Debug Information**
- SDK initialization status
- Class availability checks
- Helper function verification
- Plugin metadata

### Automatic Event Tracking

**Post Published Event**
- Automatically tracks when posts are published
- Includes post ID and post type
- Demonstrates WordPress hook integration

**Install Event**
- Sent when user grants consent
- Includes all system information
- One-time event

**Weekly System Info**
- Scheduled via WP-Cron
- Sends system information weekly
- Can be triggered manually

**Deactivation Event**
- Captures deactivation reason
- Modal interface for feedback
- Includes reason category and text

### Filter Demonstrations

**Report Interval Customization**
```php
add_filter('coderex_telemetry_report_interval', function($interval) {
    return 'daily'; // or 'hourly', 'twicedaily'
});
```

**Custom System Info**
```php
add_filter('coderex_telemetry_system_info', function($info) {
    $info['custom_field'] = 'custom_value';
    return $info;
});
```

## Quick Start

### 1. Install Dependencies
```bash
# From SDK root directory
composer install
```

### 2. Verify Installation
```bash
php examples/test-plugin/verify-installation.php
```

### 3. Configure API Key
Edit `examples/test-plugin/test-telemetry-plugin.php`:
```php
$api_key = 'your-openpanel-api-key-here';
```

### 4. Install to WordPress

**Option A: Symlink (Recommended for Development)**
```bash
# Keeps plugin in SDK repo, uses SDK's vendor directory
ln -s $(pwd)/examples/test-plugin /path/to/wordpress/wp-content/plugins/test-telemetry-plugin
```

**Option B: Standalone Copy**
```bash
# Copy plugin and install its own dependencies
cp -r examples/test-plugin /path/to/wordpress/wp-content/plugins/test-telemetry-plugin
cd /path/to/wordpress/wp-content/plugins/test-telemetry-plugin
composer install
```

**Option C: Use Setup Script**
```bash
./examples/test-plugin/setup.sh /path/to/wordpress
# Follow the prompts to choose installation method
```

### 5. Activate Plugin
- Log into WordPress admin
- Navigate to Plugins → Installed Plugins
- Activate "Test Telemetry Plugin"

### 6. Start Testing
- Follow the consent flow
- Navigate to "Telemetry Test" menu
- Use the testing guide for comprehensive testing

## Autoloader Path Logic

The plugin intelligently detects the Composer autoloader:

1. **First**: Checks `./vendor/autoload.php` (standalone installation)
2. **Second**: Checks `../../vendor/autoload.php` (symlinked from SDK repo)
3. **Fallback**: Shows admin error if neither found

## Testing Coverage

The test plugin validates all requirements from the specification:

### Requirement Coverage

✓ **1.1-1.5**: Package Installation and Autoloading
- Composer installation
- PSR-4 autoloading
- PHP 7.4+ compatibility
- Client class accessibility

✓ **2.1-2.6**: Consent Management
- Admin notice display
- Opt-in/opt-out functionality
- Consent storage
- Data transmission control

✓ **3.1-3.8**: Install Event Tracking
- Event triggering on consent
- System information collection
- Proper payload structure

✓ **4.1-4.9**: Deactivation Event Tracking
- Modal display
- Reason collection
- Event transmission

✓ **5.1-5.8**: Weekly System Info Reporting
- WP-Cron scheduling
- System info collection
- Custom interval support

✓ **6.1-6.6**: Developer API
- Client class usage
- track() method
- Helper functions
- API key configuration

✓ **7.1-7.6**: OpenPanel Integration
- HTTPS transmission
- API authentication
- Payload formatting
- Driver abstraction

✓ **8.1-8.7**: Security and Privacy
- Consent enforcement
- HTTPS only
- No PII collection
- Input sanitization
- Output escaping
- Nonce verification

✓ **9.1-9.7**: Package Structure and Documentation
- README and guides
- Code examples
- Documentation completeness

## Test Scenarios

The test plugin supports these comprehensive test scenarios:

1. **First-Time Activation with Consent**
   - Verify consent notice
   - Test opt-in flow
   - Validate install event

2. **Custom Event Tracking**
   - Send events with properties
   - Test event sanitization
   - Verify payload structure

3. **Weekly System Info Reporting**
   - Manual cron trigger
   - Automatic scheduling
   - Custom interval testing

4. **Deactivation with Feedback**
   - Modal display
   - Reason submission
   - Event validation

5. **Consent Denial**
   - Opt-out flow
   - Verify no data sent
   - Test all features blocked

6. **Post Publishing Event**
   - Automatic tracking
   - WordPress hook integration

7. **Security Testing**
   - Nonce verification
   - Input sanitization
   - Output escaping
   - Capability checks
   - HTTPS enforcement

8. **PHP Version Compatibility**
   - Test with PHP 7.4, 8.0, 8.1, 8.2
   - Verify no errors or warnings

9. **Error Handling**
   - Invalid API key
   - Network timeouts
   - API errors

## Expected Events in OpenPanel

After complete testing, you should see these events:

1. **telemetry_installed**
   - Triggered: When consent is granted
   - Frequency: Once per installation

2. **test_custom_event** (or your custom names)
   - Triggered: When using test form
   - Frequency: On demand

3. **post_published**
   - Triggered: When publishing posts
   - Frequency: Per post published

4. **system_info**
   - Triggered: Weekly via cron or manual trigger
   - Frequency: Weekly (or custom interval)

5. **plugin_deactivated**
   - Triggered: When deactivating with reason
   - Frequency: On deactivation

## Validation Checklist

Before considering testing complete:

- [ ] All SDK classes load without errors
- [ ] Consent notice appears and functions correctly
- [ ] Install event is sent with complete data
- [ ] Custom events can be tracked successfully
- [ ] Weekly cron is scheduled and executes
- [ ] Deactivation modal appears and captures feedback
- [ ] Consent denial prevents all data transmission
- [ ] All security measures are verified (nonces, sanitization, escaping)
- [ ] Plugin works on PHP 7.4, 8.0, 8.1, and 8.2
- [ ] Error handling works gracefully
- [ ] All events appear in OpenPanel with correct structure
- [ ] Documentation is accurate and complete

## Troubleshooting

### Common Issues

**SDK Not Loading**
- Run `composer install` from package root
- Verify `vendor/autoload.php` exists
- Check PHP error logs

**Events Not Sending**
- Verify consent is granted (status shows "Granted ✓")
- Check API key is configured correctly
- Verify OpenPanel endpoint is accessible
- Check browser Network tab for failed requests

**Consent Notice Not Appearing**
- Clear browser cache
- Verify option doesn't exist in database
- Check you're logged in as admin
- Review PHP error logs

**Deactivation Modal Not Showing**
- Ensure consent is granted
- Check browser console for JavaScript errors
- Clear browser cache
- Verify consent option value is 'yes'

### Debug Mode

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check logs:
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

## Next Steps

After successful testing:

1. **Review Results**
   - Document any issues found
   - Verify all requirements are met
   - Complete the testing checklist

2. **Update Documentation**
   - Fix any inaccuracies discovered
   - Add any missing information
   - Update examples if needed

3. **Prepare for Release**
   - Tag version in git
   - Update CHANGELOG.md
   - Prepare release notes

4. **Deploy to Production**
   - Publish to Packagist (if public)
   - Notify plugin developers
   - Provide integration support

## Support and Resources

- **Main Documentation**: `/docs/`
- **Integration Guide**: `/docs/integration.md`
- **Event Catalog**: `/docs/event-catalog.md`
- **Privacy Policy**: `/docs/privacy.md`
- **Example Integration**: `/examples/creator-lms-integration.php`

## License

GPL-2.0-or-later

---

**Ready to test?** Start with the verification script, then follow the testing guide!

```bash
php examples/test-plugin/verify-installation.php
```
