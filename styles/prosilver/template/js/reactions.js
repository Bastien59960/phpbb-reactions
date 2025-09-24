(function () {
    'use strict';

    let currentPicker = null;

    // Émojis populaires affichés en premier (modifiables selon vos besoins)
    const POPULAR_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡', '🔥', '👏', '🥳', '🎉'];

    // ---------- Initialisation ----------
    function initReactions() {
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
    }

    function attachReactionEvents() {
        // Seulement pour les réactions interactives (non readonly)
        document.querySelectorAll('.post-reactions .reaction:not(.reaction-readonly)').forEach(reaction => {
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

        // Vérifier si l'utilisateur est connecté
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        sendReaction(postId, emoji);
    }

    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        // Vérifier si l'utilisateur est connecté
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        closeAllPickers();

        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        if (!postId) return;

        // Création du picker flottant
        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        currentPicker = picker;

        // Charger categories.json depuis prosilver - utiliser un chemin relatif
        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => {
                console.error('Erreur de chargement categories.json', err);
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
        // ✅ NOUVEAU : Section des émojis populaires EN PREMIER
        const popularSection = document.createElement('div');
        popularSection.classList.add('emoji-section', 'popular-section');
        
        const popularTitle = document.createElement('div');
        popularTitle.classList.add('emoji-category', 'popular-title');
        popularTitle.textContent = '⭐ Populaires';
        popularSection.appendChild(popularTitle);

        const popularGrid = document.createElement('div');
        popularGrid.classList.add('emoji-grid', 'popular-grid');
        
        POPULAR_EMOJIS.forEach(emoji => {
            const cell = document.createElement('span');
            cell.classList.add('emoji-cell', 'popular-emoji');
            cell.textContent = emoji;
            cell.addEventListener('click', () => {
                sendReaction(postId, emoji);
                closeAllPickers();
            });
            popularGrid.appendChild(cell);
        });
        
        popularSection.appendChild(popularGrid);
        picker.appendChild(popularSection);

        // ✅ Séparateur visuel
        const separator = document.createElement('div');
        separator.classList.add('emoji-separator');
        separator.innerHTML = '<hr style="margin: 10px 0; border: 1px solid #ddd;">';
        picker.appendChild(separator);

        // ✅ Titre pour les autres catégories
        const otherTitle = document.createElement('div');
        otherTitle.classList.add('emoji-category', 'other-categories-title');
        otherTitle.textContent = '📋 Toutes les catégories';
        picker.appendChild(otherTitle);

        // ✅ Reste des catégories (en excluant les populaires pour éviter doublons)
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            picker.appendChild(catTitle);

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    // ✅ Éviter les doublons avec la section populaire
                    if (POPULAR_EMOJIS.includes(emojiObj.emoji)) {
                        return; // Skip cet emoji car il est déjà dans la section populaire
                    }

                    const cell = document.createElement('span');
                    cell.classList.add('emoji-cell');
                    cell.textContent = emojiObj.emoji;
                    cell.addEventListener('click', () => {
                        sendReaction(postId, emojiObj.emoji);
                        closeAllPickers();
                    });
                    grid.appendChild(cell);
                });

                // N'ajouter la grille que si elle contient des émojis
                if (grid.children.length > 0) {
                    picker.appendChild(grid);
                }
            });
        });
    }

    function buildFallbackPicker(picker, postId) {
        // ✅ Section populaire même en mode fallback
        const popularTitle = document.createElement('div');
        popularTitle.classList.add('emoji-category', 'popular-title');
        popularTitle.textContent = '⭐ Populaires';
        picker.appendChild(popularTitle);

        const popularGrid = document.createElement('div');
        popularGrid.classList.add('emoji-grid', 'popular-grid');

        POPULAR_EMOJIS.forEach(emoji => {
            const cell = document.createElement('span');
            cell.classList.add('emoji-cell', 'popular-emoji');
            cell.textContent = emoji;
            cell.addEventListener('click', () => {
                sendReaction(postId, emoji);
                closeAllPickers();
            });
            popularGrid.appendChild(cell);
        });

        picker.appendChild(popularGrid);

        // ✅ Autres émojis de base (non populaires)
        const separator = document.createElement('div');
        separator.innerHTML = '<hr style="margin: 10px 0; border: 1px solid #ddd;">';
        picker.appendChild(separator);

        const otherTitle = document.createElement('div');
        otherTitle.classList.add('emoji-category');
        otherTitle.textContent = '📋 Autres';
        picker.appendChild(otherTitle);

        const fallbackEmojis = ['🤔', '🙏', '🤩', '😴', '🤮', '💯', '🙌', '🤝', '😅', '🤷', '😬', '🤗', '😇', '😎', '😤', '😱'];
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
        if (currentPicker && (!event || !currentPicker.contains(event.target))) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    function isUserLoggedIn() {
        // Vérifier si REACTIONS_SID existe et n'est pas vide
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    function showLoginMessage() {
        // Afficher un message demandant à l'utilisateur de se connecter
        const message = document.createElement('div');
        message.className = 'reactions-login-message';
        message.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 2px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            z-index: 10001;
            text-align: center;
        `;
        message.innerHTML = `
            <p>Vous devez être connecté pour réagir aux messages.</p>
            <button onclick="this.parentNode.remove()" style="margin-top: 10px; padding: 5px 15px;">OK</button>
        `;
        document.body.appendChild(message);

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (message.parentNode) {
                message.remove();
            }
        }, 5000);
    }

    function sendReaction(postId, emoji) {
        if (typeof REACTIONS_SID === 'undefined') {
            console.error('REACTIONS_SID is not defined - CSRF may fail');
            REACTIONS_SID = '';
        }

        // Vérifier à nouveau si l'utilisateur est connecté
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // Déterminer l'action : add ou remove basé sur l'état actuel
        const reactionElement = document.querySelector(`.post-reactions-container[data-post-id="${postId}"] .reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);
        const hasReacted = reactionElement && reactionElement.classList.contains('active');
        const action = hasReacted ? 'remove' : 'add';

        fetch(REACTIONS_AJAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                emoji: emoji,
                action: action,
                sid: REACTIONS_SID
            })
        })
        .then(response => {
            if (!response.ok) {
                // Gérer spécifiquement l'erreur 403 (non connecté)
                if (response.status === 403) {
                    showLoginMessage();
                    throw new Error('User not logged in');
                }
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateSingleReactionDisplay(postId, emoji, data.count, data.user_reacted);
            } else {
                console.error('Erreur de réaction :', data.error || data.message);
                // Si l'erreur indique que l'utilisateur n'est pas connecté
                if (data.error && data.error.includes('logged in')) {
                    showLoginMessage();
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (error.message === 'User not logged in') {
                // Ne pas afficher d'erreur supplémentaire, le message de connexion est déjà affiché
                return;
            }
        });
    }

    /**
     * Met à jour l'affichage d'une seule réaction après une réponse réussie.
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`);
        if (!postContainer) return;

        let reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);
        
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
