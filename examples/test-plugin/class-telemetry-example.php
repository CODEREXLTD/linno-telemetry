<?php
/**
 * Class-based Telemetry Example
 *
 * This file demonstrates how to use the CodeRex Telemetry SDK in a class-based approach.
 * This is an alternative to the functional approach in test-telemetry-plugin.php
 *
 * @package Test_Telemetry_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Telemetry_Example
 *
 * Example class showing how to integrate telemetry tracking in an OOP style.
 *
 * @since 1.0.0
 */
class Telemetry_Example {
    
    /**
     * Telemetry client instance
     *
     * @var \CodeRex\Telemetry\Client
     */
    private $telemetry;
    
    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;
    
    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file;
    
    /**
     * Constructor
     *
     * @param string $api_key OpenPanel API key
     * @param string $api_secret OpenPanel API secret
     * @param string $plugin_name Plugin name
     * @param string $plugin_file Plugin file path
     */
    public function __construct($api_key, $api_secret, $plugin_name, $plugin_file) {
        $this->plugin_name = $plugin_name;
        $this->plugin_file = $plugin_file;
        
        try {
            // Initialize the telemetry client
            $this->telemetry = new \CodeRex\Telemetry\Client(
                $api_key,
                $api_secret,
                $plugin_name,
                $plugin_file
            );
            
            // Initialize the SDK (sets up hooks and background reporting)
            $this->telemetry->init();
            
        } catch (Exception $e) {
            error_log('Telemetry Example: Failed to initialize - ' . $e->getMessage());
        }
    }
    
    /**
     * Track a custom event
     *
     * @param string $event_name Event name
     * @param array  $properties Event properties (optional)
     * @return bool True on success, false on failure
     */
    public function track($event_name, $properties = []) {
        if (!$this->telemetry) {
            return false;
        }
        
        return $this->telemetry->track($event_name, $properties);
    }
    
    /**
     * Track user action
     *
     * Example method showing how to track a specific user action
     *
     * @param string $action Action name
     * @param array  $context Additional context
     * @return bool
     */
    public function track_user_action($action, $context = []) {
        $properties = array_merge([
            'action' => $action,
            'timestamp' => current_time('mysql'),
        ], $context);
        
        return $this->track('user_action', $properties);
    }
    
    /**
     * Track feature usage
     *
     * Example method for tracking feature usage
     *
     * @param string $feature_name Feature name
     * @param array  $metadata Additional metadata
     * @return bool
     */
    public function track_feature_usage($feature_name, $metadata = []) {
        $properties = array_merge([
            'feature' => $feature_name,
            'used_at' => current_time('mysql'),
        ], $metadata);
        
        return $this->track('feature_used', $properties);
    }
    
    /**
     * Track error
     *
     * Example method for tracking errors
     *
     * @param string $error_message Error message
     * @param string $error_code Error code (optional)
     * @param array  $context Additional context
     * @return bool
     */
    public function track_error($error_message, $error_code = '', $context = []) {
        $properties = array_merge([
            'error_message' => $error_message,
            'error_code' => $error_code,
            'occurred_at' => current_time('mysql'),
        ], $context);
        
        return $this->track('error_occurred', $properties);
    }
    
    /**
     * Track page view
     *
     * Example method for tracking admin page views
     *
     * @param string $page_slug Page slug
     * @return bool
     */
    public function track_page_view($page_slug) {
        return $this->track('page_viewed', [
            'page' => $page_slug,
            'viewed_at' => current_time('mysql'),
        ]);
    }
}

/**
 * Example usage in a WordPress plugin
 */

// Initialize the telemetry tracker
$telemetry_tracker = new Telemetry_Example(
    'op_4d049e93ece5870c534a',      // API key
    'sec_4d049e93ece5870c534a',     // API secret
    'My Awesome Plugin',             // Plugin name
    __FILE__                         // Plugin file
);

// Example 1: Track a simple event
add_action('init', function() use ($telemetry_tracker) {
    $telemetry_tracker->track('plugin_loaded', [
        'version' => '1.0.0',
        'environment' => wp_get_environment_type(),
    ]);
});

// Example 2: Track user action
add_action('wp_login', function($user_login, $user) use ($telemetry_tracker) {
    $telemetry_tracker->track_user_action('user_login', [
        'user_role' => $user->roles[0] ?? 'unknown',
    ]);
}, 10, 2);

// Example 3: Track feature usage
add_action('my_plugin_export_data', function($export_type) use ($telemetry_tracker) {
    $telemetry_tracker->track_feature_usage('data_export', [
        'export_type' => $export_type,
        'file_format' => 'csv',
    ]);
});

// Example 4: Track errors
add_action('my_plugin_error', function($error) use ($telemetry_tracker) {
    $telemetry_tracker->track_error(
        $error->get_error_message(),
        $error->get_error_code(),
        [
            'context' => 'payment_processing',
            'severity' => 'high',
        ]
    );
});

// Example 5: Track admin page views
add_action('admin_init', function() use ($telemetry_tracker) {
    if (isset($_GET['page']) && strpos($_GET['page'], 'my-plugin') === 0) {
        $telemetry_tracker->track_page_view(sanitize_text_field($_GET['page']));
    }
});

// Example 6: Track custom post type creation
add_action('save_post_my_custom_type', function($post_id, $post) use ($telemetry_tracker) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $telemetry_tracker->track('custom_post_created', [
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'has_featured_image' => has_post_thumbnail($post_id),
    ]);
}, 10, 2);

// Example 7: Track settings changes
add_action('update_option_my_plugin_settings', function($old_value, $new_value) use ($telemetry_tracker) {
    $telemetry_tracker->track('settings_updated', [
        'changed_fields' => array_keys(array_diff_assoc($new_value, $old_value)),
    ]);
}, 10, 2);

// Example 8: Track AJAX actions
add_action('wp_ajax_my_plugin_action', function() use ($telemetry_tracker) {
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'unknown';
    
    $telemetry_tracker->track('ajax_action', [
        'action_type' => $action_type,
        'is_admin' => current_user_can('manage_options'),
    ]);
    
    // Your AJAX handler code here...
});
