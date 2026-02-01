jQuery(document).ready(function($) {
    $('.registration-btn').on('click', function() {
        var $btn = $(this);
        var $container = $btn.closest('.wrestler-registration-item');
        
        // Disable buttons during request
        $container.find('.registration-btn').prop('disabled', true);
        
        $.ajax({
            url: werAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wrestler_registration',
                nonce: werAjax.nonce,
                event_id: $btn.data('event-id'),
                wrestler_id: $btn.data('wrestler-id'),
                status: $btn.data('status')
            },
            success: function(response) {
                if (response.success) {
                    // Update button states
                    $container.find('.registration-btn').removeClass('active');
                    $btn.addClass('active');
                    
                    // Update counts
                    var counts = response.data.counts;
                    $('.response-item.attending .count').text(counts.attending + ' attending');
                    $('.response-item.unanswered .count').text(counts.unanswered + ' unanswered');
                    $('.response-item.declined .count').text(counts.declined + ' declined');
                } else {
                    alert('Registration failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
            },
            complete: function() {
                // Re-enable buttons
                $container.find('.registration-btn').prop('disabled', false);
            }
        });
    });
});
