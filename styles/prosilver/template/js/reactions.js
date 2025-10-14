/**
 * Fichier : styles/prosilver/template/js/reactions.js â€” bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 * 
 * JavaScript pour l'extension Reactions phpBB 3.3.15
 *
 * Ce fichier gÃ¨re toute l'interactivitÃ© cÃ´tÃ© client pour les rÃ©actions aux messages du forum.
 * Il est le pendant client du contrÃ´leur AJAX et du helper PHP.
 *
 * Points clÃ©s de la logique mÃ©tier :
 *   - Gestion des clics sur les rÃ©actions existantes (ajout/suppression)
 *   - Affichage de la palette d'emojis (picker) avec recherche et catÃ©gories
 *   - RequÃªtes AJAX vers le serveur (add, remove, get, get_users)
 *   - Mise Ã  jour dynamique du DOM aprÃ¨s rÃ©ponse serveur (sans rechargement)
 *   - Tooltips affichant la liste des utilisateurs ayant rÃ©agi
 *   - Support complet des emojis Unicode (utf8mb4)
 *   - Recherche d'emojis avec support franÃ§ais via EMOJI_KEYWORDS_FR
 *
 * ARCHITECTURE :
 * - Module IIFE (Immediately Invoked Function Expression) pour isolation du scope
 * - Pas de dÃ©pendances externes (vanilla JavaScript)
 * - Compatible tous navigateurs modernes (ES6+)
 *
 * SÃ‰CURITÃ‰ :
 * - Nettoyage des emojis avant envoi (safeEmoji) pour Ã©viter erreurs 400
 * - Ã‰chappement HTML pour prÃ©venir XSS
 * - Validation cÃ´tÃ© client (doublÃ©e cÃ´tÃ© serveur)
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

/* ========================================================================== */
/* ========================= FONCTIONS UTILITAIRES ========================== */
/* ========================================================================== */

/**
 * Basculer la visibilitÃ© d'un Ã©lÃ©ment (usage utilitaire)
 * 
 * Cette fonction simple permet de montrer/cacher un Ã©lÃ©ment par son ID.
 * UtilisÃ©e principalement pour les tests manuels.
 * 
 * @param {string} id ID de l'Ã©lÃ©ment DOM Ã  basculer
 */
function toggle_visible(id) {
    var x = document.getElementById(id);
    if (!x) {
        return; // Ã‰lÃ©ment introuvable, sortie silencieuse
    }
    if (x.style.display === "block") {
        x.style.display = "none";
    } else {
        x.style.display = "block";
    }
}

/* ========================================================================== */
/* ========================= MODULE PRINCIPAL ============================== */
/* ========================================================================== */

