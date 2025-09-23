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

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    const cell = document.createElement('span');
                    cell.classList.add('emoji-cell');
                    cell.textContent = emojiObj.emoji;
                    cell.addEventListener('click', () => {
                        sendReaction(postId, emojiObj.emoji);
                        closeAllPickers();
                    });
                    grid.appendChild(cell);
                });

                picker.appendChild(grid);
            });
        });
    }

    function buildFallbackPicker(picker, postId) {
        const fallbackEmojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
        const grid = document.createElement('div');
        grid.classList.add('emoji-grid');

        fallbackEmojis.forEach(emoji => {
            const cell = document.createElement('span');
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

    function closeAllPickers(event) {
        if (currentPicker && !currentPicker.contains(event.target)) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    function sendReaction(postId, emoji) {
        if (typeof REACTIONS_SID === 'undefined') {
            console.error('REACTIONS_SID is not defined - CSRF may fail');
            REACTIONS_SID = ''; // Fallback pour éviter crash, mais CSRF échouera côté serveur
        }

        fetch(REACTIONS_AJAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                reaction_emoji: emoji,
                sid: REACTIONS_SID
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateSingleReactionDisplay(postId, emoji, data.count, data.user_reacted);
            } else {
                console.error('Erreur de réaction :', data.message);
            }
        })
        .catch(error => console.error('Erreur:', error));
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
