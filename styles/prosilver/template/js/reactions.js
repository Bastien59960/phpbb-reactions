// ------------- Ajouts -----------
function toggle_visible(id) {
    var x = document.getElementById(id);
    if (x.style.display === "block") {
        x.style.display = "none";
    } else {
        x.style.display = "block";
    }
} 

(function () {
    'use strict';

    let currentPicker = null;
    let currentTooltip = null;

    // CORRECTION MAJEURE : Renommage "POPULAR_EMOJIS" en "COMMON_EMOJIS"
    // Les 10 émojis courantes affichées dans le pickup avec 👍 et 👎 en positions 1 et 2
    // À synchroniser avec ajax.php et listener.php
    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

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

        // Charger categories.json depuis prosilver
        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => {
                console.error('Erreur de chargement categories.json', err);
                // CORRECTION : Fallback avec seulement les émojis courantes
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
        // CORRECTION : Section des émojis courantes SANS TITRE selon cahier des charges
        const commonSection = document.createElement('div');
        commonSection.classList.add('emoji-section', 'common-section');

        const commonGrid = document.createElement('div');
        commonGrid.classList.add('emoji-grid', 'common-grid');
        
        // CORRECTION : Utilisation de COMMON_EMOJIS au lieu de POPULAR_EMOJIS
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

        // Séparateur visuel
        const separator = document.createElement('div');
        separator.classList.add('emoji-separator');
        separator.innerHTML = '<hr style="margin: 10px 0; border: 1px solid #ddd;">';
        picker.appendChild(separator);

        // Reste des catégories (en excluant les émojis courantes pour éviter doublons)
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            picker.appendChild(catTitle);

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    // CORRECTION : Éviter les doublons avec les émojis courantes
                    if (COMMON_EMOJIS.includes(emojiObj.emoji)) {
                        return; // Skip cet emoji car il est déjà dans la section courante
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

    // CORRECTION MAJEURE : Fallback sans émojis en dur
    // Affiche seulement les 10 émojis courantes en cas d'échec du JSON
    function buildFallbackPicker(picker, postId) {
        // Section des émojis courantes uniquement
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

        // Message d'information pour l'administrateur
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
     * CORRECTION : Met à jour l'affichage d'une seule réaction après une réponse réussie
     * Selon cahier des charges : masque si count = 0
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
            
            // CORRECTION : Insérer APRÈS le bouton "+" selon cahier des charges
            // Les réactions s'accumulent à droite du bouton +
            const moreButton = postContainer.querySelector('.reaction-more');
            if (moreButton) {
                // Insérer après le bouton + (à droite)
                if (moreButton.nextSibling) {
                    moreButton.parentNode.insertBefore(reactionElement, moreButton.nextSibling);
                } else {
                    moreButton.parentNode.appendChild(reactionElement);
                }
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
        
        // CORRECTION SELON CAHIER DES CHARGES : Masquer si count = 0
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }

        // NOUVEAU : Ajouter tooltip au survol
        setupReactionTooltip(reactionElement, postId, emoji);
    }

    // ========================================================================
    // NOUVELLE FONCTIONNALITÉ : TOOLTIP AU SURVOL AVEC LISTE DES UTILISATEURS
    // ========================================================================

    /**
     * Configure le tooltip au survol d'une réaction
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Retirer les anciens listeners pour éviter les doublons
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        reactionElement.addEventListener('mouseenter', function(e) {
            // Délai de 500ms avant d'afficher le tooltip
            tooltipTimeout = setTimeout(() => {
                // Charger les utilisateurs ayant réagi
                fetch(REACTIONS_AJAX_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        post_id: postId,
                        emoji: emoji,
                        action: 'get_users',
                        sid: REACTIONS_SID
                    })
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success && data.users && data.users.length > 0) {
                        showUserTooltip(reactionElement, data.users);
                    }
                })
                .catch(err => {
                    console.error('Erreur chargement users:', err);
                });
            }, 500);
        });

        reactionElement.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            hideUserTooltip();
        });
    }

    /**
     * Affiche le tooltip avec la liste des utilisateurs
     */
    function showUserTooltip(element, users) {
        hideUserTooltip(); // Fermer tooltip existant

        const tooltip = document.createElement('div');
        tooltip.className = 'reaction-user-tooltip';
        
        // Créer les liens vers les profils
        const userLinks = users.map(user => 
            `<a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" 
                class="reaction-user-link" 
                target="_blank">${escapeHtml(user.username)}</a>`
        ).join('');
        
        tooltip.innerHTML = userLinks;
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        // Positionner le tooltip sous la réaction
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
        tooltip.style.left = `${rect.left + window.scrollX}px`;
        tooltip.style.zIndex = '10001';

        // Empêcher le tooltip de disparaître quand on passe la souris dessus
        tooltip.addEventListener('mouseenter', () => {
            // Le tooltip reste visible
        });

        tooltip.addEventListener('mouseleave', () => {
            hideUserTooltip();
        });
    }

    /**
     * Cache le tooltip
     */
    function hideUserTooltip() {
        if (currentTooltip) {
            currentTooltip.remove();
            currentTooltip = null;
        }
    }

    /**
     * Échappe le HTML pour éviter les injections XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================================================================
    // FIN DES NOUVELLES FONCTIONNALITÉS TOOLTIP
    // ========================================================================

    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    document.addEventListener('DOMContentLoaded', initReactions);

})();