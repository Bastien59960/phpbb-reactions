/* global jQuery, window */
(function($) {
    'use strict';

    /**
     * Gère les clics sur les réactions et la palette d'emojis.
     */
    function setupReactions() {
        // Gère l'affichage/la masquage de la palette de réactions
        $('.reaction-more').on('click', function(e) {
            e.preventDefault();
            const container = $(this).closest('.post-reactions-container');
            const picker = container.find('.reaction-picker');
            
            // Masquer les autres palettes
            $('.reaction-picker.show').not(picker).removeClass('show');
            
            picker.toggleClass('show');
        });

        // Gère le clic sur un emoji dans la palette
        $('.reaction-picker .reaction').on('click', function(e) {
            e.preventDefault();
            const emoji = $(this).data('emoji');
            const postContainer = $(this).closest('.post-reactions-container');
            const postId = postContainer.data('post-id');

            if (!postId || !emoji) {
                console.error('Missing post ID or emoji.');
                return;
            }

            // Fermer la palette
            postContainer.find('.reaction-picker').removeClass('show');
            
            // Appel AJAX pour la réaction
            sendReaction(postId, emoji, postContainer);
        });

        // Gère le clic sur une réaction existante
        $('.post-reactions .reaction').on('click', function(e) {
            e.preventDefault();
            const emoji = $(this).data('emoji');
            const postContainer = $(this).closest('.post-reactions-container');
            const postId = postContainer.data('post-id');

            if (!postId || !emoji) {
                console.error('Missing post ID or emoji.');
                return;
            }

            // Appel AJAX pour la réaction
            sendReaction(postId, emoji, postContainer);
        });
    }

    /**
     * Envoie la requête AJAX au serveur.
     * @param {int} postId L'ID du post
     * @param {string} emoji L'emoji
     * @param {jQuery} postContainer Le conteneur .post-reactions-container
     */
    function sendReaction(postId, emoji, postContainer) {
        $.ajax({
            url: window.REACTIONS_AJAX_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                post_id: postId,
                reaction_emoji: emoji
            },
            success: function(response) {
                if (response.status === 'success') {
                    updateReactionsDisplay(response.post_id, response.counters, response.user_reaction, postContainer);
                } else {
                    console.error('Error from server: ', response.message);
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error: ' + status, error);
                alert('An error occurred. Please try again.');
            }
        });
    }

    /**
     * Met à jour l'affichage des réactions après une réponse réussie.
     * @param {int} postId L'ID du post
     * @param {object} counters Les nouveaux comptes de réactions
     * @param {string} userReaction L'emoji que l'utilisateur a actuellement
     * @param {jQuery} postContainer Le conteneur .post-reactions-container
     */
    function updateReactionsDisplay(postId, counters, userReaction, postContainer) {
        const reactionsList = postContainer.find('.post-reactions');
        const moreButton = reactionsList.find('.reaction-more');
        
        // Mettre à jour les réactions existantes
        reactionsList.find('.reaction[data-emoji]').each(function() {
            const reactionElement = $(this);
            const emoji = reactionElement.data('emoji');
            const newCount = counters[emoji] || 0;
            
            if (newCount > 0) {
                reactionElement.show();
                reactionElement.find('.count').text(newCount);
                reactionElement.data('count', newCount);
                reactionElement.attr('title', newCount + ' réaction' + (newCount > 1 ? 's' : ''));
            } else {
                reactionElement.hide();
            }

            if (emoji === userReaction) {
                reactionElement.addClass('active');
            } else {
                reactionElement.removeClass('active');
            }
        });
    }

    // Initialisation
    $(document).ready(function() {
        setupReactions();
    });

})(jQuery);
