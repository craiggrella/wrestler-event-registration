<?php
/**
 * Core functions for wrestler registration
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Get wrestler data from FluentCRM custom fields
 */
function wer_get_parent_wrestlers($user_id) {
    if (!function_exists('FluentCrmApi')) {
        return [];
    }
    
    $contact = FluentCrmApi('contacts')->getContactByUserRef($user_id);
    if (!$contact) {
        return [];
    }
    
    $wrestlers = [];
    
    for ($i = 1; $i <= 5; $i++) {
        $wrestler_id = $contact->{"wrestler_{$i}_id"} ?? '';
        $first_name = $contact->{"wrestler_{$i}_first_name"} ?? '';
        $last_name = $contact->{"wrestler_{$i}_last_name"} ?? '';
        
        $full_name = trim($first_name . ' ' . $last_name);
        
        if (!empty($wrestler_id) && !empty($full_name)) {
            $wrestlers[] = [
                'id' => $wrestler_id,
                'name' => $full_name,
                'first_name' => $first_name,
                'last_name' => $last_name
            ];
        }
    }
    
    return $wrestlers;
}

/**
 * Get registration for a specific wrestler and event
 */
function wer_get_registration($event_id, $wrestler_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . WER_TABLE_NAME . " WHERE event_id = %d AND wrestler_id = %s",
        $event_id,
        $wrestler_id
    ));
}

/**
 * Save or update registration
 */
function wer_save_registration($event_id, $parent_user_id, $wrestler_id, $wrestler_name, $status) {
    global $wpdb;
    
    $existing = wer_get_registration($event_id, $wrestler_id);
    
    if ($existing) {
        $result = $wpdb->update(
            WER_TABLE_NAME,
            [
                'status' => $status,
                'parent_user_id' => $parent_user_id,
                'wrestler_name' => $wrestler_name
            ],
            [
                'event_id' => $event_id,
                'wrestler_id' => $wrestler_id
            ],
            ['%s', '%d', '%s'],
            ['%d', '%s']
        );
    } else {
        $result = $wpdb->insert(
            WER_TABLE_NAME,
            [
                'event_id' => $event_id,
                'parent_user_id' => $parent_user_id,
                'wrestler_id' => $wrestler_id,
                'wrestler_name' => $wrestler_name,
                'status' => $status
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }
    
    return $result !== false;
}

/**
 * Get registration counts for an event
 */
function wer_get_registration_counts($event_id) {
    global $wpdb;
    
    $counts = $wpdb->get_results($wpdb->prepare(
        "SELECT status, COUNT(*) as count FROM " . WER_TABLE_NAME . " WHERE event_id = %d GROUP BY status",
        $event_id
    ), OBJECT_K);
    
    return [
        'attending' => isset($counts['attending']) ? (int)$counts['attending']->count : 0,
        'unanswered' => isset($counts['unanswered']) ? (int)$counts['unanswered']->count : 0,
        'declined' => isset($counts['declined']) ? (int)$counts['declined']->count : 0,
        'total_hosts' => 1
    ];
}
