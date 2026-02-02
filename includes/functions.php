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
    
    // Get custom fields the correct way for FluentCRM
    $custom_fields = (array) $contact->custom_fields();
    
    $wrestlers = [];
    
    // Loop through up to 5 wrestlers
    for ($i = 1; $i <= 5; $i++) {
        $wrestler_id = $custom_fields["wrestler_{$i}_id"] ?? '';
        $first_name = $custom_fields["wrestler_{$i}_first_name"] ?? '';
        $last_name = $custom_fields["wrestler_{$i}_last_name"] ?? '';
        $weight = $custom_fields["wrestler_{$i}_weight"] ?? '';
        
        // Combine first and last name
        $full_name = trim($first_name . ' ' . $last_name);
        
        if (!empty($wrestler_id) && !empty($full_name)) {
            $wrestlers[] = [
                'id' => $wrestler_id,
                'name' => $full_name,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'weight' => $weight
            ];
        }
    }
    
    return $wrestlers;
}

/**
 * Get ALL wrestlers from ALL users in FluentCRM
 */
function wer_get_all_wrestlers() {
    if (!function_exists('FluentCrmApi')) {
        return [];
    }
    
    $contacts = FluentCrmApi('contacts')->all();
    $all_wrestlers = [];
    
    foreach ($contacts as $contact) {
        if (!$contact->user_id) {
            continue;
        }
        
        $custom_fields = (array) $contact->custom_fields();
        
        // Loop through up to 5 wrestlers per parent
        for ($i = 1; $i <= 5; $i++) {
            $wrestler_id = $custom_fields["wrestler_{$i}_id"] ?? '';
            $first_name = $custom_fields["wrestler_{$i}_first_name"] ?? '';
            $last_name = $custom_fields["wrestler_{$i}_last_name"] ?? '';
            $weight = $custom_fields["wrestler_{$i}_weight"] ?? '';
            
            $full_name = trim($first_name . ' ' . $last_name);
            
            if (!empty($wrestler_id) && !empty($full_name)) {
                $all_wrestlers[$wrestler_id] = [
                    'id' => $wrestler_id,
                    'name' => $full_name,
                    'weight' => $weight,
                    'user_id' => $contact->user_id
                ];
            }
        }
    }
    
    return $all_wrestlers;
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
function wer_save_registration($event_id, $parent_user_id, $wrestler_id, $status) {
    global $wpdb;
    
    $existing = wer_get_registration($event_id, $wrestler_id);
    
    if ($existing) {
        $result = $wpdb->update(
            WER_TABLE_NAME,
            [
                'status' => $status,
                'parent_user_id' => $parent_user_id
            ],
            [
                'event_id' => $event_id,
                'wrestler_id' => $wrestler_id
            ],
            ['%s', '%d'],
            ['%d', '%s']
        );
    } else {
        $result = $wpdb->insert(
            WER_TABLE_NAME,
            [
                'event_id' => $event_id,
                'parent_user_id' => $parent_user_id,
                'wrestler_id' => $wrestler_id,
                'status' => $status
            ],
            ['%d', '%d', '%s', '%s']
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
        'declined' => isset($counts['declined']) ? (int)$counts['declined']->count : 0
    ];
}

/**
 * Get wrestlers grouped by status for an event
 */
function wer_get_wrestlers_by_status($event_id) {
    global $wpdb;
    
    // Get all wrestlers from FluentCRM
    $all_wrestlers = wer_get_all_wrestlers();
    
    // Get registrations from database
    $registrations = $wpdb->get_results($wpdb->prepare(
        "SELECT wrestler_id, status FROM " . WER_TABLE_NAME . " WHERE event_id = %d",
        $event_id
    ), OBJECT_K);
    
    $grouped = [
        'attending' => [],
        'unanswered' => [],
        'declined' => []
    ];
    
    // Loop through all wrestlers and categorize them
    foreach ($all_wrestlers as $wrestler_id => $wrestler_data) {
        if (isset($registrations[$wrestler_id])) {
            // Wrestler has a registration
            $status = $registrations[$wrestler_id]->status;
        } else {
            // No registration = unanswered
            $status = 'unanswered';
        }
        
        if (isset($grouped[$status])) {
            $grouped[$status][] = (object)[
                'wrestler_name' => $wrestler_data['name'],
                'wrestler_weight' => $wrestler_data['weight']
            ];
        }
    }
    
    // Sort each group alphabetically
    foreach ($grouped as $status => $wrestlers) {
        usort($grouped[$status], function($a, $b) {
            return strcmp($a->wrestler_name, $b->wrestler_name);
        });
    }
    
    return $grouped;
}
