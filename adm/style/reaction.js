// Reactions JavaScript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle reaction clicks
        $(document).on('click', '.reaction-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.closest('.reactions-container').data('post-id');
            var reaction = $btn.data('reaction');
            var actionUrl = $('#reactions-action-url').val(); // From template variable
            
            if (!actionUrl) {
                console.error('Reactions: Action URL not found');
                return;
            }
            
            // Disable button during request
            $btn.prop('disabled', true);
            
            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: {
                    post_id: postId,
                    reaction_unicode: reaction
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        updateReactionCounts(response.post_id, response.counters, response.user_reaction);
                    } else {
                        console.error('Reactions error:', response.message);
                        alert(response.message || 'Une erreur est survenue');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Erreur de connexion. Veuillez réessayer.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    
    function updateReactionCounts(postId, counters, userReaction) {
        var $container = $('.reactions-container[data-post-id="' + postId + '"]');
        
        $container.find('.reaction-btn').each(function() {
            var $btn = $(this);
            var reaction = $btn.data('reaction');
            var count = counters[reaction] || 0;
            
            // Update count
            $btn.find('.reaction-count').text(count);
            
            // Update user reaction state
            if (userReaction && userReaction.reaction_unicode === reaction) {
                $btn.addClass('reaction-user');
            } else {
                $btn.removeClass('reaction-user');
            }
            
            // Hide/show button based on count
            if (count > 0) {
                $btn.show();
            } else {
                $btn.hide();
            }
        });
    }
    
})(jQuery);
