<?php
/**
 * Installation Verification Script
 * 
 * Run this script to verify the Telemetry SDK is properly installed and configured.
 * 
 * Usage: php verify-installation.php
 */

// Color output for terminal
function color_output($text, $color = 'green') {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

function check_pass($message) {
    echo color_output("✓ PASS: ", 'green') . $message . "\n";
}

function check_fail($message) {
    echo color_output("✗ FAIL: ", 'red') . $message . "\n";
}

function check_warn($message) {
    echo color_output("⚠ WARN: ", 'yellow') . $message . "\n";
}

function check_info($message) {
    echo color_output("ℹ INFO: ", 'blue') . $message . "\n";
}

echo "\n";
echo "================================================\n";
echo "CodeRex Telemetry SDK - Installation Verification\n";
echo "================================================\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Check 1: PHP Version
echo "Checking PHP version...\n";
$php_version = PHP_VERSION;
if (version_compare($php_version, '7.4.0', '>=')) {
    check_pass("PHP version $php_version is compatible (>= 7.4.0)");
    $passed++;
} else {
    check_fail("PHP version $php_version is not compatible (requires >= 7.4.0)");
    $failed++;
}

// Check 2: Composer Autoloader
echo "\nChecking Composer autoloader...\n";
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    check_pass("Composer autoloader found at: $autoload_path");
    $passed++;
    
    // Load autoloader
    require_once $autoload_path;
    check_info("Autoloader loaded successfully");
} else {
    check_fail("Composer autoloader not found. Run 'composer install' from package root.");
    $failed++;
    echo "\nCannot continue without autoloader. Exiting.\n\n";
    exit(1);
}

// Check 3: Client Class
echo "\nChecking Client class...\n";
if (class_exists('CodeRex\Telemetry\Client')) {
    check_pass("Client class is available");
    $passed++;
} else {
    check_fail("Client class not found. Check namespace and autoloading.");
    $failed++;
}

// Check 4: ConsentManager Class
echo "\nChecking ConsentManager class...\n";
if (class_exists('CodeRex\Telemetry\ConsentManager')) {
    check_pass("ConsentManager class is available");
    $passed++;
} else {
    check_fail("ConsentManager class not found");
    $failed++;
}

// Check 5: EventDispatcher Class
echo "\nChecking EventDispatcher class...\n";
if (class_exists('CodeRex\Telemetry\EventDispatcher')) {
    check_pass("EventDispatcher class is available");
    $passed++;
} else {
    check_fail("EventDispatcher class not found");
    $failed++;
}

// Check 6: DeactivationHandler Class
echo "\nChecking DeactivationHandler class...\n";
if (class_exists('CodeRex\Telemetry\DeactivationHandler')) {
    check_pass("DeactivationHandler class is available");
    $passed++;
} else {
    check_fail("DeactivationHandler class not found");
    $failed++;
}

// Check 7: DriverInterface
echo "\nChecking DriverInterface...\n";
if (interface_exists('CodeRex\Telemetry\Drivers\DriverInterface')) {
    check_pass("DriverInterface is available");
    $passed++;
} else {
    check_fail("DriverInterface not found");
    $failed++;
}

// Check 8: OpenPanelDriver Class
echo "\nChecking OpenPanelDriver class...\n";
if (class_exists('CodeRex\Telemetry\Drivers\OpenPanelDriver')) {
    check_pass("OpenPanelDriver class is available");
    $passed++;
} else {
    check_fail("OpenPanelDriver class not found");
    $failed++;
}

// Check 9: Utils Class
echo "\nChecking Utils helper class...\n";
if (class_exists('CodeRex\Telemetry\Helpers\Utils')) {
    check_pass("Utils class is available");
    $passed++;
} else {
    check_fail("Utils class not found");
    $failed++;
}

// Check 10: Helper Functions
echo "\nChecking helper functions...\n";
if (function_exists('coderex_telemetry')) {
    check_pass("coderex_telemetry() function is available");
    $passed++;
} else {
    check_fail("coderex_telemetry() function not found");
    $failed++;
}

if (function_exists('coderex_telemetry_track')) {
    check_pass("coderex_telemetry_track() function is available");
    $passed++;
} else {
    check_fail("coderex_telemetry_track() function not found");
    $failed++;
}

// Check 11: Test Plugin File
echo "\nChecking test plugin file...\n";
$plugin_file = __DIR__ . '/test-telemetry-plugin.php';
if (file_exists($plugin_file)) {
    check_pass("Test plugin file exists");
    $passed++;
    
    // Check if API key is configured
    $plugin_content = file_get_contents($plugin_file);
    if (strpos($plugin_content, 'test-api-key-replace-with-real-key') !== false) {
        check_warn("API key not configured. Edit test-telemetry-plugin.php to add your OpenPanel API key.");
        $warnings++;
    } else {
        check_pass("API key appears to be configured");
        $passed++;
    }
} else {
    check_fail("Test plugin file not found");
    $failed++;
}

// Check 12: Documentation Files
echo "\nChecking documentation files...\n";
$docs = [
    'README.md' => __DIR__ . '/README.md',
    'TESTING-CHECKLIST.md' => __DIR__ . '/TESTING-CHECKLIST.md',
    'TESTING-GUIDE.md' => __DIR__ . '/TESTING-GUIDE.md',
];

foreach ($docs as $name => $path) {
    if (file_exists($path)) {
        check_pass("$name exists");
        $passed++;
    } else {
        check_fail("$name not found");
        $failed++;
    }
}

// Check 13: Required PHP Extensions
echo "\nChecking required PHP extensions...\n";
$required_extensions = ['json', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        check_pass("PHP extension '$ext' is loaded");
        $passed++;
    } else {
        check_fail("PHP extension '$ext' is not loaded (REQUIRED for API communication)");
        $failed++;
    }
}

// Additional cURL function check
if (function_exists('curl_init')) {
    check_pass("cURL functions are available");
    $passed++;
} else {
    check_fail("cURL functions are not available");
    $failed++;
}

// Summary
echo "\n================================================\n";
echo "Verification Summary\n";
echo "================================================\n\n";

echo "Total Checks: " . ($passed + $failed + $warnings) . "\n";
echo color_output("Passed: $passed\n", 'green');
if ($failed > 0) {
    echo color_output("Failed: $failed\n", 'red');
}
if ($warnings > 0) {
    echo color_output("Warnings: $warnings\n", 'yellow');
}

echo "\n";

if ($failed === 0) {
    echo color_output("✓ All critical checks passed!\n", 'green');
    echo "\nNext steps:\n";
    echo "1. Configure your OpenPanel API key in test-telemetry-plugin.php\n";
    echo "2. Copy the test plugin to your WordPress plugins directory\n";
    echo "3. Activate the plugin in WordPress admin\n";
    echo "4. Follow the testing guide in TESTING-GUIDE.md\n";
    echo "\n";
    exit(0);
} else {
    echo color_output("✗ Some checks failed. Please fix the issues above.\n", 'red');
    echo "\n";
    exit(1);
}
