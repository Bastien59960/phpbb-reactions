(function($) {
    'use strict';

    /**
     * Attache les événements de clic sur les éléments de réaction.
     */
    function attachEventListeners() {
        $('.reactions').on('click', '.reaction-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var postId = $button.closest('.reactions').data('post-id');
            var reactionUnicode = $button.data('reaction');

            // Appel de la fonction pour envoyer la requête AJAX
            sendReaction(postId, reactionUnicode);
        });
    }

    /**
     * Envoie la requête AJAX au contrôleur PHP.
     * @param {number} postId ID du message.
     * @param {string} reactionUnicode Code Unicode de la réaction.
     */
    function sendReaction(postId, reactionUnicode) {
        var url = '{{ U_REACTIONS_HANDLE }}'; // Variable de template pour l'URL

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                post_id: postId,
                reaction: reactionUnicode,
            },
            success: function(response) {
                console.log('Réponse du serveur:', response);

                // Ici, on mettra à jour l'interface utilisateur
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    }

    $(document).ready(function() {
        attachEventListeners();
    });
})(jQuery);
