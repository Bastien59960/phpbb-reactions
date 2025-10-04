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
    let allEmojisData = null; // Stocke toutes les données d'emojis pour la recherche

    // Stockage local des usages d'emojis pour la section "Utilisé fréquemment"
    const LOCAL_STORAGE_USAGE_KEY = 'reactions_emoji_usage_v1';

    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '💌', '🥳'];

    // Icônes de catégories pour une barre d'onglets façon LinkedIn
    const CATEGORY_TABS = [
        { key: 'recent', icon: '🕘', label: 'Récents' },
        { key: 'Smileys & Emotion', icon: '🙂', label: 'Smileys' },
        { key: 'Animals & Nature', icon: '🐻', label: 'Animaux' },
        { key: 'Food & Drink', icon: '🍔', label: 'Nourriture' },
        { key: 'Activities', icon: '🏀', label: 'Activités' },
        { key: 'Travel & Places', icon: '🚗', label: 'Lieux' },
        { key: 'Objects', icon: '💡', label: 'Objets' },
        { key: 'Symbols', icon: '％', label: 'Symboles' },
        { key: 'Flags', icon: '🏁', label: 'Drapeaux' }
    ];

    // ---------- Utilitaires usage fréquemment ----------
    function getEmojiUsageMap() {
        try {
            const raw = localStorage.getItem(LOCAL_STORAGE_USAGE_KEY);
            if (!raw) return {};
            const parsed = JSON.parse(raw);
            return typeof parsed === 'object' && parsed ? parsed : {};
        } catch (_) {
            return {};
        }
    }

    function saveEmojiUsageMap(map) {
        try {
            localStorage.setItem(LOCAL_STORAGE_USAGE_KEY, JSON.stringify(map));
        } catch (_) {
            // ignore quota errors
        }
    }

    function incrementEmojiUsage(emoji) {
        const map = getEmojiUsageMap();
        map[emoji] = (map[emoji] || 0) + 1;
        saveEmojiUsageMap(map);
    }

    function getFrequentEmojis(fallbackList = COMMON_EMOJIS, max = 12) {
        const map = getEmojiUsageMap();
        const entries = Object.entries(map);
        if (entries.length === 0) {
            return fallbackList.slice(0, max);
        }
        const sorted = entries
            .sort((a, b) => b[1] - a[1])
            .map(([emoji]) => emoji);
        // compléter avec fallback si pas assez d'éléments
        const merged = [...new Set([...sorted, ...fallbackList])];
        return merged.slice(0, max);
    }

    function slugify(text) {
        return String(text)
            .toLowerCase()
            .replace(/&/g, ' and ')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    // ---------- Initialisation ----------
    function initReactions() {
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
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

        // Charger categories.json
        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => res.json())
            .then(data => {
                allEmojisData = data; // Stocker pour la recherche
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
        // Attributs d'accessibilité de base
        picker.setAttribute('role', 'dialog');
        picker.setAttribute('aria-label', 'Sélecteur d’emoji');

        // ========== En-tête : CHAMP DE RECHERCHE ==========
        const searchContainer = document.createElement('div');
        searchContainer.classList.add('emoji-search-container');
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.classList.add('emoji-search-input');
        searchInput.placeholder = 'Rechercher un emoji...';
        searchInput.autocomplete = 'off';
        
        searchContainer.appendChild(searchInput);
        picker.appendChild(searchContainer);

        // Barre d'onglets (catégories) façon LinkedIn
        const tabsContainer = document.createElement('div');
        tabsContainer.classList.add('emoji-tabs');
        CATEGORY_TABS.forEach(tab => {
            const tabEl = document.createElement('button');
            tabEl.type = 'button';
            tabEl.classList.add('emoji-tab');
            tabEl.setAttribute('aria-label', tab.label);
            tabEl.textContent = tab.icon;
            tabsContainer.appendChild(tabEl);
        });
        picker.appendChild(tabsContainer);

        // Conteneur pour les résultats de recherche
        const searchResults = document.createElement('div');
        searchResults.classList.add('emoji-search-results');
        searchResults.style.display = 'none';
        picker.appendChild(searchResults);

        // Section "Utilisé fréquemment"
        const frequentTitle = document.createElement('div');
        frequentTitle.classList.add('emoji-section-title');
        frequentTitle.textContent = 'Utilisé fréquemment';
        picker.appendChild(frequentTitle);

        const frequentGrid = document.createElement('div');
        frequentGrid.classList.add('emoji-grid', 'common-grid');
        const frequentEmojis = getFrequentEmojis(COMMON_EMOJIS, 18);
        frequentEmojis.forEach(emoji => {
            const cell = createEmojiCell(emoji, postId);
            frequentGrid.appendChild(cell);
        });
        picker.appendChild(frequentGrid);

        // Bouton "Voir tout" et conteneur des catégories (lazy load)
        const showAllWrapper = document.createElement('div');
        showAllWrapper.classList.add('emoji-show-all-wrapper');

        const showAllBtn = document.createElement('button');
        showAllBtn.type = 'button';
        showAllBtn.classList.add('emoji-show-all');
        showAllBtn.innerHTML = '<span class="chevron">▾</span> Afficher toutes les émojis';
        showAllWrapper.appendChild(showAllBtn);
        picker.appendChild(showAllWrapper);

        const categoriesContainer = document.createElement('div');
        categoriesContainer.classList.add('emoji-categories-container');
        categoriesContainer.style.display = 'none';
        picker.appendChild(categoriesContainer);

        let categoriesBuilt = false;
        function ensureCategoriesBuilt() {
            if (categoriesBuilt) return;
            const built = buildFullCategoriesElement(emojiData, postId);
            categoriesContainer.appendChild(built);
            categoriesBuilt = true;
        }

        function expandCategories() {
            ensureCategoriesBuilt();
            categoriesContainer.style.display = 'block';
            showAllBtn.classList.add('expanded');
            showAllBtn.innerHTML = '<span class="chevron">▴</span> Masquer la liste complète';
        }

        function collapseCategories() {
            categoriesContainer.style.display = 'none';
            showAllBtn.classList.remove('expanded');
            showAllBtn.innerHTML = '<span class="chevron">▾</span> Afficher toutes les émojis';
        }

        showAllBtn.addEventListener('click', () => {
            if (showAllBtn.classList.contains('expanded')) {
                collapseCategories();
            } else {
                expandCategories();
            }
        });

        // Navigation via onglets: si on clique, on ouvre et on scroll sur la catégorie
        tabsContainer.querySelectorAll('.emoji-tab').forEach((tabEl, idx) => {
            const tab = CATEGORY_TABS[idx];
            tabEl.addEventListener('click', () => {
                // Ouvrir si fermé
                if (categoriesContainer.style.display === 'none') expandCategories();
                // Tenter le scroll vers la catégorie
                if (tab.key === 'recent') {
                    frequentTitle.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    const id = 'cat-' + slugify(tab.key);
                    const target = categoriesContainer.querySelector('#' + id);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // ========== GESTION DE LA RECHERCHE ==========
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            
            if (query.length === 0) {
                // Afficher les catégories normales
                searchResults.style.display = 'none';
                // Ne ré-ouvre pas automatiquement la liste complète si elle était masquée
                if (showAllBtn.classList.contains('expanded')) {
                    categoriesContainer.style.display = 'block';
                }
                return;
            }

            // Masquer les catégories et afficher les résultats
            categoriesContainer.style.display = 'none';
            searchResults.style.display = 'block';
            
            // Rechercher les emojis
            const results = searchEmojis(query, emojiData);
            
            // Afficher les résultats
            displaySearchResults(searchResults, results, postId);
        });

        // Focus automatique sur le champ de recherche
        setTimeout(() => searchInput.focus(), 100);
    }

    /**
     * NOUVEAU : Crée une cellule d'emoji réutilisable
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cell = document.createElement('span');
        cell.classList.add('emoji-cell');
        cell.textContent = emoji;
        cell.title = name; // Tooltip avec le nom
        cell.addEventListener('click', () => {
            incrementEmojiUsage(emoji);
            sendReaction(postId, emoji);
            closeAllPickers();
        });
        return cell;
    }

    // Construit paresseusement la liste complète des catégories
    function buildFullCategoriesElement(emojiData, postId) {
        const container = document.createElement('div');

        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            catTitle.id = 'cat-' + slugify(category);
            container.appendChild(catTitle);

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    // Evite les doublons avec la section fréquente
                    if (COMMON_EMOJIS.includes(emojiObj.emoji)) return;
                    const cell = createEmojiCell(emojiObj.emoji, postId, emojiObj.name);
                    grid.appendChild(cell);
                });

                if (grid.children.length > 0) {
                    container.appendChild(grid);
                }
            });
        });

        return container;
    }

    /**
     * NOUVEAU : Recherche d'emojis avec support bilingue FR+EN
     */
    function searchEmojis(query, emojiData) {
        const results = [];
        const maxResults = 50; // Limite de résultats
        
        // Charger les keywords français si disponibles
        const keywordsFr = typeof EMOJI_KEYWORDS_FR !== 'undefined' ? EMOJI_KEYWORDS_FR : {};
        
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                emojis.forEach(emojiObj => {
                    if (results.length >= maxResults) return;
                    
                    // Recherche dans le nom anglais
                    if (emojiObj.name.toLowerCase().includes(query)) {
                        results.push(emojiObj);
                        return;
                    }
                    
                    // Recherche dans les keywords français
                    if (keywordsFr[emojiObj.emoji]) {
                        const frKeywords = keywordsFr[emojiObj.emoji];
                        if (frKeywords.some(keyword => keyword.toLowerCase().includes(query))) {
                            results.push(emojiObj);
                            return;
                        }
                    }
                    
                    // Recherche directe dans l'emoji (pour les emojis tapés directement)
                    if (emojiObj.emoji.includes(query)) {
                        results.push(emojiObj);
                    }
                });
            });
        });
        
        return results;
    }

    /**
     * NOUVEAU : Affiche les résultats de recherche
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
        resultsGrid.classList.add('emoji-grid', 'emoji-results-grid');
        
        results.forEach(emojiObj => {
            const cell = createEmojiCell(emojiObj.emoji, postId, emojiObj.name);
            cell.classList.add('emoji-result');
            resultsGrid.appendChild(cell);
        });
        
        container.appendChild(resultsGrid);
    }

    function buildFallbackPicker(picker, postId) {
        const commonGrid = document.createElement('div');
        commonGrid.classList.add('emoji-grid', 'common-grid');

        COMMON_EMOJIS.forEach(emoji => {
            const cell = createEmojiCell(emoji, postId);
            cell.classList.add('common-emoji');
            commonGrid.appendChild(cell);
        });

        picker.appendChild(commonGrid);

        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 10px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; margin-top: 10px;';
        infoDiv.textContent = 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.';
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
        reactionElement.title = `${newCount} réaction${newCount > 1 ? 's' : ''}`;

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

        setupReactionTooltip(reactionElement, postId, emoji);
    }

    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        reactionElement.addEventListener('mouseenter', function(e) {
            tooltipTimeout = setTimeout(() => {
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
