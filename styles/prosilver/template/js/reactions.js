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

        // Charger emojis.json depuis prosilver - utiliser un chemin relatif
        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => {
                console.error('Erreur de chargement emojis.json', err);
                // Fallback avec quelques emojis de base
                buildFallbackPicker(picker, postId);
            });

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

    function buildFallbackPicker(picker, postId) {
        const fallbackEmojis = ['😀', '😂', '❤️', '👍', '👎', '😮', '😢', '😡', '🔥', '👏', '🎉', '💯'];
        
        const grid = document.createElement('div');
        grid.classList.add('emoji-grid');
        
        fallbackEmojis.forEach(emoji => {
            const cell = document.createElement('div');
            cell.classList.add('emoji-cell');
            cell.textContent = emoji;
            cell.addEventListener('click', () => {
                sendReaction(postId, emoji);
                closeAllPickers();
            });
            grid.appendChild(cell);
        });
        
        picker.appendChild(grid);
    }

    function closeAllPickers() {
        if (currentPicker) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    /**
     * Envoie la requête AJAX au serveur.
     */
    function sendReaction(postId, emoji) {
        const url = window.REACTIONS_AJAX_URL;

        if (!url) {
            console.error('REACTIONS_AJAX_URL non définie');
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest' // Important pour is_ajax()
            },
            body: new URLSearchParams({
                'post_id': postId,
                'emoji': emoji,
                'action': 'toggle' // Optionnel, mais peut être utile
            })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                updateSingleReactionDisplay(postId, emoji, data.count, data.user_reacted);
            } else {
                console.error('Erreur serveur:', data.error);
                alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(err => {
            console.error('Erreur AJAX:', err);
            alert('Erreur de connexion: ' + err.message);
        });
    }

    /**
     * Met à jour l'affichage d'une seule réaction après une réponse réussie.
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]`);
        if (!postContainer) return;

        let reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]`);
        
        // Si l'élément n'existe pas, le créer
        if (!reactionElement) {
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', emoji);
            reactionElement.innerHTML = `${emoji} <span class="count">0</span>`;
            reactionElement.addEventListener('click', handleReactionClick);
            
            // L'insérer avant le bouton "+"
            const moreButton = postContainer.querySelector('.reaction-more');
            if (moreButton) {
                moreButton.parentNode.insertBefore(reactionElement, moreButton);
            } else {
                postContainer.querySelector('.post-reactions').appendChild(reactionElement);
            }
        }

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
        
        // Afficher/masquer l'élément selon le compteur
        if (newCount === 0) {
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
