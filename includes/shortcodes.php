<?php
/**
 * Shortcodes for wrestler registration
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Display registration interface
 */
function wer_display_registration($event_id) {
    if (!is_user_logged_in()) {
        return '';
    }
    
    $user_id = get_current_user_id();
    $wrestlers = wer_get_parent_wrestlers($user_id);
    
    if (empty($wrestlers)) {
        return '<p class="wer-no-wrestlers">No wrestlers found for your account.</p>';
    }
    
    ob_start();
    ?>
    <div class="wrestler-registration-section">
        <h3>Register Your Wrestlers</h3>
        
        <?php foreach ($wrestlers as $wrestler): ?>
            <?php 
            $registration = wer_get_registration($event_id, $wrestler['id']);
            $current_status = $registration ? $registration->status : 'unanswered';
            ?>
            
            <div class="wrestler-registration-item" data-wrestler-id="<?php echo esc_attr($wrestler['id']); ?>">
                <h4>Mark registration for <?php echo esc_html($wrestler['name']); ?>:</h4>
                <div class="registration-buttons">
                    <button 
                        class="registration-btn attend-btn <?php echo $current_status === 'attending' ? 'active' : ''; ?>"
                        data-event-id="<?php echo esc_attr($event_id); ?>"
                        data-wrestler-id="<?php echo esc_attr($wrestler['id']); ?>"
                        data-wrestler-name="<?php echo esc_attr($wrestler['name']); ?>"
                        data-status="attending">
                        <span class="icon">✓</span> Attending
                    </button>
                    <button 
                        class="registration-btn decline-btn <?php echo $current_status === 'declined' ? 'active' : ''; ?>"
                        data-event-id="<?php echo esc_attr($event_id); ?>"
                        data-wrestler-id="<?php echo esc_attr($wrestler['id']); ?>"
                        data-wrestler-name="<?php echo esc_attr($wrestler['name']); ?>"
                        data-status="declined">
                        <span class="icon">✕</span> Decline
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="registration-response-section">
            <h3>Responses</h3>
            <?php 
            $counts = wer_get_registration_counts($event_id);
            ?>
            <div class="response-counts">
                <div class="response-item attending">
                    <span class="icon">✓</span>
                    <span class="count"><?php echo $counts['attending']; ?> attending</span>
                </div>
                <div class="response-item declined">
                    <span class="icon">✕</span>
                    <span class="count"><?php echo $counts['declined']; ?> declined</span>
                </div>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Shortcode handler
 */
function wer_registration_shortcode($atts) {
    $atts = shortcode_atts([
        'event_id' => get_the_ID()
    ], $atts);
    
    return wer_display_registration($atts['event_id']);
}
add_shortcode('wrestler_registration', 'wer_registration_shortcode');
