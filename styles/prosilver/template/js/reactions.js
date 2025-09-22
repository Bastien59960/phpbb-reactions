/**
 * Extension Reactions pour phpBB 3.3.15
 * JavaScript pour la gestion des interactions avec les réactions
 */

(function() {
    'use strict';

    // Configuration des émojis par défaut (toujours visibles)
    const DEFAULT_EMOJIS = ['👍', '❤️', '😂', '😮', '😢'];
    
    // Palette complète d'émojis (pour le sélecteur étendu)
    const EMOJI_PALETTE = [
        '😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊',
        '😋', '😎', '😍', '😘', '🥰', '😗', '😙', '😚', '😇', '🥳',
        '😈', '👿', '😠', '😡', '🤬', '😱', '😰', '😨', '😧', '😦',
        '😮', '😯', '😲', '🤯', '😳', '🥺', '😢', '😭', '😤', '😪',
        '👍', '👎', '👌', '✌️', '🤞', '👏', '🙌', '👐', '🤲', '🙏',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
        '💯', '💥', '💢', '💨', '💫', '⭐', '🌟', '✨', '⚡', '🔥'
    ];

    let currentPickerId = null;

    /**
     * Initialisation des réactions au chargement de la page
     */
    function initReactions() {
        // Forcer l’affichage des réactions par défaut
        ensureDefaultReactions();

        // Attacher les événements aux réactions existantes
        attachReactionEvents();
        
        // Attacher les événements aux boutons "plus"
        attachMoreButtonEvents();
        
        // Fermer les palettes au clic à l'extérieur
        document.addEventListener('click', closeAllPickers);
        
        console.log('Reactions extension initialisée');
    }

    /**
     * S’assurer que les émojis par défaut sont toujours affichés
     */
    function ensureDefaultReactions() {
        document.querySelectorAll('.post-reactions-container').forEach(container => {
            const reactionContainer = container.querySelector('.post-reactions');
            if (!reactionContainer) return;

            const postId = getPostIdFromReaction(container);

            DEFAULT_EMOJIS.forEach(emoji => {
                let existing = reactionContainer.querySelector(`[data-unicode="${emoji}"]`);
                if (!existing) {
                    // Crée l’élément avec compteur "00"
                    const reaction = createReactionElement(emoji, 0, false, true);
                    const moreBtn = reactionContainer.querySelector('.reaction-more');
                    if (moreBtn) {
                        reactionContainer.insertBefore(reaction, moreBtn);
                    } else {
                        reactionContainer.appendChild(reaction);
                    }
                    // Attache les événements
                    reaction.addEventListener('click', handleReactionClick);
                    reaction.addEventListener('mouseenter', showTooltip);
                    reaction.addEventListener('mouseleave', hideTooltip);
                }
            });
        });
    }

    /**
     * Attacher les événements aux réactions existantes
     */
    function attachReactionEvents() {
        const reactions = document.querySelectorAll('.post-reactions .reaction');
        reactions.forEach(reaction => {
            reaction.addEventListener('click', handleReactionClick);
            reaction.addEventListener('mouseenter', showTooltip);
            reaction.addEventListener('mouseleave', hideTooltip);
        });
    }

    /**
     * Attacher les événements aux boutons "+"
     */
    function attachMoreButtonEvents() {
        const moreButtons = document.querySelectorAll('.reaction-more');
        moreButtons.forEach(button => {
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    /**
     * Créer un élément de réaction
     * @param {string} emoji 
     * @param {number} count 
     * @param {boolean} isActive 
     * @param {boolean} isDefault 
     */
    function createReactionElement(emoji, count, isActive, isDefault = false) {
        const reaction = document.createElement('span');
        reaction.className = 'reaction';
        reaction.setAttribute('data-unicode', emoji);
        reaction.setAttribute('data-count', count);
        
        if (isActive) {
            reaction.classList.add('active');
        }
        if (isDefault) {
            reaction.classList.add('default-reaction');
        }

        // Si compteur = 0 → afficher "00"
        const displayCount = count === 0 ? '00' : count;
        reaction.innerHTML = `${emoji} <span class="count">${displayCount}</span>`;
        
        return reaction;
    }

    /**
     * Mettre à jour un élément de réaction existant
     */
    function updateReactionElement(element, count, isActive) {
        const countSpan = element.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = count === 0 ? '00' : count;
        }
        
        element.setAttribute('data-count', count);
        
        if (isActive) {
            element.classList.add('active');
        } else {
            element.classList.remove('active');
        }

        // ⚠️ Ne pas supprimer les réactions par défaut
        if (!element.classList.contains('default-reaction')) {
            if (count === 0 && !isActive) {
                element.classList.add('removing');
                setTimeout(() => {
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                }, 200);
            } else {
                element.style.display = '';
            }
        }
    }

    // ... (tout le reste du code inchangé : addReaction, removeReaction, picker, AJAX, tooltips, etc.)

    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReactions);
    } else {
        initReactions();
    }

})();
