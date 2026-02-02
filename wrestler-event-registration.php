<?php
/**
 * Plugin Name: Wrestler Event Registration
 * Plugin URI: https://github.com/yourusername/wrestler-event-registration
 * Description: Event registration system for wrestlers with FluentCRM integration
 * Version: 1.0.0
 * Author: Craig Grella
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wrestler-event-registration
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WER_VERSION', '1.0.0');
define('WER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database table name constant
global $wpdb;
define('WER_TABLE_NAME', $wpdb->prefix . 'wrestler_event_registrations');

/**
 * Activation hook - creates database table
 */
function wer_activate() {
    global $wpdb;
    
    $table_name = WER_TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id bigint(20) UNSIGNED NOT NULL,
        parent_user_id bigint(20) UNSIGNED NOT NULL,
        wrestler_id varchar(100) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'unanswered',
        registered_date datetime DEFAULT CURRENT_TIMESTAMP,
        updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_registration (event_id, wrestler_id),
        KEY parent_user_id (parent_user_id),
        KEY event_id (event_id),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    update_option('wer_db_version', WER_VERSION);
}
register_activation_hook(__FILE__, 'wer_activate');

/**
 * Deactivation hook
 */
function wer_deactivate() {
    // Optional: Add cleanup code here if needed
    // Note: We don't drop the table to preserve data
}
register_deactivation_hook(__FILE__, 'wer_deactivate');

// Include required files
require_once WER_PLUGIN_DIR . 'includes/functions.php';
require_once WER_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once WER_PLUGIN_DIR . 'includes/shortcodes.php';

/**
 * Enqueue styles and scripts
 */
function wer_enqueue_assets() {
    // Only load on single event pages or where shortcode is used
    $load_assets = false;
    
    if (is_singular('event')) {
        $load_assets = true;
    }
    
    // Check for shortcode in content
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wrestler_registration')) {
        $load_assets = true;
    }
    
    if (!$load_assets) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'wrestler-registration-styles',
        WER_PLUGIN_URL . 'assets/css/styles.css',
        [],
        WER_VERSION
    );
    
    // Enqueue jQuery (WordPress includes it)
    wp_enqueue_script('jquery');
    
    // Enqueue custom JS
    wp_enqueue_script(
        'wrestler-registration-script',
        WER_PLUGIN_URL . 'assets/js/registration.js',
        ['jquery'],
        WER_VERSION,
        true
    );
    
    // Pass AJAX URL and nonce to JavaScript
    wp_localize_script('wrestler-registration-script', 'werAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wrestler_registration')
    ]);
}
add_action('wp_enqueue_scripts', 'wer_enqueue_assets');
