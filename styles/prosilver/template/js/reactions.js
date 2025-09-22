(function () {
    'use strict';

    let currentPicker = null;

    // ---------- Initialisation ----------
    function initReactions() {
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
    }

    function attachReactionEvents() {
        document.querySelectorAll('.post-reactions .reaction').forEach(reaction => {
            reaction.removeEventListener('click', handleReactionClick);
            reaction.addEventListener('click', handleReactionClick);
        });
    }

    function attachMoreButtonEvents() {
        document.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

function handleReactionClick(event) {
    event.preventDefault();
    event.stopPropagation();

    const el = event.currentTarget;
    const emoji = el.getAttribute('data-emoji');
    const postId = getPostIdFromReaction(el);
    if (!emoji || !postId) return;

    // Déterminer l'action : si l'élément a la classe 'active', on retire la réaction.
    const action = el.classList.contains('active') ? 'remove' : 'add';

    sendReaction(postId, emoji, action); // On passe l'action à sendReaction
}

    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        closeAllPickers();

        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        if (!postId) return;

        // Création du picker flottant
        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        currentPicker = picker;

        // Charger emojis.json depuis prosilver
        fetch('/forum/ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => console.error('Erreur de chargement emojis.json', err));

        document.body.appendChild(picker);

        // Positionnement sous le bouton
        const rect = button.getBoundingClientRect();
        picker.style.position = 'absolute';
        picker.style.top = `${rect.bottom + window.scrollY}px`;
        picker.style.left = `${rect.left + window.scrollX}px`;
        picker.style.zIndex = 10000;
    }

    function buildEmojiPicker(picker, postId, emojiData) {
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            picker.appendChild(catTitle);

            Object.values(subcategories).forEach(emojis => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emoji => {
                    const cell = document.createElement('div');
                    cell.classList.add('emoji-cell');
                    cell.textContent = emoji.emoji;
                    cell.title = emoji.name;
                    cell.addEventListener('click', () => {
                        sendReaction(postId, emoji.emoji, 'add');
                        closeAllPickers();
                    });
                    grid.appendChild(cell);
                });
                picker.appendChild(grid);
            });
        });
    }

    function closeAllPickers() {
        if (currentPicker) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

   // reactions.js

/**
 * Envoie la requête AJAX au serveur.
 * @param {int} postId L'ID du post
 * @param {string} emoji L'emoji
 * @param {string} action L'action à effectuer ('add' ou 'remove')
 */
function sendReaction(postId, emoji, action) {
    const url = window.REACTIONS_AJAX_URL; // Ceci est correct

    fetch(url, {
        method: 'POST',
        // On envoie en 'form-data' car le contrôleur PHP lit les variables POST directement
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams({
            'action': action,
            'post_id': postId,
            'emoji': emoji
        })
    })
    .then(res => res.json())
    .then(data => {
        // La structure de la réponse a changé, on appelle la nouvelle fonction de mise à jour
        if (data.success) {
            updateSingleReactionDisplay(postId, emoji, data.count, data.user_reacted);
        } else {
            console.error('Erreur serveur:', data.error);
            alert('Erreur: ' + data.error); // Utiliser data.error comme défini dans ajax.php
        }
    })
    .catch(err => console.error('Erreur AJAX:', err));
}
    /**
     * Met à jour l'affichage des réactions après une réponse réussie.
     * @param {int} postId L'ID du post
     * @param {object} counters Les nouveaux comptes de réactions
     * @param {string} userReaction L'emoji que l'utilisateur a actuellement
     */
 /**
 * Met à jour l'affichage d'une seule réaction après une réponse réussie.
 * @param {int} postId L'ID du post
 * @param {string} emoji L'émoji concerné
 * @param {int} newCount Le nouveau total pour cet émoji
 * @param {boolean} userHasReacted Si l'utilisateur a réagi avec cet émoji
 */
function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
    const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]`);
    if (!postContainer) return;

    const reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]`);
    if (!reactionElement) return; // Ne devrait pas arriver sur un clic

    const countSpan = reactionElement.querySelector('.count');

    // Mettre à jour le compteur
    if (countSpan) {
        countSpan.textContent = newCount;
    }
    
    reactionElement.setAttribute('data-count', newCount);
    reactionElement.title = `${newCount} réaction${newCount > 1 ? 's' : ''}`;

    // Gérer l'état "actif" pour l'utilisateur
    if (userHasReacted) {
        reactionElement.classList.add('active');
    } else {
        reactionElement.classList.remove('active');
    }
    
    // On cache l'élément si le compteur tombe à zéro (sauf si c'est une réaction par défaut)
    if (newCount === 0 && !reactionElement.hasAttribute('data-is-default')) {
        reactionElement.style.display = 'none';
    } else {
        reactionElement.style.display = '';
    }
}
    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    document.addEventListener('DOMContentLoaded', initReactions);

})();
