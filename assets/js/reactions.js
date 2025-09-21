(function($) {
    'use strict';

    /**
     * Attache les événements de clic sur les éléments de réaction.
     */
    function attachEventListeners() {
        $('.reactions').on('click', '.reaction-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $reactionsContainer = $button.closest('.reactions');
            var postId = $reactionsContainer.data('post-id');
            var reactionUnicode = $button.data('reaction');

            // Appel de la fonction pour envoyer la requête AJAX
            sendReaction(postId, reactionUnicode, $reactionsContainer);
        });
    }

    /**
     * Envoie la requête AJAX au contrôleur PHP et met à jour l'interface.
     * @param {number} postId ID du message.
     * @param {string} reactionUnicode Code Unicode de la réaction.
     * @param {jQuery} $container Le conteneur jQuery des réactions pour ce post.
     */
    function sendReaction(postId, reactionUnicode, $container) {
        var url = '{{ U_REACTIONS_HANDLE }}';

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                post_id: postId,
                reaction: reactionUnicode,
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Si la requête a réussi, on met à jour l'affichage
                    updateReactionsDisplay(response.counters, $container);
                } else {
                    // Gérer les erreurs, par exemple afficher un message d'erreur
                    console.error('Erreur du serveur:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    }

    /**
     * Met à jour l'affichage des compteurs et des boutons de réaction.
     * @param {object} counters Les compteurs de réactions reçus du serveur.
     * @param {jQuery} $container Le conteneur jQuery des réactions pour ce post.
     */
    function updateReactionsDisplay(counters, $container) {
        $container.empty(); // On vide le conteneur actuel
        
        // On recrée les boutons de réaction avec les nouveaux compteurs
        for (var unicode in counters) {
            if (counters.hasOwnProperty(unicode)) {
                var count = counters[unicode];
                var html = '<span class="reaction-button" data-reaction="' + unicode + '">' + unicode + ' <span class="reaction-count">' + count + '</span></span>';
                $container.append(html);
            }
        }
    }

    $(document).ready(function() {
        attachEventListeners();
    });
})(jQuery);
