// Reactions JavaScript
(function($) {
    'use strict';

    $(document).ready(function() {
        var actionUrl = $('#reactions-action-url').val();
        
        // Handle reaction clicks on existing reactions
        $(document).on('click', '.reaction-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.reactions-container');
            var postId = $container.data('post-id');
            var reaction = $btn.data('reaction');
            
            sendReaction($container, postId, reaction);
        });

        // Handle clicks on the '+' button to show the picker
        $(document).on('click', '.reaction-add-btn', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.reactions-container');
            $container.find('.reaction-picker-modal').fadeIn(200);
        });
        
        // Handle reaction clicks from the picker
        $(document).on('click', '.reaction-picker-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $container = $btn.closest('.reactions-container');
            var postId = $container.data('post-id');
            var reaction = $btn.data('reaction');
            
            $container.find('.reaction-picker-modal').fadeOut(200);
            sendReaction($container, postId, reaction);
        });
        
        // Handle closing the picker
        $(document).on('click', '.reaction-picker-close-btn', function(e) {
            e.preventDefault();
            $(this).closest('.reaction-picker-modal').fadeOut(200);
        });
        
        function sendReaction($container, postId, reaction) {
            if (!actionUrl) {
                console.error('Reactions: Action URL not found');
                return;
            }
            
            // Disable all buttons in container during request
            $container.find('button').prop('disabled', true);
            
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
                    $container.find('button').prop('disabled', false);
                }
            });
        }
        
        function updateReactionCounts(postId, counters, userReaction) {
            var $container = $('.reactions-container[data-post-id="' + postId + '"]');
            var $reactionsList = $container.find('.reactions-list');
            
            // Clear current reactions
            $reactionsList.find('.reaction-btn').remove();
            
            // Add or update buttons for each reaction
            for (var unicode in counters) {
                if (counters.hasOwnProperty(unicode)) {
                    var count = counters[unicode];
                    var isUserReaction = (unicode === userReaction);
                    
                    var $btn = $('<button class="reaction-btn" data-reaction="' + unicode + '">' +
                                     '<span class="reaction-emoji">' + unicode + '</span> ' +
                                     '<span class="reaction-count">' + count + '</span>' +
                                 '</button>');
                    
                    if (isUserReaction) {
                        $btn.addClass('reaction-user');
                        $btn.attr('data-user-reaction', '1');
                    }
                    
                    $reactionsList.prepend($btn);
                }
            }
        }
    });
})(jQuery);