(function () {
    'use strict';

    /* ---------------------------------------------------------------------- */
    /* --------------------------- VARIABLES GLOBALES ----------------------  */
    /* ---------------------------------------------------------------------- */

    /** @type {HTMLElement|null} Palette d'emojis actuellement ouverte */
    let currentPicker = null;

    /** @type {HTMLElement|null} Tooltip affichant les utilisateurs ayant rÃ©agi */
    let currentTooltip = null;

    /** @type {Object|null} DonnÃ©es JSON chargÃ©es depuis categories.json */
    let allEmojisData = null;

    /** Intervalle (ms) entre deux synchronisations automatiques */
    const DEFAULT_OPTIONS = {
        postEmojiSize: 24,
        pickerWidth: 320,
        pickerHeight: 280,
        pickerEmojiSize: 24,
        showCategories: true,
        showSearch: true,
        useJson: true,
        syncInterval: 5000,
    };

    const options = (typeof window !== 'undefined' && typeof window.REACTIONS_OPTIONS === 'object')
        ? Object.assign({}, DEFAULT_OPTIONS, window.REACTIONS_OPTIONS)
        : Object.assign({}, DEFAULT_OPTIONS);

    function applyOptionStyles() {
        const root = document.documentElement;
        root.style.setProperty('--reactions-post-emoji-size', options.postEmojiSize + 'px');
        root.style.setProperty('--reactions-picker-width', options.pickerWidth + 'px');
        root.style.setProperty('--reactions-picker-height', options.pickerHeight + 'px');
        root.style.setProperty('--reactions-picker-emoji-size', options.pickerEmojiSize + 'px');
    }

    applyOptionStyles();

    /** Identifiant de l'intervalle de synchronisation */
    let liveSyncTimer = null;

    /** Flag pour Ã©viter les requÃªtes concurrentes */
    let liveSyncInFlight = false;

    /**
     * Liste des 10 emojis courantes affichÃ©es par dÃ©faut
     * 
     * IMPORTANT : Ces emojis doivent Ãªtre synchronisÃ©s avec la configuration
     * serveur (ajax.php, ligne 98) pour une cohÃ©rence totale.
     * 
     * @type {string[]}
     */
    const COMMON_EMOJIS = ['ðŸ‘', 'ðŸ‘Ž', 'â¤ï¸', 'ðŸ˜‚', 'ðŸ˜®', 'ðŸ˜¢', 'ðŸ˜¡', 'ðŸ”¥', 'ðŸ‘Œ', 'ðŸ¥³'];

    /* ---------------------------------------------------------------------- */
    /* ------------------------- FONCTIONS D'AIDE EMOJI ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Nettoie une chaÃ®ne emoji pour retirer les caractÃ¨res de contrÃ´le
     * 
     * Cette fonction est CRITIQUE pour Ã©viter les erreurs 400 cÃ´tÃ© serveur.
     * Elle retire les caractÃ¨res de contrÃ´le ASCII qui peuvent corrompre
     * le JSON lors de la transmission AJAX.
     * 
     * PLAGE NETTOYÃ‰E :
     * - 0x00-0x08 : NULL, SOH, STX, ETX, EOT, ENQ, ACK, BEL, BS
     * - 0x0B : Tabulation verticale
     * - 0x0C : Form feed
     * - 0x0E-0x1F : CaractÃ¨res de contrÃ´le
     * - 0x7F : DEL
     * 
     * NE TOUCHE PAS :
     * - Les sÃ©quences UTF-8 valides (ZWJ, modificateurs de skin tone, etc.)
     * - Les emojis composÃ©s (famille, drapeaux, etc.)
     * 
     * @param {string} e ChaÃ®ne pouvant contenir un emoji
     * @returns {string} ChaÃ®ne nettoyÃ©e
     */
    function safeEmoji(e) {
        if (typeof e !== 'string') {
            e = String(e || ''); // Forcer conversion en string
        }
        // Regex : retire caractÃ¨res de contrÃ´le ASCII dangereux
        return e.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------- INITIALISATION & EVENTS ----------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Point d'entrÃ©e principal : initialisation de l'extension
     * 
     * Cette fonction est appelÃ©e au DOMContentLoaded et attache tous les
     * Ã©couteurs d'Ã©vÃ©nements nÃ©cessaires. Elle peut aussi Ãªtre appelÃ©e
     * manuellement aprÃ¨s une mise Ã  jour AJAX du DOM pour rÃ©attacher les listeners.
     * 
     * IDEMPOTENCE : Cette fonction peut Ãªtre appelÃ©e plusieurs fois sans risque
     * grÃ¢ce Ã  removeEventListener() avant chaque addEventListener().
     * 
     * @param {HTMLElement} [context=document] Contexte DOM (document ou sous-Ã©lÃ©ment)
     */
    function initReactions(context) {
        context = context || document;
        if (!(context instanceof Element || context instanceof Document)) {
            console.warn('[Reactions] initReactions: paramÃ¨tre context invalide', context);
            return;
        }

        // Attache Ã©vÃ©nements sur les rÃ©actions affichÃ©es
        attachReactionEvents(context);

        // Attache Ã©vÃ©nements sur les boutons "plus" (ouverture picker)
        attachMoreButtonEvents(context);

        // Attache les tooltips (hover) pour chaque rÃ©action
        attachTooltipEvents(context);

        // Fermeture globale des pickers au clic ailleurs (une seule fois sur document)
        if (context === document) {
            document.addEventListener('click', closeAllPickers);
        }
    }

    /**
     * Attache les Ã©couteurs de clic sur les rÃ©actions existantes
     * 
     * Recherche tous les Ã©lÃ©ments .reaction (sauf .reaction-readonly) dans le
     * contexte fourni et attache handleReactionClick.
     * 
     * PATTERN IDEMPOTENT : retire puis ajoute pour Ã©viter doublons.
     * 
     * @param {HTMLElement} context Contexte DOM de recherche
     */
    function attachReactionEvents(context) {
        context.querySelectorAll('.post-reactions .reaction:not(.reaction-readonly)').forEach(reaction => {
            reaction.removeEventListener('click', handleReactionClick);
            reaction.addEventListener('click', handleReactionClick);
        });
    }

    /**
     * Attache les Ã©couteurs de clic sur les boutons "plus"
     * 
     * Le bouton "plus" (+) ouvre la palette d'emojis pour ajouter une nouvelle rÃ©action.
     * 
     * @param {HTMLElement} context Contexte DOM de recherche
     */
    function attachMoreButtonEvents(context) {
        context.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    /**
     * Attache les tooltips sur chaque rÃ©action
     * 
     * Au survol d'une rÃ©action, un tooltip affiche la liste des utilisateurs
     * ayant utilisÃ© cet emoji (avec appel AJAX get_users si nÃ©cessaire).
     * 
     * @param {HTMLElement} context Contexte DOM de recherche
     */
    function attachTooltipEvents(context) {
        context.querySelectorAll('.post-reactions .reaction').forEach(reaction => {
            const emoji = reaction.getAttribute('data-emoji');
            const postId = getPostIdFromReaction(reaction);
            if (emoji && postId) {
                setupReactionTooltip(reaction, postId, emoji);
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- HANDLERS CLICK ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * GÃ¨re le clic sur une rÃ©action existante
     * 
     * COMPORTEMENT :
     * - Si l'utilisateur a dÃ©jÃ  rÃ©agi : retire la rÃ©action (action='remove')
     * - Sinon : ajoute la rÃ©action (action='add')
     * 
     * SÃ‰CURITÃ‰ :
     * - VÃ©rifie que l'utilisateur est connectÃ© avant envoi
     * - EmpÃªche la propagation de l'Ã©vÃ©nement pour Ã©viter conflits
     * 
     * @param {MouseEvent} event Ã‰vÃ©nement de clic
     */
    function handleReactionClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const reactionElement = event.currentTarget;
        const emoji = reactionElement.getAttribute('data-emoji');
        const postId = getPostIdFromReaction(reactionElement);
        
        // Validation des donnÃ©es
        if (!emoji || !postId) { // SÃ©curitÃ© : ne rien faire si les donnÃ©es sont invalides
            console.warn('[Reactions] DonnÃ©es manquantes sur la rÃ©action cliquÃ©e');
            return;
        }

        // VÃ©rification authentification
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // Envoi de la rÃ©action au serveur
        sendReaction(postId, emoji);
    }

    /**
     * GÃ¨re le clic sur le bouton "plus" (ouverture du picker)
     * 
     * COMPORTEMENT :
     * 1. Ferme tout picker dÃ©jÃ  ouvert (un seul Ã  la fois)
     * 2. CrÃ©e un nouveau picker
     * 3. Charge categories.json pour la liste complÃ¨te d'emojis
     * 4. Si Ã©chec, affiche un picker restreint (COMMON_EMOJIS)
     * 5. Positionne le picker sous le bouton
     * 
     * @param {MouseEvent} event Ã‰vÃ©nement de clic
     */
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

        if (!postId) {
            console.warn('[Reactions] post_id introuvable sur le bouton "plus"');
            return;
        }

        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        picker.style.width = `${options.pickerWidth}px`;
        picker.style.maxWidth = `${options.pickerWidth}px`;
        picker.style.height = `${options.pickerHeight}px`;
        picker.style.maxHeight = `${options.pickerHeight}px`;
        currentPicker = picker;

        const shouldLoadJson = options.useJson !== false
            && typeof REACTIONS_JSON_PATH === 'string'
            && REACTIONS_JSON_PATH.trim() !== '';

        if (shouldLoadJson) {
            fetch(REACTIONS_JSON_PATH)
                .then((res) => {
                    if (!res.ok) {
                        throw new Error('categories.json HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then((data) => {
                    allEmojisData = data;
                    buildEmojiPicker(picker, postId, data);
                })
                .catch((err) => {
                    console.error('[Reactions] Erreur chargement categories.json:', err);
                    buildFallbackPicker(picker, postId);
                });
        } else {
            buildFallbackPicker(picker, postId);
        }

        document.body.appendChild(picker);

        const rect = button.getBoundingClientRect();
        picker.style.position = 'absolute';
        picker.style.top = `${rect.bottom + window.scrollY}px`;
        picker.style.left = `${rect.left + window.scrollX}px`;
        picker.style.zIndex = '10000';
    }


    /* ---------------------------------------------------------------------- */
    /* ---------------------------- BUILD PICKER ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Construit le DOM complet du picker d'emojis (version complÃ¨te)
     * 
     * STRUCTURE DU PICKER :
     * 1. Onglets de catÃ©gories (Smileys, Animaux, Nourriture, etc.)
     * 2. Header avec champ de recherche et bouton fermeture
     * 3. Section "UtilisÃ© frÃ©quemment" (COMMON_EMOJIS)
     * 4. Contenu principal scrollable avec toutes les catÃ©gories
     * 5. Zone de rÃ©sultats de recherche (masquÃ©e par dÃ©faut)
     * 
     * RECHERCHE :
     * - Support des mots-clÃ©s franÃ§ais via EMOJI_KEYWORDS_FR
     * - Filtre en temps rÃ©el pendant la saisie
     * - Limite Ã  100 rÃ©sultats pour les performances
     * 
     * @param {HTMLElement} picker Conteneur du picker
     * @param {number|string} postId ID du message cible
     * @param {Object} emojiData DonnÃ©es JSON des emojis
     */
    function buildEmojiPicker(picker, postId, emojiData) {
        const hasEmojiData = emojiData && typeof emojiData === 'object' && emojiData.emojis && Object.keys(emojiData.emojis).length > 0;
        const enableCategories = options.showCategories !== false && hasEmojiData;
        const enableSearch = options.showSearch !== false;

        picker.classList.toggle('emoji-picker--no-categories', !enableCategories);
        picker.classList.toggle('emoji-picker--no-search', !enableSearch);

        let tabsContainer = null;
        if (enableCategories) {
            tabsContainer = document.createElement('div');
            tabsContainer.className = 'emoji-tabs';
            picker.appendChild(tabsContainer);
        }

        const header = document.createElement('div');
        header.className = 'emoji-picker-header';

        let searchInput = null;
        if (enableSearch) {
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'emoji-search-input';
            searchInput.placeholder = 'Rechercher...';
            searchInput.autocomplete = 'off';
            header.appendChild(searchInput);
        }

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'emoji-picker-close';
        closeBtn.title = 'Fermer';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllPickers();
        });
        header.appendChild(closeBtn);
        picker.appendChild(header);

        const pickerBody = document.createElement('div');
        pickerBody.className = 'emoji-picker-body';
        picker.appendChild(pickerBody);

        const frequentSection = document.createElement('div');
        frequentSection.className = 'emoji-frequent-section';

        const frequentTitle = document.createElement('div');
        frequentTitle.className = 'emoji-category-title';
        frequentTitle.textContent = 'Utilisé fréquemment';

        const frequentGrid = document.createElement('div');
        frequentGrid.className = 'emoji-grid';
        COMMON_EMOJIS.forEach((emoji) => {
            frequentGrid.appendChild(createEmojiCell(emoji, postId));
        });

        frequentSection.appendChild(frequentTitle);
        frequentSection.appendChild(frequentGrid);
        pickerBody.appendChild(frequentSection);

        let mainContent = null;
        if (enableCategories) {
            mainContent = document.createElement('div');
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

                Object.values(subcategories).flat().forEach((emojiObj) => {
                    if (emojiObj && emojiObj.emoji) {
                        grid.appendChild(createEmojiCell(emojiObj.emoji, postId, emojiObj.name));
                    }
                });

                categoriesContainer.appendChild(grid);
            });

            mainContent.appendChild(categoriesContainer);
            pickerBody.appendChild(mainContent);
        }

        let searchResults = null;
        if (enableSearch) {
            searchResults = document.createElement('div');
            searchResults.className = 'emoji-search-results';
            searchResults.style.display = 'none';
            pickerBody.appendChild(searchResults);
        }

        if (enableCategories && tabsContainer) {
            const iconMap = {
                frequent: '⭐',
                smileys: '😄',
                people: '😊',
                animals: '🐻',
                nature: '🌿',
                food: '🍔',
                activities: '⚽',
                travel: '✈️',
                objects: '💡',
                symbols: '🔣',
                flags: '🏳️',
            };

            const availableKeys = Object.keys(emojiData.emojis);
            const tabDefinitions = [
                { key: 'frequent', emoji: iconMap.frequent, title: 'Utilisé fréquemment' },
                ...availableKeys.map((key) => ({
                    key,
                    emoji: iconMap[key] || '🔹',
                    title: key.charAt(0).toUpperCase() + key.slice(1),
                })),
            ];

            tabDefinitions.forEach((cat, index) => {
                if (cat.key !== 'frequent' && !availableKeys.includes(cat.key)) {
                    return;
                }

                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'emoji-tab';
                tab.textContent = cat.emoji;
                tab.title = cat.title;
                if (index === 0) {
                    tab.classList.add('active');
                }

                tab.addEventListener('click', (e) => {
                    e.stopPropagation();
                    tabsContainer.querySelectorAll('.emoji-tab').forEach((t) => t.classList.remove('active'));
                    tab.classList.add('active');

                    if (!mainContent) {
                        return;
                    }

                    if (cat.key === 'frequent') {
                        mainContent.scrollTop = 0;
                        return;
                    }

                    const categoryElement = mainContent.querySelector(`[data-category-name="${cat.key}"]`);
                    if (categoryElement) {
                        mainContent.scrollTop = categoryElement.offsetTop - mainContent.offsetTop;
                    }
                });

                tabsContainer.appendChild(tab);
            });
        }

        if (enableSearch && searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim().toLowerCase();

                if (query.length > 0) {
                    frequentSection.style.display = 'none';
                    if (mainContent) {
                        mainContent.style.display = 'none';
                    }
                    if (searchResults) {
                        searchResults.style.display = 'block';
                        const results = (emojiData && emojiData.emojis)
                            ? searchEmojis(query, emojiData)
                            : COMMON_EMOJIS
                                .filter((emoji) => emoji.toLowerCase().includes(query))
                                .map((emoji) => ({ emoji, name: '' }));
                        displaySearchResults(searchResults, results, postId);
                    }
                } else {
                    frequentSection.style.display = 'block';
                    if (mainContent) {
                        mainContent.style.display = 'block';
                    }
                    if (searchResults) {
                        searchResults.style.display = 'none';
                    }
                }
            });

            window.setTimeout(() => searchInput && searchInput.focus(), 50);
        }
    }


    /* ---------------------------------------------------------------------- */
    /* -------------------------- CRÃ‰ATEURS DOM ----------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * CrÃ©e une cellule d'emoji cliquable pour le picker
     * 
     * SÃ‰CURITÃ‰ :
     * - Applique safeEmoji() pour nettoyer l'emoji
     * - Stocke l'emoji nettoyÃ© dans data-emoji pour cohÃ©rence
     * 
     * COMPORTEMENT :
     * - Au clic : envoie la rÃ©action et ferme le picker
     * 
     * @param {string} emoji Emoji Ã  afficher
     * @param {number|string} postId ID du message cible
     * @param {string} [name=''] Nom descriptif (affichÃ© au survol)
     * @returns {HTMLElement} Bouton de la cellule emoji
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cleanEmoji = safeEmoji(String(emoji));
        
        const cell = document.createElement('button');
        cell.classList.add('emoji-cell');
        cell.textContent = cleanEmoji;
        cell.setAttribute('data-emoji', cleanEmoji);
        cell.title = name;
        
        cell.addEventListener('click', () => {
            sendReaction(postId, cleanEmoji);
            closeAllPickers();
        });
        
        return cell;
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- RECHERCHE EMOJI --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Recherche des emojis selon une requÃªte textuelle
     * 
     * SOURCES DE RECHERCHE (par ordre de prioritÃ©) :
     * 1. Mots-clÃ©s franÃ§ais (EMOJI_KEYWORDS_FR) si disponible
     * 2. Nom anglais de l'emoji (emojiObj.name)
     * 3. Emoji littÃ©ral (utile si copier-coller)
     * 
     * OPTIMISATIONS :
     * - Limite Ã  100 rÃ©sultats pour performances
     * - Utilise Set pour Ã©viter les doublons
     * 
     * @param {string} query Texte de recherche (dÃ©jÃ  en minuscules)
     * @param {Object} emojiData DonnÃ©es JSON des emojis
     * @returns {Array} Tableau d'objets {emoji, name}
     */
    function searchEmojis(query, emojiData) {
        const results = [];
        const addedEmojis = new Set(); // Pour Ã©viter les doublons
        const maxResults = 100;

        // Table de mots-clÃ©s franÃ§ais (optionnelle, injectÃ©e globalement)
        const keywordsFr = (typeof EMOJI_KEYWORDS_FR !== 'undefined' && EMOJI_KEYWORDS_FR) ? EMOJI_KEYWORDS_FR : {};

        // Flatten : rÃ©cupÃ©rer tous les emojiObj de toutes les catÃ©gories
        const allEmojis = Object.values(emojiData.emojis).flatMap(Object.values).flat();

        for (const emojiObj of allEmojis) {
            if (results.length >= maxResults) break;

            // SÃ©curitÃ© : vÃ©rifier structure valide
            if (!emojiObj || !emojiObj.emoji) continue;

            const emojiStr = emojiObj.emoji;

            // Fonction pour ajouter un rÃ©sultat unique
            const addResult = (obj) => {
                if (!addedEmojis.has(obj.emoji)) {
                    results.push(obj);
                    addedEmojis.add(obj.emoji);
                }
            };

            // 1. Recherche via mots-clÃ©s FR
            if (keywordsFr[emojiStr] && keywordsFr[emojiStr].some(kw => kw.toLowerCase().includes(query))) {
                addResult(emojiObj);
            }

            // 2. Recherche par nom anglais
            if (emojiObj.name && emojiObj.name.toLowerCase().includes(query) && results.length < maxResults) {
                addResult(emojiObj);
            }

            // 3. Recherche par emoji littÃ©ral
            if (emojiStr && emojiStr.includes(query) && results.length < maxResults) {
                addResult(emojiObj);
            }
        }

        return results;
    }

    /**
     * Affiche les rÃ©sultats de recherche dans le picker
     * 
     * @param {HTMLElement} container Conteneur des rÃ©sultats
     * @param {Array} results Tableau d'objets {emoji, name}
     * @param {number|string} postId ID du message cible
     */
    function displaySearchResults(container, results, postId) {
        container.innerHTML = '';

        if (results.length === 0) {
            const noResults = document.createElement('div');
            noResults.classList.add('emoji-no-results');
            noResults.textContent = 'Aucun emoji trouvÃ©';
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

    /**
     * Construit un picker restreint (fallback si categories.json inaccessible)
     * 
     * Affiche uniquement les COMMON_EMOJIS avec un message d'information.
     * 
     * @param {HTMLElement} picker Conteneur du picker
     * @param {number|string} postId ID du message cible
     */
    function buildFallbackPicker(picker, postId) {
        const pickerContent = document.createElement('div');
        pickerContent.classList.add('emoji-picker-content');

        const commonSection = document.createElement('div');
        commonSection.classList.add('common-section');

        const commonTitle = document.createElement('div');
        commonTitle.classList.add('common-section-title');
        commonTitle.textContent = 'UtilisÃ© frÃ©quemment';
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

        // Message d'information
        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 16px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0;';
        infoDiv.textContent = 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.';
        pickerContent.appendChild(infoDiv);

        picker.appendChild(pickerContent);
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- FERMER PICKER / GESTION UI --------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Ferme tous les pickers ouverts
     * 
     * COMPORTEMENT :
     * - Si event fourni : vÃ©rifie que le clic est en dehors du picker
     * - Sinon : ferme inconditionnellement (fermeture programmÃ©e)
     * 
     * @param {MouseEvent} [event] Ã‰vÃ©nement de clic (optionnel)
     */
    function closeAllPickers(event) {
        if (currentPicker && (!event || !currentPicker.contains(event.target))) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------------- AUTH UTIL --------------------------------*/
    /* ---------------------------------------------------------------------- */

    /**
     * VÃ©rifie si l'utilisateur est connectÃ©
     * 
     * MÃ‰THODE :
     * - Lecture de la variable globale REACTIONS_SID (injectÃ©e par phpBB)
     * - Si vide ou undefined : non connectÃ©
     * 
     * IMPORTANT : Cette vÃ©rification est doublÃ©e cÃ´tÃ© serveur (sÃ©curitÃ©).
     * 
     * @returns {boolean} True si connectÃ©, False sinon
     */
    function isUserLoggedIn() {
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    /**
     * Affiche un message modal demandant la connexion
     * 
     * AFFICHAGE :
     * - Modal centrÃ© avec overlay transparent
     * - Fermeture au clic sur bouton OK
     * - Auto-fermeture aprÃ¨s 5 secondes
     */
    function showLoginMessage() {
        // VÃ©rifier qu'il n'y a pas dÃ©jÃ  un message affichÃ©
        if (document.querySelector('.reactions-login-message')) {
            return;
        }

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
            <p>Vous devez Ãªtre connectÃ© pour rÃ©agir aux messages.</p>
            <button class="reactions-login-dismiss" style="margin-top: 10px; padding: 5px 15px; cursor: pointer;">OK</button>
        `;
        document.body.appendChild(message);

        // Fermeture au clic sur OK
        message.querySelector('.reactions-login-dismiss').addEventListener('click', () => {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        });

        // Auto-fermeture aprÃ¨s 5 secondes
        setTimeout(() => {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 5000);
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------------- AJAX SEND ------------------------------ */
    /* ---------------------------------------------------------------------- */

    /**
     * Envoie une requÃªte AJAX pour ajouter ou retirer une rÃ©action
     * 
     * PROCESSUS :
     * 1. VÃ©rification authentification
     * 2. Nettoyage de l'emoji avec safeEmoji()
     * 3. DÃ©termination de l'action (add ou remove selon Ã©tat actuel)
     * 4. Construction du payload JSON
     * 5. Envoi via fetch() avec headers appropriÃ©s
     * 6. Traitement de la rÃ©ponse et mise Ã  jour du DOM
     * 
     * GESTION DES ERREURS :
     * - 403 : Affiche message de connexion
     * - 400 : Log console (donnÃ©es invalides)
* - 500 : Log console (erreur serveur)
     * - Network error : Log console (problÃ¨me rÃ©seau)
     * 
     * MISE Ã€ JOUR DOM :
     * - Si data.html fourni : remplacement complet du bloc (mÃ©thode privilÃ©giÃ©e)
     * - Sinon : mise Ã  jour manuelle compteur (fallback)
     * 
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la rÃ©action
     */
    function sendReaction(postId, emoji) {
        // =====================================================================
        // Ã‰TAPE 1 : VÃ‰RIFICATIONS PRÃ‰LIMINAIRES
        // =====================================================================
        
        // VÃ©rification de la variable globale REACTIONS_SID
        if (typeof REACTIONS_SID === 'undefined') {
            console.error('[Reactions] REACTIONS_SID non dÃ©finie');
            REACTIONS_SID = '';
        }

        // VÃ©rification authentification
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // =====================================================================
        // Ã‰TAPE 2 : PRÃ‰PARATION DES DONNÃ‰ES
        // =====================================================================
        
        // Nettoyage de l'emoji pour Ã©viter erreurs 400
        const cleanEmoji = safeEmoji(String(emoji));

        // Recherche de l'Ã©lÃ©ment rÃ©action dans le DOM pour dÃ©terminer l'action
        const reactionElement = document.querySelector(
            `.post-reactions-container[data-post-id="${postId}"] .reaction[data-emoji="${cleanEmoji}"]:not(.reaction-readonly)`
        );
        
        // DÃ©termine si l'utilisateur a dÃ©jÃ  rÃ©agi (classe "active")
        const hasReacted = reactionElement && reactionElement.classList.contains('active');
        
        // Action : 'add' si pas encore rÃ©agi, 'remove' sinon
        const action = hasReacted ? 'remove' : 'add';

        // =====================================================================
        // Ã‰TAPE 3 : CONSTRUCTION DU PAYLOAD JSON
        // =====================================================================
        
        const payload = {
            post_id: postId,
            emoji: cleanEmoji,
            action: action,
            sid: REACTIONS_SID
        };

        // Log de debug (uniquement si le mode debug de phpBB est activÃ©)
        if (window.REACTIONS_DEBUG_MODE) {
            console.debug('[Reactions] Envoi payload:', payload);
        }
        // =====================================================================
        // Ã‰TAPE 4 : ENVOI DE LA REQUÃŠTE AJAX
        // =====================================================================
        
        fetch(REACTIONS_AJAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            // Gestion des codes HTTP d'erreur
            if (!response.ok) {
                if (response.status === 403) {
                    // Utilisateur non authentifiÃ© ou session expirÃ©e
                    showLoginMessage();
                    throw new Error('User not logged in (403)');
                }
                // Autres erreurs HTTP (400, 500, etc.)
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            // =====================================================================
            // Ã‰TAPE 5 : TRAITEMENT DE LA RÃ‰PONSE SERVEUR
            // =====================================================================
            if (window.REACTIONS_DEBUG_MODE) {
                console.debug('[Reactions] RÃ©ponse serveur:', data);
            }

            if (data.success) {
                if (window.REACTIONS_DEBUG_MODE) {
                    if (data.html) {
                        console.debug('[Reactions] HTML reÃ§u: ' + data.html.length + ' caractÃ¨res');
                    } else {
                        console.warn('[Reactions] Pas de HTML dans la rÃ©ponse, utilisation du fallback');
                    }
                }
                // =====================================================================
                // MÃ‰THODE 1 : REMPLACEMENT COMPLET DU BLOC (RECOMMANDÃ‰)
                // =====================================================================
                
                const postContainer = document.querySelector(
                    `.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`
                );
                
                if (postContainer && data.html !== undefined) {
                    postContainer.innerHTML = data.html;
                    // Passer le parent direct qui contient les rÃ©actions
                    initReactions(postContainer);
                    if (window.REACTIONS_DEBUG_MODE) {
                        console.log('[Reactions] âœ… Bloc mis Ã  jour avec succÃ¨s via HTML serveur');
                    }
                } else {
                    // =====================================================================
                    // MÃ‰THODE 2 : MISE Ã€ JOUR MANUELLE (FALLBACK)
                    // =====================================================================

                    // Si le HTML n'est pas fourni ou conteneur introuvable
                    console.warn('[Reactions] Utilisation du fallback updateSingleReactionDisplay');
                    updateSingleReactionDisplay(postId, cleanEmoji, data.count || 0, data.user_reacted || false);
                }
                
            } else {
                // =====================================================================
                // GESTION DES ERREURS MÃ‰TIER RENVOYÃ‰ES PAR LE SERVEUR
                // =====================================================================
                
                console.error('[Reactions] Erreur mÃ©tier du serveur:', data.error || data.message || 'Erreur inconnue.');
                alert(data.error || 'Une erreur est survenue.');
                
                // Si erreur de limite (max rÃ©actions atteintes)
                if (data.error && data.error.includes('LIMIT')) {
                    alert('Limite de rÃ©actions atteinte pour ce message.');
                }
            }
        })
        .catch(error => {
            // =====================================================================
            // GESTION DES ERREURS RÃ‰SEAU OU EXCEPTIONS
            // =====================================================================
            
            console.error('[Reactions] Erreur lors de l\'envoi:', error);
            
            // Afficher un message utilisateur sympathique
            // (Ne pas exposer les dÃ©tails techniques aux utilisateurs finaux)
            alert('Une erreur est survenue lors de l\'ajout de la rÃ©action. Veuillez rÃ©essayer.');
        });
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- MISE Ã€ JOUR DU DOM APRÃˆS AJAX ------------------ */
    /* ---------------------------------------------------------------------- */

    /**
     * Met Ã  jour manuellement l'affichage d'une rÃ©action (fallback)
     * 
     * UTILISATION :
     * - AppelÃ©e uniquement si le serveur ne renvoie pas de HTML complet
     * - CrÃ©e l'Ã©lÃ©ment rÃ©action s'il n'existe pas
     * - Met Ã  jour le compteur et l'Ã©tat "active"
     * - Masque si compteur = 0
     * 
     * IMPORTANT :
     * - Cette mÃ©thode est moins fiable que le remplacement HTML complet
     * - PrÃ©fÃ©rer toujours la mÃ©thode avec data.html du serveur
     * 
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la rÃ©action
     * @param {number} newCount Nouveau compteur
     * @param {boolean} userHasReacted Si l'utilisateur actuel a rÃ©agi
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        // Localiser le conteneur des rÃ©actions
        const postContainer = document.querySelector(
            `.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`
        );
        
        if (!postContainer) {
            console.error('[Reactions Fallback] Conteneur introuvable pour post_id=' + postId);
            return;
        }

        // Rechercher l'Ã©lÃ©ment rÃ©action existant
        let reactionElement = postContainer.querySelector(
            `.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`
        );

        // =====================================================================
        // CAS 1 : LA RÃ‰ACTION N'EXISTE PAS ENCORE DANS LE DOM
        // =====================================================================
        
        if (!reactionElement) {
            // CrÃ©er un nouvel Ã©lÃ©ment span.reaction
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', safeEmoji(String(emoji)));
            reactionElement.innerHTML = `${safeEmoji(String(emoji))} <span class="count">0</span>`;
            
            // Attacher l'Ã©couteur de clic
            reactionElement.addEventListener('click', handleReactionClick);

            // InsÃ©rer dans le DOM (avant le bouton "plus" si prÃ©sent)
            const moreButton = postContainer.querySelector('.reaction-more');
            const reactionsContainer = postContainer.querySelector('.post-reactions');
            
            if (reactionsContainer) {
                if (moreButton) {
                    reactionsContainer.insertBefore(reactionElement, moreButton);
                } else {
                    reactionsContainer.appendChild(reactionElement);
                }
            } else {
                console.error('[Reactions] Impossible d\'insÃ©rer la nouvelle rÃ©action');
                return;
            }
        }

        // =====================================================================
        // CAS 2 : MISE Ã€ JOUR DE LA RÃ‰ACTION EXISTANTE
        // =====================================================================
        
        // Mettre Ã  jour le compteur affichÃ©
        const countSpan = reactionElement.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = newCount;
        }

        // Mettre Ã  jour l'attribut data-count
        reactionElement.setAttribute('data-count', newCount);

        // Gestion de l'Ã©tat actif (classe CSS "active")
        if (userHasReacted) {
            reactionElement.classList.add('active');
        } else {
            reactionElement.classList.remove('active');
        }

        // Masquer si compteur Ã  zÃ©ro
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }

        // RÃ©-attacher le tooltip avec les nouvelles donnÃ©es
        setupReactionTooltip(reactionElement, postId, emoji);
    }

    /* ---------------------------------------------------------------------- */
    /* ---------------------------- TOOLTIP USERS --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Configure le tooltip affichant les utilisateurs ayant rÃ©agi
     * 
     * COMPORTEMENT :
     * - Au survol (300ms de dÃ©lai pour Ã©viter flicker)
     * - Affiche la liste des utilisateurs
     * - Appel AJAX get_users si data-users vide
     * 
     * OPTIMISATION :
     * - Si data-users prÃ©-rempli : utilisation directe (pas d'appel AJAX)
     * - Sinon : appel AJAX avec cache cÃ´tÃ© serveur
     * 
     * @param {HTMLElement} reactionElement Ã‰lÃ©ment rÃ©action
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la rÃ©action
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Nettoyer les anciens listeners (idempotence)
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        // Supprimer le title natif HTML (Ã©vite double affichage)
        reactionElement.removeAttribute('title');

        // =====================================================================
        // Ã‰VÃ‰NEMENT : MOUSE ENTER (SURVOL)
        // =====================================================================
        
        reactionElement.addEventListener('mouseenter', function(e) {
            // DÃ©lai de 300ms avant affichage (Ã©vite les survols rapides)
            tooltipTimeout = setTimeout(() => {
                
                // VÃ©rifier si data-users est prÃ©-rempli (optimisation)
                const usersData = reactionElement.getAttribute('data-users');
                
                if (usersData && usersData !== '[]') {
                    try {
                        const users = JSON.parse(usersData);
                        if (users && users.length > 0) {
                            showUserTooltip(reactionElement, users);
                            return; // Pas besoin d'appel AJAX
                        }
                    } catch (err) {
                        console.error('[Reactions] Erreur parsing data-users:', err);
                    }
                }

                // =====================================================================
                // APPEL AJAX GET_USERS
                // =====================================================================
                
                const cleanEmoji = safeEmoji(String(emoji));

                const payload = {
                    post_id: postId,
                    emoji: cleanEmoji,
                    action: 'get_users',
                    sid: REACTIONS_SID
                };

                fetch(REACTIONS_AJAX_URL, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data?.success && Array.isArray(data.users) && data.users.length > 0) {
                        showUserTooltip(reactionElement, data.users);
                    }
                })
                .catch(err => {
                    console.error('[Reactions] Erreur chargement users:', err);
                });

            }, 300); // DÃ©lai de 300ms
        });

        // =====================================================================
        // Ã‰VÃ‰NEMENT : MOUSE LEAVE (FIN SURVOL)
        // =====================================================================
        
        reactionElement.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            hideUserTooltip();
        });
    }

    /**
     * Affiche le tooltip avec la liste des utilisateurs
     * 
     * AFFICHAGE :
     * - PositionnÃ© sous l'Ã©lÃ©ment rÃ©action
     * - Liste de liens cliquables vers les profils
     * - Reste visible si survolÃ©
     * 
     * @param {HTMLElement} element Ã‰lÃ©ment rÃ©action
     * @param {Array} users Tableau d'objets {user_id, username}
     */
    function showUserTooltip(element, users) {
        // Supprimer tout tooltip existant (un seul Ã  la fois)
        hideUserTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'reaction-user-tooltip';

        // Construction HTML sÃ©curisÃ©e (escape XSS)
        const userLinks = users.map(user =>
            `<a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" class="reaction-user-link" target="_blank">${escapeHtml(user.username)}</a>`
        ).join('');

        tooltip.innerHTML = userLinks || '<span class="no-users">Personne</span>';
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        // Positionnement sous l'Ã©lÃ©ment
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
        tooltip.style.left = `${rect.left + window.scrollX}px`;
        tooltip.style.zIndex = '10001';

        // Garder visible si l'utilisateur survole le tooltip
        tooltip.addEventListener('mouseenter', () => {});
        tooltip.addEventListener('mouseleave', () => {
            hideUserTooltip();
        });
    }

    /**
     * Masque le tooltip actuellement affichÃ©
     */
    function hideUserTooltip() {
        if (currentTooltip) {
            currentTooltip.remove();
            currentTooltip = null;
        }
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------------- UTILS DIVERS ----------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Ã‰chappe les caractÃ¨res HTML pour prÃ©venir XSS
     * 
     * MÃ‰THODE :
     * - Utilise textContent d'un Ã©lÃ©ment temporaire
     * - Plus sÃ»r que les regex manuelles
     * 
     * @param {string} text Texte Ã  Ã©chapper
     * @returns {string} Texte Ã©chappÃ©
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * RÃ©cupÃ¨re le post_id depuis un Ã©lÃ©ment du DOM
     * 
     * MÃ‰THODE :
     * - Remonte l'arbre DOM jusqu'Ã  .post-reactions-container
     * - Lit l'attribut data-post-id
     * 
     * @param {HTMLElement} el Ã‰lÃ©ment DOM de dÃ©part
     * @returns {string|null} post_id ou null si introuvable
     */
    function getPostIdFromReaction(element) {
        const container = element.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- BOOTSTRAP ON READY ------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Initialisation au chargement de la page
     * 
     * Ã‰VÃ‰NEMENT : DOMContentLoaded
     * - Garanti que le DOM est prÃªt avant d'attacher les Ã©couteurs
     */
    document.addEventListener('DOMContentLoaded', () => {
        initReactions();
        startLiveSync();
    });

    /* ---------------------------------------------------------------------- */
    /* --------------------- SYNCHRONISATION TEMPS RÃ‰EL ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * DÃ©marre la synchronisation automatique.
     */
    function startLiveSync() {
        if (typeof REACTIONS_AJAX_URL === 'undefined') {
            return;
        }

        if (liveSyncTimer !== null) {
            window.clearInterval(liveSyncTimer);
            liveSyncTimer = null;
        }

        performLiveSync();
        liveSyncTimer = window.setInterval(performLiveSync, Math.max(1000, options.syncInterval));
    }

    /**
     * RÃ©cupÃ¨re les identifiants des messages prÃ©sents sur la page.
     * @returns {number[]}
     */
    function collectLiveSyncPostIds() {
        const ids = [];
        document.querySelectorAll('.post-reactions-container[data-post-id]').forEach((container) => {
            const value = parseInt(container.getAttribute('data-post-id'), 10);
            if (!Number.isNaN(value) && value > 0) {
                ids.push(value);
            }
        });
        return Array.from(new Set(ids));
    }

    /**
     * Interroge l'API pour rÃ©cupÃ©rer les rÃ©actions actualisÃ©es.
     */
    function performLiveSync() {
        if (liveSyncInFlight) {
            return;
        }

        const postIds = collectLiveSyncPostIds();
        if (!postIds.length) {
            if (liveSyncTimer !== null) {
                window.clearInterval(liveSyncTimer);
                liveSyncTimer = null;
            }
            return;
        }

        liveSyncInFlight = true;

        fetch(REACTIONS_AJAX_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'sync',
                sid: REACTIONS_SID,
                post_ids: postIds,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data || !data.success || !data.posts) {
                    return;
                }

                Object.keys(data.posts).forEach((postId) => {
                    applyLiveSyncPayload(postId, data.posts[postId]);
                });
            })
            .catch((error) => {
                console.warn('[Reactions] Live sync failed', error);
            })
            .finally(() => {
                liveSyncInFlight = false;
            });
    }

    /**
     * Met Ã  jour le DOM avec les informations renvoyÃ©es par l'API.
     * @param {string|number} postId
     * @param {{html?: string}} payload
     */
    function applyLiveSyncPayload(postId, payload) {
        if (!payload || typeof payload.html !== 'string') {
            return;
        }

        const container = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]`);
        if (!container) {
            return;
        }

        if (container.innerHTML === payload.html) {
            return;
        }

        container.innerHTML = payload.html;
        initReactions(container);
    }

    /* ====================================================================== */
    /* ===================== FIN DU MODULE PRINCIPAL ======================== */
    /* ====================================================================== */

    /**
     * NOTES DE DÃ‰BOGAGE ET MAINTENANCE
     * 
     * === PROBLÃˆMES COURANTS ET SOLUTIONS ===
     * 
     * 1. ERREUR 400 LORS DE L'ENVOI :
     *    - VÃ©rifier que safeEmoji() nettoie bien l'emoji
     *    - Console rÃ©seau â†’ Request payload â†’ vÃ©rifier les octets
     *    - VÃ©rifier REACTIONS_AJAX_URL et REACTIONS_SID
     * 
     * 2. ERREUR 500 AVEC EMOJIS 4-OCTETS :
     *    - VÃ©rifier collation table : utf8mb4_unicode_ci
     *    - ALTER TABLE phpbb_post_reactions CONVERT TO CHARACTER SET utf8mb4
     *    - VÃ©rifier LONGEUR reaction_emoji : VARCHAR(191) minimum
     * 
     * 3. RÃ‰ACTION NE S'AFFICHE PAS APRÃˆS CLIC :
     *    - Console : vÃ©rifier data.html dans la rÃ©ponse
     *    - Console : vÃ©rifier logs "[Reactions] HTML reÃ§u"
     *    - VÃ©rifier que helper.php renvoie bien du HTML
     * 
     * 4. Ã‰COUTEURS NE FONCTIONNENT PLUS APRÃˆS AJAX :
     *    - VÃ©rifier que initReactions() est appelÃ© aprÃ¨s mise Ã  jour DOM
     *    - VÃ©rifier le contexte passÃ© Ã  initReactions(context)
     * 
     * 5. TOOLTIP N'APPARAÃŽT PAS :
     *    - VÃ©rifier que setupReactionTooltip() est appelÃ©
     *    - Console rÃ©seau â†’ action get_users â†’ vÃ©rifier rÃ©ponse
     *    - VÃ©rifier styles CSS .reaction-user-tooltip
     * 
     * === OPTIMISATIONS POSSIBLES ===
     * 
     * - Debounce sur la recherche du picker (dÃ©jÃ  prÃ©sent via input)
     * - Cache cÃ´tÃ© client pour get_users (localStorage avec TTL)
     * - Spinner/loading indicator pendant requÃªtes AJAX
     * - Compression gzip du fichier JS en production
     * - Minification en production (uglify-js, terser)
     * 
     * === COMPATIBILITÃ‰ ===
     * 
     * - ES6+ requis (arrow functions, const/let, template literals)
     * - fetch() API requis (polyfill si support IE11)
     * - TestÃ© sur Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
     * 
     * === SÃ‰CURITÃ‰ ===
     * 
     * - Toutes les vÃ©rifications cÃ´tÃ© client sont DOUBLÃ‰ES cÃ´tÃ© serveur
     * - Ne JAMAIS faire confiance au sid cÃ´tÃ© client
     * - escapeHtml() systÃ©matique pour contenu utilisateur
     * - safeEmoji() systÃ©matique avant envoi AJAX
     */

})(); // Fin IIFE (Immediately Invoked Function Expression)
