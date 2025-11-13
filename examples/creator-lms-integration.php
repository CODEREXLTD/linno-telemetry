<?php
/**
 * Plugin Name: Creator LMS
 * Plugin URI: https://coderex.co/creator-lms
 * Description: Complete Learning Management System for WordPress
 * Version: 1.2.3
 * Author: Code Rex
 * Author URI: https://coderex.co
 * License: GPL-2.0+
 * Text Domain: creator-lms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 1: Load Composer autoloader
 * 
 * This loads all dependencies including the CodeRex Telemetry SDK.
 * Make sure you've run 'composer require coderexltd/telemetry' first.
 */
require_once __DIR__ . '/vendor/autoload.php';

use CodeRex\Telemetry\Client;

/**
 * Step 2: Initialize the Telemetry Client
 * 
 * Create a global variable to store the telemetry client instance.
 * This allows you to access it throughout your plugin.
 */
global $creator_lms_telemetry;

/**
 * Step 3: Instantiate the Client with your configuration
 * 
 * Parameters:
 * - API Key: Your OpenPanel API key (get this from OpenPanel dashboard)
 * - Plugin Name: Human-readable name of your plugin
 * - Plugin File: Use __FILE__ to pass the main plugin file path
 * 
 * The SDK will automatically:
 * - Extract the plugin version from the plugin headers
 * - Initialize consent management
 * - Set up the OpenPanel driver
 * - Prepare event dispatching
 */
$creator_lms_telemetry = new Client(
    'a4a8da5b-b419-4656-98e9-4a42e9044891', // Replace with your actual OpenPanel API key
    'Creator LMS',                           // Your plugin name
    __FILE__                                 // Main plugin file
);

/**
 * Step 4: Initialize the SDK
 * 
 * This sets up WordPress hooks and schedules background reporting.
 * Call this after instantiation to activate telemetry features:
 * - Admin notice for consent
 * - Deactivation feedback modal
 * - Weekly system info reporting via WP-Cron
 */
$creator_lms_telemetry->init();

/**
 * Step 5: Track custom events throughout your plugin
 * 
 * Use the track() method to send custom events when important actions occur.
 * Events are only sent if the user has granted consent.
 * 
 * The track() method accepts:
 * - Event name: Use snake_case, alphanumeric with underscores only
 * - Properties: Array of additional data to send with the event
 */

// Example: Track when a course is created
add_action('creator_lms_course_created', function($course_id) {
    global $creator_lms_telemetry;
    
    $creator_lms_telemetry->track('course_created', [
        'course_id' => $course_id,
        'course_type' => get_post_meta($course_id, '_course_type', true),
        'has_lessons' => get_post_meta($course_id, '_lesson_count', true) > 0,
    ]);
});

// Example: Track when a student enrolls in a course
add_action('creator_lms_student_enrolled', function($user_id, $course_id) {
    global $creator_lms_telemetry;
    
    $creator_lms_telemetry->track('student_enrolled', [
        'course_id' => $course_id,
        'enrollment_type' => 'manual', // or 'automatic', 'purchase', etc.
    ]);
}, 10, 2);

// Example: Track when a quiz is completed
add_action('creator_lms_quiz_completed', function($quiz_id, $user_id, $score) {
    global $creator_lms_telemetry;
    
    $creator_lms_telemetry->track('quiz_completed', [
        'quiz_id' => $quiz_id,
        'score' => $score,
        'passed' => $score >= 70,
    ]);
}, 10, 3);

// Example: Track when a certificate is generated
add_action('creator_lms_certificate_generated', function($certificate_id, $course_id) {
    global $creator_lms_telemetry;
    
    $creator_lms_telemetry->track('certificate_generated', [
        'certificate_id' => $certificate_id,
        'course_id' => $course_id,
    ]);
}, 10, 2);

/**
 * Step 6: Customize telemetry behavior with filters
 * 
 * The SDK provides filters to customize its behavior without modifying the SDK code.
 */

/**
 * Filter: Customize the reporting interval
 * 
 * By default, system info is sent weekly. You can change this to:
 * - 'hourly': Every hour
 * - 'twicedaily': Twice per day
 * - 'daily': Once per day
 * - 'weekly': Once per week (default)
 * 
 * Note: More frequent reporting means more API calls, so use wisely.
 */
add_filter('coderex_telemetry_report_interval', function($interval) {
    // Change to daily reporting for more frequent updates
    return 'daily';
});

/**
 * Filter: Add custom system information
 * 
 * You can add plugin-specific system information to all events.
 * This is useful for tracking plugin-specific configuration or environment details.
 */
add_filter('coderex_telemetry_system_info', function($info) {
    // Add custom fields to system info
    $info['total_courses'] = wp_count_posts('course')->publish;
    $info['total_students'] = count_users()['total_users'];
    $info['lms_mode'] = get_option('creator_lms_mode', 'standard');
    
    return $info;
});

/**
 * Step 7: Using helper functions (alternative approach)
 * 
 * The SDK provides global helper functions for convenience.
 * These are useful when you don't want to pass the client instance around.
 */

// Alternative way to track events using the helper function
function track_course_completion($course_id, $user_id) {
    // The coderex_telemetry_track() helper function is available globally
    coderex_telemetry_track('course_completed', [
        'course_id' => $course_id,
        'user_id' => $user_id,
        'completion_time' => current_time('mysql'),
    ]);
}

// Example usage
add_action('creator_lms_course_completed', 'track_course_completion', 10, 2);

/**
 * Best Practices:
 * 
 * 1. Event Naming:
 *    - Use snake_case for event names
 *    - Be descriptive but concise: 'course_created' not 'new_course_was_created'
 *    - Use past tense: 'enrolled' not 'enroll'
 * 
 * 2. Properties:
 *    - Only send necessary data
 *    - Avoid sending personal information (emails, names, addresses)
 *    - Use IDs instead of full objects
 *    - Keep property names consistent across events
 * 
 * 3. Performance:
 *    - The SDK is non-blocking and won't slow down your plugin
 *    - Events are sent asynchronously
 *    - Failed requests don't impact user experience
 * 
 * 4. Privacy:
 *    - The SDK respects user consent
 *    - No data is sent until the user opts in
 *    - Users can opt out at any time
 *    - Only collect data you actually need
 * 
 * 5. Testing:
 *    - Test with consent granted and denied
 *    - Verify events appear in your OpenPanel dashboard
 *    - Check that deactivation feedback works
 *    - Ensure weekly reports are being sent
 */

/**
 * Debugging:
 * 
 * If events aren't appearing in OpenPanel:
 * 1. Check that user has granted consent (wp_options: coderex_telemetry_opt_in = 'yes')
 * 2. Verify your API key is correct
 * 3. Check WordPress debug.log for errors (enable WP_DEBUG_LOG)
 * 4. Ensure your server can make outbound HTTPS requests
 * 5. Test with a simple event: $telemetry->track('test_event', ['test' => 'data']);
 */
