(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('.reactions-container').on('click', '.reaction-btn', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $container = $btn.closest('.reactions-container');
            const postId = $container.data('post-id');
            const reactionUnicode = $btn.data('reaction');
            
            // Send AJAX request
            $.ajax({
                url: U_REACTIONS_HANDLE,
                method: 'POST',
                data: {
                    post_id: postId,
                    reaction_unicode: reactionUnicode
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Update the UI
                        updateReactionsUI($container, response);
                    } else {
                        console.error('Error:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });
        
        function updateReactionsUI($container, response) {
            // Update all reaction counts
            $.each(response.counters, function(unicode, count) {
                const $btn = $container.find('[data-reaction="' + unicode + '"]');
                $btn.find('.reaction-count').text(count);
                $btn.toggleClass('reaction-user', response.user_reaction && response.user_reaction.reaction_unicode === unicode);
            });
            
            // Remove user class from all if no reaction
            if (!response.user_reaction) {
                $container.find('.reaction-btn').removeClass('reaction-user');
            }
        }
    });
})(jQuery);
