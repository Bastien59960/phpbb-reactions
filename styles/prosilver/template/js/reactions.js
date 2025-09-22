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
        const emoji = el.getAttribute('data-emoji'); // CORRECTION: Utilisation de data-emoji
        const postId = getPostIdFromReaction(el);
        if (!emoji || !postId) return;

        // Appel de la fonction pour envoyer la requête
        sendReaction(postId, emoji);
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
                        sendReaction(postId, emoji.emoji); // Appel direct de sendReaction
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

    /**
     * Envoie la requête AJAX au serveur.
     * @param {int} postId L'ID du post
     * @param {string} emoji L'emoji
     */
    function sendReaction(postId, emoji) {
        const url = window.REACTIONS_AJAX_URL; // Utilisation de l'URL fournie par PHP
        
        // Données au format de formulaire
        const formData = new URLSearchParams();
        formData.append('post_id', postId);
        formData.append('reaction_emoji', emoji); // CORRECTION: Variable envoyée au serveur

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                updateReactionsDisplay(postId, data.counters, data.user_reaction);
            } else {
                console.error('Erreur serveur:', data.message);
                alert('Erreur: ' + data.message);
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
                // Créer un nouvel élément de réaction s'il n'existe pas
                reactionElement = document.createElement('span');
                reactionElement.classList.add('reaction');
                reactionElement.setAttribute('data-emoji', emoji);
                reactionElement.innerHTML = `${emoji} <span class="count">${count}</span>`;
                reactionElement.addEventListener('click', handleReactionClick);
                
                reactionsList.insertBefore(reactionElement, moreButton);
            } else {
                // Mettre à jour le compteur d'une réaction existante
                const countSpan = reactionElement.querySelector('.count');
                if (countSpan) countSpan.textContent = count;
            }
            
            // Mettre à jour les attributs
            reactionElement.setAttribute('data-count', count);
            reactionElement.title = `${count} réaction${count > 1 ? 's' : ''}`;

            // Gérer la classe 'active'
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
