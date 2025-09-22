/**
 * Extension Reactions pour phpBB 3.3.15
 * JavaScript pour la gestion des interactions avec les réactions
 */

(function() {
    'use strict';

    // Configuration des émojis par défaut (toujours visibles)
    const DEFAULT_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];
    
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
        // Attacher les événements aux réactions existantes
        attachReactionEvents();
        
        // Attacher les événements aux boutons "plus"
        attachMoreButtonEvents();
        
        // Fermer les palettes au clic à l'extérieur
        document.addEventListener('click', closeAllPickers);
        
        console.log('Reactions extension initialisée');
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
     * Gestion du clic sur une réaction
     */
    function handleReactionClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const reaction = event.currentTarget;
        const emoji = reaction.getAttribute('data-unicode');
        const postId = getPostIdFromReaction(reaction);
        
        if (!emoji || !postId) {
            console.error('Impossible de déterminer l\'emoji ou l\'ID du post');
            return;
        }

        // Vérifier si l'utilisateur a déjà réagi avec cet emoji
        const isActive = reaction.classList.contains('active');
        
        if (isActive) {
            removeReaction(postId, emoji, reaction);
        } else {
            addReaction(postId, emoji, reaction);
        }
    }

    /**
     * Gestion du clic sur le bouton "+"
     */
    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        const container = button.closest('.post-reactions-container') || button.parentNode;
        
        let picker = container.querySelector('.reaction-picker');
        
        if (!picker) {
            picker = createReactionPicker(postId);
            container.appendChild(picker);
        }
        
        // Fermer les autres palettes ouvertes
        closeAllPickers();
        
        // Afficher cette palette
        showPicker(picker, button);
        currentPickerId = postId;
    }

    /**
     * Créer une palette de sélection d'émojis
     */
    function createReactionPicker(postId) {
        const picker = document.createElement('div');
        picker.className = 'reaction-picker';
        picker.setAttribute('data-post-id', postId);
        
        EMOJI_PALETTE.forEach(emoji => {
            const emojiSpan = document.createElement('span');
            emojiSpan.className = 'reaction';
            emojiSpan.setAttribute('data-unicode', emoji);
            emojiSpan.textContent = emoji;
            emojiSpan.title = emoji;
            
            emojiSpan.addEventListener('click', handlePickerEmojiClick);
            picker.appendChild(emojiSpan);
        });
        
        return picker;
    }

    /**
     * Gestion du clic sur un emoji dans la palette
     */
    function handlePickerEmojiClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const emojiSpan = event.currentTarget;
        const emoji = emojiSpan.getAttribute('data-unicode');
        const picker = emojiSpan.closest('.reaction-picker');
        const postId = picker.getAttribute('data-post-id');
        
        // Ajouter la réaction
        const reactionContainer = picker.parentNode.querySelector('.post-reactions');
        let existingReaction = reactionContainer.querySelector(`[data-unicode="${emoji}"]`);
        
        if (existingReaction && existingReaction.classList.contains('active')) {
            // Déjà réagi avec cet emoji, le retirer
            removeReaction(postId, emoji, existingReaction);
        } else {
            // Ajouter la réaction
            addReaction(postId, emoji, existingReaction);
        }
        
        // Fermer la palette
        closePicker(picker);
    }

    /**
     * Afficher la palette de sélection
     */
    function showPicker(picker, button) {
        // Positionner la palette
        const rect = button.getBoundingClientRect();
        picker.style.left = rect.left + 'px';
        picker.style.top = (rect.bottom + 5) + 'px';
        
        picker.classList.add('show');
    }

    /**
     * Fermer une palette spécifique
     */
    function closePicker(picker) {
        picker.classList.remove('show');
    }

    /**
     * Fermer toutes les palettes ouvertes
     */
    function closeAllPickers() {
        const openPickers = document.querySelectorAll('.reaction-picker.show');
        openPickers.forEach(picker => closePicker(picker));
        currentPickerId = null;
    }

    /**
     * Ajouter une réaction
     */
    function addReaction(postId, emoji, existingReaction) {
        // Requête AJAX pour ajouter la réaction
        fetch(getAjaxUrl(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'action': 'add',
                'post_id': postId,
                'emoji': emoji,
                'token': getSecurityToken()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionDisplay(postId, emoji, data.count, true, existingReaction);
            } else {
                console.error('Erreur lors de l\'ajout de la réaction:', data.error);
                showError(data.error || 'Erreur lors de l\'ajout de la réaction');
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            showError('Erreur de connexion');
        });
    }

    /**
     * Retirer une réaction
     */
    function removeReaction(postId, emoji, reactionElement) {
        // Requête AJAX pour retirer la réaction
        fetch(getAjaxUrl(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'action': 'remove',
                'post_id': postId,
                'emoji': emoji,
                'token': getSecurityToken()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionDisplay(postId, emoji, data.count, false, reactionElement);
            } else {
                console.error('Erreur lors de la suppression de la réaction:', data.error);
                showError(data.error || 'Erreur lors de la suppression de la réaction');
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            showError('Erreur de connexion');
        });
    }

    /**
     * Mettre à jour l'affichage des réactions
     */
    function updateReactionDisplay(postId, emoji, count, isActive, existingReaction) {
        const reactionContainer = document.querySelector(`[data-post-id="${postId}"] .post-reactions`);
        if (!reactionContainer) return;

        let reactionElement = existingReaction || reactionContainer.querySelector(`[data-unicode="${emoji}"]`);

        if (!reactionElement) {
            // Créer une nouvelle réaction
            reactionElement = createReactionElement(emoji, count, isActive);
            
            // Insérer avant le bouton "plus"
            const moreButton = reactionContainer.querySelector('.reaction-more');
            if (moreButton) {
                reactionContainer.insertBefore(reactionElement, moreButton);
            } else {
                reactionContainer.appendChild(reactionElement);
            }
            
            // Animation d'apparition
            reactionElement.classList.add('new');
            setTimeout(() => reactionElement.classList.remove('new'), 300);
            
            // Attacher les événements
            reactionElement.addEventListener('click', handleReactionClick);
            reactionElement.addEventListener('mouseenter', showTooltip);
            reactionElement.addEventListener('mouseleave', hideTooltip);
        } else {
            // Mettre à jour l'existant
            updateReactionElement(reactionElement, count, isActive);
        }
    }

    /**
     * Créer un élément de réaction
     */
    function createReactionElement(emoji, count, isActive) {
        const reaction = document.createElement('span');
        reaction.className = 'reaction';
        reaction.setAttribute('data-unicode', emoji);
        reaction.setAttribute('data-count', count);
        
        if (isActive) {
            reaction.classList.add('active');
        }
        
        reaction.innerHTML = `${emoji} <span class="count">${count}</span>`;
        
        return reaction;
    }

    /**
     * Mettre à jour un élément de réaction existant
     */
    function updateReactionElement(element, count, isActive) {
        const countSpan = element.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = count;
        }
        
        element.setAttribute('data-count', count);
        
        if (isActive) {
            element.classList.add('active');
        } else {
            element.classList.remove('active');
        }
        
        // Masquer si count = 0 et pas actif
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

    /**
     * Obtenir l'ID du post à partir d'un élément de réaction
     */
    function getPostIdFromReaction(element) {
        // Chercher dans les attributs data ou dans l'ID parent
        const post = element.closest('[data-post-id]') || element.closest('[id^="p"]');
        if (post) {
            return post.getAttribute('data-post-id') || post.id.replace('p', '');
        }
        return null;
    }

    /**
     * Obtenir l'URL AJAX
     */
    function getAjaxUrl() {
        return window.REACTIONS_AJAX_URL || phpbb.append_sid('app.php/reactions/ajax', '', true, '');
    }

    /**
     * Obtenir le token de sécurité
     */
    function getSecurityToken() {
        const tokenInput = document.querySelector('input[name="form_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    /**
     * Afficher une erreur à l'utilisateur
     */
    function showError(message) {
        // Créer ou utiliser un système d'alerte existant
        const alertDiv = document.createElement('div');
        alertDiv.className = 'error-message';
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #d32f2f;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            z-index: 10000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;
        alertDiv.textContent = message;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

    /**
     * Afficher un tooltip
     */
    function showTooltip(event) {
        const reaction = event.currentTarget;
        const count = reaction.getAttribute('data-count') || '0';
        const emoji = reaction.getAttribute('data-unicode');
        
        if (count > 0) {
            const tooltip = document.createElement('div');
            tooltip.className = 'reaction-tooltip';
            tooltip.textContent = `${count} réaction${count > 1 ? 's' : ''} ${emoji}`;
            
            document.body.appendChild(tooltip);
            
            const rect = reaction.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            
            setTimeout(() => tooltip.classList.add('show'), 10);
            
            reaction.tooltipElement = tooltip;
        }
    }

    /**
     * Masquer un tooltip
     */
    function hideTooltip(event) {
        const reaction = event.currentTarget;
        if (reaction.tooltipElement) {
            reaction.tooltipElement.remove();
            reaction.tooltipElement = null;
        }
    }

    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReactions);
    } else {
        initReactions();
    }

})();
