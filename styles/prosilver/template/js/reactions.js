/**
 * JavaScript pour l'extension Reactions
 * 
 * Ce fichier gère toute l'interactivité côté client pour les réactions aux messages.
 * Il inclut :
 * - Gestion des clics sur les réactions existantes
 * - Affichage de la palette d'emojis
 * - Requêtes AJAX vers le serveur
 * - Gestion des tooltips avec les utilisateurs
 * - Support des emojis courantes et étendues
 * 
 * Fonctionnalités principales :
 * - Ajout/suppression de réactions
 * - Affichage des compteurs en temps réel
 * - Tooltips avec liste des utilisateurs
 * - Gestion des erreurs et états de chargement
 * - Support des emojis composés (ZWJ)
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// =============================================================================
// FONCTION UTILITAIRE GLOBALE
// =============================================================================

/**
 * Basculer la visibilité d'un élément
 * 
 * @param {string} id ID de l'élément à basculer
 */
function toggle_visible(id) {
    var x = document.getElementById(id);
    if (x.style.display === "block") {
        x.style.display = "none";
    } else {
        x.style.display = "block";
    }
}

// =============================================================================
// MODULE PRINCIPAL DES RÉACTIONS
// =============================================================================

(function () {
    'use strict';

    // =============================================================================
    // VARIABLES GLOBALES
    // =============================================================================
    
    let currentPicker = null;      // Palette d'emojis actuellement ouverte
    let currentTooltip = null;     // Tooltip actuellement affiché
    let allEmojisData = null;      // Données des emojis étendus

    /**
     * Liste des 10 emojis courantes utilisées par défaut
     * 
     * Ces emojis sont affichés en priorité dans l'interface utilisateur.
     * Ils doivent être synchronisés avec ajax.php et listener.php.
     */
    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    // =============================================================================
    // INITIALISATION ET CONFIGURATION DES ÉVÉNEMENTS
    // =============================================================================
    
    /**
     * Initialiser le système de réactions
     * 
     * Cette fonction configure tous les événements nécessaires
     * pour le fonctionnement des réactions.
     */
    function initReactions() {
        attachReactionEvents();      // Événements sur les réactions existantes
        attachMoreButtonEvents();    // Événements sur le bouton "plus"
        attachTooltipEvents();       // Événements sur les tooltips
        document.addEventListener('click', closeAllPickers); // Fermeture des palettes
    }

    /**
     * Attacher les événements aux réactions existantes
     * 
     * Cette fonction configure les événements de clic sur les réactions
     * existantes pour permettre l'ajout/suppression de réactions.
     */
    function attachReactionEvents() {
        document.querySelectorAll('.post-reactions .reaction:not(.reaction-readonly)').forEach(reaction => {
            reaction.removeEventListener('click', handleReactionClick);
            reaction.addEventListener('click', handleReactionClick);
        });
    }

    /**
     * Attacher les événements au bouton "plus"
     * 
     * Cette fonction configure les événements de clic sur le bouton
     * "plus" pour ouvrir la palette d'emojis.
     */
    function attachMoreButtonEvents() {
        document.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    /**
     * Attacher les événements aux tooltips
     * 
     * Cette fonction configure les événements de survol sur les réactions
     * pour afficher les tooltips avec la liste des utilisateurs.
     */
    function attachTooltipEvents() {
        document.querySelectorAll('.post-reactions .reaction').forEach(reaction => {
            const emoji = reaction.getAttribute('data-emoji');
            const postId = getPostIdFromReaction(reaction);
            if (emoji && postId) {
                setupReactionTooltip(reaction, postId, emoji);
            }
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
                allEmojisData = data;
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
    
    // =========================================================================
    // NOUVELLE STRUCTURE DU PICKER
    // =========================================================================
    function buildEmojiPicker(picker, postId, emojiData) {
        // --- 1. ONGLETS ---
        const tabsContainer = document.createElement('div');
        tabsContainer.className = 'emoji-tabs';
        picker.appendChild(tabsContainer);

        // --- 2. HEADER (Recherche et Fermeture) ---
        const header = document.createElement('div');
        header.className = 'emoji-picker-header';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'emoji-search-input';
        searchInput.placeholder = 'Rechercher...';
        searchInput.autocomplete = 'off';
        const closeBtn = document.createElement('button');
        closeBtn.className = 'emoji-picker-close';
        closeBtn.title = 'Fermer';
        closeBtn.addEventListener('click', (e) => { e.stopPropagation(); closeAllPickers(); });
        header.appendChild(searchInput);
        header.appendChild(closeBtn);
        picker.appendChild(header);

        // --- NOUVEAU CONTENEUR POUR LE CORPS DU PICKER ---
        const pickerBody = document.createElement('div');
        pickerBody.className = 'emoji-picker-body';
        picker.appendChild(pickerBody);

        // --- 3. SECTION "FRÉQUEMMENT UTILISÉ" (FIXE) ---
        const frequentSection = document.createElement('div');
        frequentSection.className = 'emoji-frequent-section';
        const frequentTitle = document.createElement('div');
        frequentTitle.className = 'emoji-category-title';
        frequentTitle.textContent = 'Utilisé fréquemment';
        const frequentGrid = document.createElement('div');
        frequentGrid.className = 'emoji-grid';
        COMMON_EMOJIS.forEach(emoji => {
            frequentGrid.appendChild(createEmojiCell(emoji, postId));
        });
        frequentSection.appendChild(frequentTitle);
        frequentSection.appendChild(frequentGrid);
        pickerBody.appendChild(frequentSection);

        // --- 4. CONTENU PRINCIPAL (SCROLLABLE) ---
        const mainContent = document.createElement('div');
        mainContent.className = 'emoji-picker-main';
        const categoriesContainer = document.createElement('div');
        categoriesContainer.className = 'emoji-categories-container';
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.className = 'emoji-category-title';
            catTitle.textContent = category;
            catTitle.dataset.categoryName = category;
            categoriesContainer.appendChild(catTitle);
            const grid = document.createElement('div');
            grid.className = 'emoji-grid';
            Object.values(subcategories).flat().forEach(emojiObj => {
                grid.appendChild(createEmojiCell(emojiObj.emoji, postId, emojiObj.name));
            });
            categoriesContainer.appendChild(grid);
        });
        mainContent.appendChild(categoriesContainer);
        pickerBody.appendChild(mainContent);

        // Conteneur pour les résultats de recherche (remplace tout le corps)
        const searchResults = document.createElement('div');
        searchResults.className = 'emoji-search-results';
        searchResults.style.display = 'none';
        pickerBody.appendChild(searchResults);

        // --- Logique des onglets ---
        const categoryData = [
            { key: 'frequent', emoji: '🕒', title: 'Utilisé fréquemment' },
            { key: 'smileys', emoji: '😊', title: 'Smileys & Émotions' },
            { key: 'animals', emoji: '🐻', title: 'Animaux & Nature' },
            { key: 'food', emoji: '🍔', title: 'Nourriture & Boisson' },
            { key: 'activities', emoji: '⚽', title: 'Activités' },
            { key: 'travel', emoji: '🚗', title: 'Voyages & Lieux' },
            { key: 'objects', emoji: '💡', title: 'Objets' },
            { key: 'symbols', emoji: '🔥', title: 'Symboles' }
        ];
        categoryData.forEach((cat, index) => {
            const tab = document.createElement('button');
            tab.className = 'emoji-tab';
            tab.textContent = cat.emoji;
            tab.title = cat.title;
            if (index === 0) tab.classList.add('active');
            tab.addEventListener('click', (e) => {
                e.stopPropagation();
                tabsContainer.querySelectorAll('.emoji-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                if (cat.key === 'frequent') {
                    mainContent.scrollTop = 0; // Remonte la liste principale au cas où
                } else {
                    const categoryNameToFind = Object.keys(emojiData.emojis)[index - 1];
                    const categoryElement = mainContent.querySelector(`[data-category-name="${categoryNameToFind}"]`);
                    if (categoryElement) {
                        mainContent.scrollTop = categoryElement.offsetTop;
                    }
                }
            });
            tabsContainer.appendChild(tab);
        });

        // --- GESTION DE LA RECHERCHE ---
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            if (query.length > 0) {
                frequentSection.style.display = 'none';
                mainContent.style.display = 'none';
                searchResults.style.display = 'block';
                const results = searchEmojis(query, emojiData);
                displaySearchResults(searchResults, results, postId);
            } else {
                frequentSection.style.display = 'block';
                mainContent.style.display = 'block';
                searchResults.style.display = 'none';
            }
        });

        setTimeout(() => searchInput.focus(), 50);
    }

    // ... (Le reste du fichier JS reste identique)
    
    /**
     * Crée une cellule d'emoji réutilisable
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cell = document.createElement('button');
        cell.classList.add('emoji-cell');
        cell.textContent = emoji;
        cell.title = name;
        cell.addEventListener('click', () => {
            sendReaction(postId, emoji);
            closeAllPickers();
        });
        return cell;
    }

    /**
     * CORRIGÉ : Recherche avec support FR via EMOJI_KEYWORDS_FR
     */
    function searchEmojis(query, emojiData) {
        const results = new Set(); // Utiliser un Set pour éviter les doublons
        const maxResults = 100;
        
        const keywordsFr = typeof EMOJI_KEYWORDS_FR !== 'undefined' ? EMOJI_KEYWORDS_FR : {};
        
        const allEmojis = Object.values(emojiData.emojis).flatMap(Object.values).flat();

        for (const emojiObj of allEmojis) {
            if (results.size >= maxResults) break;

            // Recherche dans les keywords français
            if (keywordsFr[emojiObj.emoji] && keywordsFr[emojiObj.emoji].some(kw => kw.toLowerCase().includes(query))) {
                results.add(emojiObj);
                continue;
            }
            
            // Recherche dans le nom anglais
            if (emojiObj.name.toLowerCase().includes(query)) {
                results.add(emojiObj);
            }
        }
        
        return Array.from(results);
    }

    /**
     * Affiche les résultats de recherche
     */
    function displaySearchResults(container, results, postId) {
        container.innerHTML = '';
        
        if (results.length === 0) {
            const noResults = document.createElement('div');
            noResults.classList.add('emoji-no-results');
            noResults.textContent = 'Aucun emoji trouvé';
            container.appendChild(noResults);
            return;
        }
        
        const resultsGrid = document.createElement('div');
        resultsGrid.classList.add('emoji-grid');
        
        results.forEach(emojiObj => {
            resultsGrid.appendChild(createEmojiCell(emojiObj.emoji, postId, emojiObj.name));
        });
        
        container.appendChild(resultsGrid);
    }

    function buildFallbackPicker(picker, postId) {
        const pickerContent = document.createElement('div');
        pickerContent.classList.add('emoji-picker-content');
        
        const commonSection = document.createElement('div');
        commonSection.classList.add('common-section');
        
        const commonTitle = document.createElement('div');
        commonTitle.classList.add('common-section-title');
        commonTitle.textContent = 'Utilisé fréquemment';
        commonSection.appendChild(commonTitle);
        
        const commonGrid = document.createElement('div');
        commonGrid.classList.add('emoji-grid', 'common-grid');

        COMMON_EMOJIS.forEach(emoji => {
            const cell = createEmojiCell(emoji, postId);
            cell.classList.add('common-emoji');
            commonGrid.appendChild(cell);
        });
        
        commonSection.appendChild(commonGrid);
        pickerContent.appendChild(commonSection);

        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 16px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0;';
        infoDiv.textContent = 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.';
        pickerContent.appendChild(infoDiv);
        
        picker.appendChild(pickerContent);
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
            console.error('REACTIONS_SID is not defined');
            REACTIONS_SID = '';
        }

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

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

    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`);
        if (!postContainer) return;

        let reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);
        
        if (!reactionElement) {
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', emoji);
            reactionElement.innerHTML = `${emoji} <span class="count">0</span>`;
            reactionElement.addEventListener('click', handleReactionClick);
            
            const moreButton = postContainer.querySelector('.reaction-more');
            if (moreButton) {
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
        if (countSpan) {
            countSpan.textContent = newCount;
        }
        
        reactionElement.setAttribute('data-count', newCount);

        if (userHasReacted) {
            reactionElement.classList.add('active');
        } else {
            reactionElement.classList.remove('active');
        }
        
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }

        // CORRIGÉ : Un seul tooltip avec la liste des utilisateurs
        setupReactionTooltip(reactionElement, postId, emoji);
    }

    /**
     * CORRIGÉ : Tooltip unique avec liste des utilisateurs
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Retirer les anciens listeners
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;
        reactionElement.removeAttribute('title'); // IMPORTANT : Supprimer le title natif

        reactionElement.addEventListener('mouseenter', function(e) {
            tooltipTimeout = setTimeout(() => {
                // D'abord, essayer d'utiliser les données déjà disponibles
                const usersData = reactionElement.getAttribute('data-users');
                if (usersData && usersData !== '[]') {
                    try {
                        const users = JSON.parse(usersData);
                        if (users && users.length > 0) {
                            showUserTooltip(reactionElement, users);
                            return;
                        }
                    } catch (e) {
                        console.error('Erreur parsing users data:', e);
                    }
                }
                
                // Si pas de données disponibles, faire un appel AJAX
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
                    if (!res.ok) throw new Error('Network error');
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
            }, 300);
        });

        reactionElement.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            hideUserTooltip();
        });
    }

    function showUserTooltip(element, users) {
        hideUserTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'reaction-user-tooltip';
        
        const userLinks = users.map(user => 
            `<a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" 
                class="reaction-user-link" 
                target="_blank">${escapeHtml(user.username)}</a>`
        ).join('');
        
        tooltip.innerHTML = userLinks;
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
        tooltip.style.left = `${rect.left + window.scrollX}px`;
        tooltip.style.zIndex = '10001';

        tooltip.addEventListener('mouseenter', () => {});
        tooltip.addEventListener('mouseleave', () => {
            hideUserTooltip();
        });
    }

    function hideUserTooltip() {
        if (currentTooltip) {
            currentTooltip.remove();
            currentTooltip = null;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    document.addEventListener('DOMContentLoaded', initReactions);

})();