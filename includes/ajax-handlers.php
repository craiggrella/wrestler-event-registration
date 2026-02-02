<?php
/**
 * AJAX handlers for wrestler registration
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Handle registration AJAX request
 */
function wer_handle_registration_ajax() {
    check_ajax_referer('wrestler_registration', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Must be logged in']);
    }
    
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $wrestler_id = isset($_POST['wrestler_id']) ? sanitize_text_field($_POST['wrestler_id']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $user_id = get_current_user_id();
    
    if (!$event_id || !$wrestler_id) {
        wp_send_json_error(['message' => 'Missing required fields']);
    }
    
    if (!in_array($status, ['attending', 'declined'])) {
        wp_send_json_error(['message' => 'Invalid status']);
    }
    
    $result = wer_save_registration($event_id, $user_id, $wrestler_id, $status);
    
    if ($result) {
        $counts = wer_get_registration_counts($event_id);
        $wrestlers_by_status = wer_get_wrestlers_by_status($event_id);
        
        wp_send_json_success([
            'message' => 'Registration updated',
            'counts' => $counts,
            'wrestlers' => $wrestlers_by_status
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to save registration']);
    }
}
add_action('wp_ajax_wrestler_registration', 'wer_handle_registration_ajax');
