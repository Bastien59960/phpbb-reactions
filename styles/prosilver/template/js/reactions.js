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
                        sendReaction(postId, emoji.emoji);
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
    function updateReactionsDisplay(postId, counters, userReaction) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]`);
        if (!postContainer) return;

        const reactionsList = postContainer.querySelector('.post-reactions');
        const moreButton = reactionsList.querySelector('.reaction-more');
        
        // Mettre à jour les réactions existantes et en ajouter si nécessaire
        for (const emoji in counters) {
            const count = counters[emoji];
            let reactionElement = reactionsList.querySelector(`.reaction[data-emoji="${emoji}"]`);

            if (!reactionElement) {
                reactionElement = document.createElement('span');
                reactionElement.classList.add('reaction');
                reactionElement.setAttribute('data-emoji', emoji);
                reactionElement.innerHTML = `${emoji} <span class="count">${count}</span>`;
                reactionElement.addEventListener('click', handleReactionClick);
                
                reactionsList.insertBefore(reactionElement, moreButton);
            } else {
                const countSpan = reactionElement.querySelector('.count');
                if (countSpan) countSpan.textContent = count;
            }
            
            reactionElement.setAttribute('data-count', count);
            reactionElement.title = `${count} réaction${count > 1 ? 's' : ''}`;

            if (emoji === userReaction) {
                reactionElement.classList.add('active');
            } else {
                reactionElement.classList.remove('active');
            }
        }
        
        // Cacher les réactions qui n'ont plus de compte
        reactionsList.querySelectorAll('.reaction[data-emoji]').forEach(el => {
            const emoji = el.getAttribute('data-emoji');
            if (counters[emoji] === undefined || counters[emoji] === 0) {
                 el.classList.remove('active');
                 el.querySelector('.count').textContent = '0';
                 el.setAttribute('data-count', '0');
            }
        });
    }

    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    document.addEventListener('DOMContentLoaded', initReactions);

})();
