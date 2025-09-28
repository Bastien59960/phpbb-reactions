(function () {
    'use strict';

    let currentPicker = null;

    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    // ---------- Initialisation ----------
    function initReactions() {
        // CORRECTION : Initialiser l'affichage des réactions existantes
        initializeExistingReactions();
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
    }

    /**
     * CORRECTION MAJEURE : Initialise l'affichage des réactions existantes au chargement
     */
    function initializeExistingReactions() {
        document.querySelectorAll('.post-reactions-container').forEach(container => {
            const reactions = container.querySelectorAll('.reaction:not(.reaction-readonly)');
            reactions.forEach(reaction => {
                const count = parseInt(reaction.getAttribute('data-count') || '0');
                const isActive = reaction.classList.contains('active');
                
                // Afficher ou masquer selon le count
                if (count > 0) {
                    reaction.style.display = '';
                } else {
                    reaction.style.display = 'none';
                }
                
                // Log de debug pour vérification
                const emoji = reaction.getAttribute('data-emoji');
                const postId = getPostIdFromReaction(reaction);
                console.log(`[Reactions Init] Post ${postId}, Emoji ${emoji}, Count: ${count}, Active: ${isActive}`);
            });
        });
    }

    function attachReactionEvents() {
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

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        sendReaction(postId, emoji);
    }

    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        closeAllPickers();

        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        if (!postId) return;

        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        currentPicker = picker;

        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => {
                console.error('Erreur de chargement categories.json', err);
                buildFallbackPicker(picker, postId);
            });

        document.body.appendChild(picker);

        const rect = button.getBoundingClientRect();
        picker.style.position = 'absolute';
        picker.style.top = `${rect.bottom + window.scrollY}px`;
        picker.style.left = `${rect.left + window.scrollX}px`;
        picker.style.zIndex = 10000;
    }

    function buildEmojiPicker(picker, postId, emojiData) {
        const commonSection = document.createElement('div');
        commonSection.classList.add('emoji-section', 'common-section');

        const commonGrid = document.createElement('div');
        commonGrid.classList.add('emoji-grid', 'common-grid');
        
        COMMON_EMOJIS.forEach(emoji => {
            const cell = document.createElement('span');
            cell.classList.add('emoji-cell', 'common-emoji');
            cell.textContent = emoji;
            cell.addEventListener('click', () => {
                sendReaction(postId, emoji);
                closeAllPickers();
            });
            commonGrid.appendChild(cell);
        });
        
        commonSection.appendChild(commonGrid);
        picker.appendChild(commonSection);

        const separator = document.createElement('div');
        separator.classList.add('emoji-separator');
        separator.innerHTML = '<hr style="margin: 10px 0; border: 1px solid #ddd;">';
        picker.appendChild(separator);

        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            picker.appendChild(catTitle);

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    if (COMMON_EMOJIS.includes(emojiObj.emoji)) {
                        return;
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

                if (grid.children.length > 0) {
                    picker.appendChild(grid);
                }
            });
        });
    }

    function buildFallbackPicker(picker, postId) {
        const commonGrid = document.createElement('div');
        commonGrid.classList.add('emoji-grid', 'common-grid');

        COMMON_EMOJIS.forEach(emoji => {
            const cell = document.createElement('span');
            cell.classList.add('emoji-cell', 'common-emoji');
            cell.textContent = emoji;
            cell.addEventListener('click', () => {
                sendReaction(postId, emoji);
                closeAllPickers();
            });
            commonGrid.appendChild(cell);
        });

        picker.appendChild(commonGrid);

        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 10px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; margin-top: 10px;';
        infoDiv.textContent = 'Fichier JSON non accessible. Seuls les émojis courantes sont disponibles.';
        picker.appendChild(infoDiv);
    }

    function closeAllPickers(event) {
        if (currentPicker && (!event || !currentPicker.contains(event.target))) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    function isUserLoggedIn() {
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    function showLoginMessage() {
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

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        const reactionElement = document.querySelector(`.post-reactions-container[data-post-id="${postId}"] .reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);
        const hasReacted = reactionElement && reactionElement.classList.contains('active');
        const action = hasReacted ? 'remove' : 'add';

        // CORRECTION : Log de debug amélioré
        console.log(`[Reactions] Sending ${action} for emoji ${emoji} on post ${postId}, hasReacted: ${hasReacted}`);

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
                if (response.status === 403) {
                    showLoginMessage();
                    throw new Error('User not logged in');
                }
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('[Reactions] Response received:', data);
            if (data.success) {
                updateSingleReactionDisplay(postId, emoji, data.count, data.user_reacted);
            } else {
                console.error('Erreur de réaction :', data.error || data.message);
                if (data.error && data.error.includes('logged in')) {
                    showLoginMessage();
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (error.message === 'User not logged in') {
                return;
            }
        });
    }

    /**
     * CORRECTION : Met à jour l'affichage d'une seule réaction
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`);
        if (!postContainer) {
            console.error(`Container not found for post ${postId}`);
            return;
        }

        let reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);
        
        if (!reactionElement) {
            // Créer l'élément s'il n'existe pas
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', emoji);
            reactionElement.innerHTML = `${emoji} <span class="count">0</span>`;
            reactionElement.addEventListener('click', handleReactionClick);
            
            const moreButton = postContainer.querySelector('.reaction-more');
            if (moreButton && moreButton.nextSibling) {
                moreButton.parentNode.insertBefore(reactionElement, moreButton.nextSibling);
            } else if (moreButton) {
                moreButton.parentNode.appendChild(reactionElement);
            } else {
                postContainer.querySelector('.post-reactions').appendChild(reactionElement);
            }
        }

        const countSpan = reactionElement.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = newCount;
        }
        
        reactionElement.setAttribute('data-count', newCount);
        reactionElement.title = `${newCount} réaction${newCount > 1 ? 's' : ''}`;

        if (userHasReacted) {
            reactionElement.classList.add('active');
        } else {
            reactionElement.classList.remove('active');
        }
        
        // CORRECTION : Masquer/afficher selon le count
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
            // Animation d'apparition pour les nouvelles réactions
            if (!reactionElement.classList.contains('initialized')) {
                reactionElement.classList.add('new');
                reactionElement.classList.add('initialized');
                setTimeout(() => reactionElement.classList.remove('new'), 300);
            }
        }

        console.log(`[Reactions] Updated display for ${emoji} on post ${postId}: count=${newCount}, active=${userHasReacted}`);
    }

    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    // CORRECTION : Attendre que le DOM soit complètement chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReactions);
    } else {
        // DOM déjà chargé (cas des pages AJAX)
        initReactions();
    }

})();
