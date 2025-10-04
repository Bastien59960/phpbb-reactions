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
    let allEmojisData = null;

    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

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

    function buildEmojiPicker(picker, postId, emojiData) {
        // ========== BOUTON DE FERMETURE CORRIGÉ ==========
        const closeBtn = document.createElement('button');
        closeBtn.classList.add('emoji-picker-close');
        closeBtn.title = 'Fermer';
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllPickers();
        });
        picker.appendChild(closeBtn);
        
        // ========== CHAMP DE RECHERCHE (SANS 👋) ==========
        const searchContainer = document.createElement('div');
        searchContainer.classList.add('emoji-search-container');
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.classList.add('emoji-search-input');
        searchInput.placeholder = 'Rechercher un emoji...';
        searchInput.autocomplete = 'off';
        searchInput.style.flex = '1';
        
        searchContainer.appendChild(searchInput);
        picker.appendChild(searchContainer);

        // Conteneur pour les résultats de recherche
        const searchResults = document.createElement('div');
        searchResults.classList.add('emoji-search-results');
        searchResults.style.display = 'none';
        picker.appendChild(searchResults);

        // Conteneur scrollable principal
        const pickerContent = document.createElement('div');
        pickerContent.classList.add('emoji-picker-content');
        
        // ========== TABS DE CATÉGORIES FONCTIONNELS ==========
        const tabsContainer = document.createElement('div');
        tabsContainer.classList.add('emoji-tabs');
        
        const categoryData = [
            { key: 'frequent', emoji: '🕒', title: 'Utilisé fréquemment', content: null },
            { key: 'smileys', emoji: '😊', title: 'Smileys & Émotions', content: 'Smileys & Emotions' },
            { key: 'animals', emoji: '🐻', title: 'Animaux & Nature', content: 'Animals & Nature' },
            { key: 'food', emoji: '🍔', title: 'Nourriture & Boisson', content: 'Food & Drink' },
            { key: 'activities', emoji: '⚽', title: 'Activités', content: 'Activities' },
            { key: 'travel', emoji: '🚗', title: 'Voyages & Lieux', content: 'Travel & Places' },
            { key: 'objects', emoji: '💡', title: 'Objets', content: 'Objects' },
            { key: 'symbols', emoji: '🔥', title: 'Symboles', content: 'Symbols' }
        ];
        
        categoryData.forEach((cat, index) => {
            const tab = document.createElement('button');
            tab.classList.add('emoji-tab');
            tab.textContent = cat.emoji;
            tab.title = cat.title;
            tab.dataset.category = cat.key;
            if (index === 0) tab.classList.add('active');
            
            tab.addEventListener('click', (e) => {
                e.stopPropagation();
                // Retirer active de tous les tabs
                tabsContainer.querySelectorAll('.emoji-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Scroll vers la catégorie
                if (cat.key === 'frequent') {
                    pickerContent.scrollTop = 0;
                } else {
                    const categoryElement = pickerContent.querySelector(`[data-category-name="${cat.content}"]`);
                    if (categoryElement) {
                        categoryElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
            
            tabsContainer.appendChild(tab);
        });
        
        picker.appendChild(tabsContainer);
        
        // Section des emojis courantes
        const commonSection = document.createElement('div');
        commonSection.classList.add('common-section');
        commonSection.dataset.categoryName = 'frequent';
        
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
        
        // Séparateur
        const separator = document.createElement('div');
        separator.classList.add('emoji-separator');
        pickerContent.appendChild(separator);
        
        // Bouton "Voir plus"
        const showMoreBtn = document.createElement('button');
        showMoreBtn.classList.add('emoji-show-more');
        showMoreBtn.textContent = 'Voir toutes les catégories';
        pickerContent.appendChild(showMoreBtn);
        
        // Conteneur des catégories
        const categoriesContainer = document.createElement('div');
        categoriesContainer.classList.add('emoji-categories-hidden');

        // Reste des catégories
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            catTitle.dataset.categoryName = category;
            categoriesContainer.appendChild(catTitle);

            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                const grid = document.createElement('div');
                grid.classList.add('emoji-grid');

                emojis.forEach(emojiObj => {
                    if (COMMON_EMOJIS.includes(emojiObj.emoji)) {
                        return;
                    }

                    const cell = createEmojiCell(emojiObj.emoji, postId, emojiObj.name);
                    grid.appendChild(cell);
                });

                if (grid.children.length > 0) {
                    categoriesContainer.appendChild(grid);
                }
            });
        });
        
        pickerContent.appendChild(categoriesContainer);
        picker.appendChild(pickerContent);
        
        // ========== GESTION DU BOUTON "VOIR PLUS" ==========
        let categoriesExpanded = false;
        showMoreBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            categoriesExpanded = !categoriesExpanded;
            
            if (categoriesExpanded) {
                categoriesContainer.classList.add('show');
                showMoreBtn.textContent = 'Voir moins';
            } else {
                categoriesContainer.classList.remove('show');
                showMoreBtn.textContent = 'Voir toutes les catégories';
                pickerContent.scrollTop = 0;
            }
        });

        // ========== GESTION DE LA RECHERCHE AVEC KEYWORDS FR ==========
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            
            if (query.length === 0) {
                searchResults.style.display = 'none';
                pickerContent.style.display = 'block';
                return;
            }

            pickerContent.style.display = 'none';
            searchResults.style.display = 'block';
            
            const results = searchEmojis(query, emojiData);
            displaySearchResults(searchResults, results, postId);
        });

        setTimeout(() => searchInput.focus(), 100);
    }

    /**
     * Crée une cellule d'emoji réutilisable
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cell = document.createElement('span');
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
        const results = [];
        const maxResults = 50;
        
        // Vérifier si les keywords FR sont chargés
        const keywordsFr = typeof EMOJI_KEYWORDS_FR !== 'undefined' ? EMOJI_KEYWORDS_FR : {};
        
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            Object.entries(subcategories).forEach(([subcategory, emojis]) => {
                emojis.forEach(emojiObj => {
                    if (results.length >= maxResults) return;
                    
                    // Recherche dans les keywords français d'ABORD
                    if (keywordsFr[emojiObj.emoji]) {
                        const frKeywords = keywordsFr[emojiObj.emoji];
                        if (frKeywords.some(keyword => keyword.toLowerCase().includes(query))) {
                            results.push(emojiObj);
                            return;
                        }
                    }
                    
                    // Puis dans le nom anglais
                    if (emojiObj.name.toLowerCase().includes(query)) {
                        results.push(emojiObj);
                        return;
                    }
                });
            });
        });
        
        return results;
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
        resultsGrid.classList.add('emoji-grid', 'emoji-results-grid');
        
        results.forEach(emojiObj => {
            const cell = createEmojiCell(emojiObj.emoji, postId, emojiObj.name);
            cell.classList.add('emoji-result');
            resultsGrid.appendChild(cell);
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
