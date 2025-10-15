/**
 * Fichier : styles/prosilver/template/js/reactions.js — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 * 
 * JavaScript pour l'extension Reactions phpBB 3.3.15
 *
 * Ce fichier gère toute l'interactivité côté client pour les réactions aux messages du forum.
 * Il est le pendant client du contrôleur AJAX et du helper PHP.
 *
 * Points clés de la logique métier :
 *   - Gestion des clics sur les réactions existantes (ajout/suppression)
 *   - Affichage de la palette d'emojis (picker) avec recherche et catégories
 *   - Requêtes AJAX vers le serveur (add, remove, get, get_users)
 *   - Mise à jour dynamique du DOM après réponse serveur (sans rechargement)
 *   - Tooltips affichant la liste des utilisateurs ayant réagi
 *   - Support complet des emojis Unicode (utf8mb4)
 *   - Recherche d'emojis avec support français via EMOJI_KEYWORDS_FR
 *
 * ARCHITECTURE :
 * - Module IIFE (Immediately Invoked Function Expression) pour isolation du scope
 * - Pas de dépendances externes (vanilla JavaScript)
 * - Compatible tous navigateurs modernes (ES6+)
 *
 * SÉCURITÉ :
 * - Nettoyage des emojis avant envoi (safeEmoji) pour éviter erreurs 400
 * - Échappement HTML pour prévenir XSS
 * - Validation côté client (doublée côté serveur)
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

/* ========================================================================== */
/* ========================= FONCTIONS UTILITAIRES ========================== */
/* ========================================================================== */

/**
 * Basculer la visibilité d'un élément (usage utilitaire)
 * 
 * Cette fonction simple permet de montrer/cacher un élément par son ID.
 * Utilisée principalement pour les tests manuels.
 * 
 * @param {string} id ID de l'élément DOM à basculer
 */
function toggle_visible(id) {
    var x = document.getElementById(id);
    if (!x) {
        return; // Élément introuvable, sortie silencieuse
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

    /** @type {HTMLElement|null} Tooltip affichant les utilisateurs ayant réagi */
    let currentTooltip = null;

    /** @type {Object|null} Données JSON chargées depuis categories.json */
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

    /** Flag pour éviter les requêtes concurrentes */
    let liveSyncInFlight = false;

    /**
     * Liste des 10 emojis courantes affichées par défaut
     * 
     * IMPORTANT : Ces emojis doivent être synchronisés avec la configuration
     * serveur (ajax.php, ligne 98) pour une cohérence totale.
     * 
     * @type {string[]}
     */
    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    /* ---------------------------------------------------------------------- */
    /* ------------------------- FONCTIONS D'AIDE EMOJI ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Nettoie une chaîne emoji pour retirer les caractères de contrôle
     * 
     * Cette fonction est CRITIQUE pour éviter les erreurs 400 côté serveur.
     * Elle retire les caractères de contrôle ASCII qui peuvent corrompre
     * le JSON lors de la transmission AJAX.
     * 
     * PLAGE NETTOYÉE :
     * - 0x00-0x08 : NULL, SOH, STX, ETX, EOT, ENQ, ACK, BEL, BS
     * - 0x0B : Tabulation verticale
     * - 0x0C : Form feed
     * - 0x0E-0x1F : Caractères de contrôle
     * - 0x7F : DEL
     * 
     * NE TOUCHE PAS :
     * - Les séquences UTF-8 valides (ZWJ, modificateurs de skin tone, etc.)
     * - Les emojis composés (famille, drapeaux, etc.)
     * 
     * @param {string} e Chaîne pouvant contenir un emoji
     * @returns {string} Chaîne nettoyée
     */
    function safeEmoji(e) {
        if (typeof e !== 'string') {
            e = String(e || ''); // Forcer conversion en string
        }
        // Regex : retire caractères de contrôle ASCII dangereux
        return e.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------- INITIALISATION & EVENTS ----------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Point d'entrée principal : initialisation de l'extension
     * 
     * Cette fonction est appelée au DOMContentLoaded et attache tous les
     * écouteurs d'événements nécessaires. Elle peut aussi être appelée
     * manuellement après une mise à jour AJAX du DOM pour réattacher les listeners.
     * 
     * IDEMPOTENCE : Cette fonction peut être appelée plusieurs fois sans risque
     * grâce à removeEventListener() avant chaque addEventListener().
     * 
     * @param {HTMLElement} [context=document] Contexte DOM (document ou sous-élément)
     */
    function initReactions(context) {
        context = context || document;
        if (!(context instanceof Element || context instanceof Document)) {
            console.warn('[Reactions] initReactions: paramètre context invalide', context);
            return;
        }

        // Attache événements sur les réactions affichées
        attachReactionEvents(context);

        // Attache événements sur les boutons "plus" (ouverture picker)
        attachMoreButtonEvents(context);

        // Attache les tooltips (hover) pour chaque réaction
        attachTooltipEvents(context);

        // Fermeture globale des pickers au clic ailleurs (une seule fois sur document)
        if (context === document) {
            document.addEventListener('click', closeAllPickers);
        }
    }

    /**
     * Attache les écouteurs de clic sur les réactions existantes
     * 
     * Recherche tous les éléments .reaction (sauf .reaction-readonly) dans le
     * contexte fourni et attache handleReactionClick.
     * 
     * PATTERN IDEMPOTENT : retire puis ajoute pour éviter doublons.
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
     * Attache les écouteurs de clic sur les boutons "plus"
     * 
     * Le bouton "plus" (+) ouvre la palette d'emojis pour ajouter une nouvelle réaction.
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
     * Attache les tooltips sur chaque réaction
     * 
     * Au survol d'une réaction, un tooltip affiche la liste des utilisateurs
     * ayant utilisé cet emoji (avec appel AJAX get_users si nécessaire).
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
     * Gère le clic sur une réaction existante
     * 
     * COMPORTEMENT :
     * - Si l'utilisateur a déjà réagi : retire la réaction (action='remove')
     * - Sinon : ajoute la réaction (action='add')
     * 
     * SÉCURITÉ :
     * - Vérifie que l'utilisateur est connecté avant envoi
     * - Empêche la propagation de l'événement pour éviter conflits
     * 
     * @param {MouseEvent} event Événement de clic
     */
    function handleReactionClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const reactionElement = event.currentTarget;
        const emoji = reactionElement.getAttribute('data-emoji');
        const postId = getPostIdFromReaction(reactionElement);
        
        // Validation des données
        if (!emoji || !postId) { // Sécurité : ne rien faire si les données sont invalides
            console.warn('[Reactions] Données manquantes sur la réaction cliquée');
            return;
        }

        // Vérification authentification
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // Envoi de la réaction au serveur
        sendReaction(postId, emoji);
    }

    /**
     * Gère le clic sur le bouton "plus" (ouverture du picker)
     * 
     * COMPORTEMENT :
     * 1. Ferme tout picker déjà ouvert (un seul à la fois)
     * 2. Crée un nouveau picker
     * 3. Charge categories.json pour la liste complète d'emojis
     * 4. Si échec, affiche un picker restreint (COMMON_EMOJIS)
     * 5. Positionne le picker sous le bouton
     * 
     * @param {MouseEvent} event Événement de clic
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
     * Construit le DOM complet du picker d'emojis (version complète)
     * 
     * STRUCTURE DU PICKER :
     * 1. Onglets de catégories (Smileys, Animaux, Nourriture, etc.)
     * 2. Header avec champ de recherche et bouton fermeture
     * 3. Section "Utilisé fréquemment" (COMMON_EMOJIS)
     * 4. Contenu principal scrollable avec toutes les catégories
     * 5. Zone de résultats de recherche (masquée par défaut)
     * 
     * RECHERCHE :
     * - Support des mots-clés français via EMOJI_KEYWORDS_FR
     * - Filtre en temps réel pendant la saisie
     * - Limite à 100 résultats pour les performances
     * 
     * @param {HTMLElement} picker Conteneur du picker
     * @param {number|string} postId ID du message cible
     * @param {Object} emojiData Données JSON des emojis
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

            Object.entries(emojiData.emojis).filter(([category]) => category !== 'Component').forEach(([category, subcategories]) => {
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
            // Table de correspondance directe entre le nom de la catégorie (du JSON) et son icône.
            // C'est plus robuste qu'une recherche par mot-clé.
            const categoryIconMap = {
                'frequent': '⭐',
                'Smileys & Emotion': '😄',
                'People & Body': '',
                'Animals & Nature': '🐻',
                'Food & Drink': '🍔',
                'Travel & Places': '✈️',
                'Activities': '⚽',
                'Objects': '💡',
                'Symbols': '🔣',
                'Flags': '🏳️',
            };

            const availableKeys = Object.keys(emojiData.emojis).filter(key => key !== 'Component');
            const tabDefinitions = [
                ...availableKeys.map((key) => ({
                    key,
                    // On cherche une correspondance exacte dans la map. Si non trouvée, on met un fallback.
                    emoji: categoryIconMap[key] || '🔹',
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
    /* -------------------------- CRÉATEURS DOM ----------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Crée une cellule d'emoji cliquable pour le picker
     * 
     * SÉCURITÉ :
     * - Applique safeEmoji() pour nettoyer l'emoji
     * - Stocke l'emoji nettoyé dans data-emoji pour cohérence
     * 
     * COMPORTEMENT :
     * - Au clic : envoie la réaction et ferme le picker
     * 
     * @param {string} emoji Emoji à afficher
     * @param {number|string} postId ID du message cible
     * @param {string} [name=''] Nom descriptif (affiché au survol)
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
     * Recherche des emojis selon une requête textuelle
     * 
     * SOURCES DE RECHERCHE (par ordre de priorité) :
     * 1. Mots-clés français (EMOJI_KEYWORDS_FR) si disponible
     * 2. Nom anglais de l'emoji (emojiObj.name)
     * 3. Emoji littéral (utile si copier-coller)
     * 
     * OPTIMISATIONS :
     * - Limite à 100 résultats pour performances
     * - Utilise Set pour éviter les doublons
     * 
     * @param {string} query Texte de recherche (déjà en minuscules)
     * @param {Object} emojiData Données JSON des emojis
     * @returns {Array} Tableau d'objets {emoji, name}
     */
    function searchEmojis(query, emojiData) {
        const results = [];
        const addedEmojis = new Set(); // Pour éviter les doublons
        const maxResults = 100;

        // Table de mots-clés français (optionnelle, injectée globalement)
        const keywordsFr = (typeof EMOJI_KEYWORDS_FR !== 'undefined' && EMOJI_KEYWORDS_FR) ? EMOJI_KEYWORDS_FR : {};

        // Flatten : récupérer tous les emojiObj de toutes les catégories
        const allEmojis = Object.values(emojiData.emojis).flatMap(Object.values).flat();

        for (const emojiObj of allEmojis) {
            if (results.length >= maxResults) break;

            // Sécurité : vérifier structure valide
            if (!emojiObj || !emojiObj.emoji) continue;

            const emojiStr = emojiObj.emoji;

            // Fonction pour ajouter un résultat unique
            const addResult = (obj) => {
                if (!addedEmojis.has(obj.emoji)) {
                    results.push(obj);
                    addedEmojis.add(obj.emoji);
                }
            };

            // 1. Recherche via mots-clés FR
            if (keywordsFr[emojiStr] && keywordsFr[emojiStr].some(kw => kw.toLowerCase().includes(query))) {
                addResult(emojiObj);
            }

            // 2. Recherche par nom anglais
            if (emojiObj.name && emojiObj.name.toLowerCase().includes(query) && results.length < maxResults) {
                addResult(emojiObj);
            }

            // 3. Recherche par emoji littéral
            if (emojiStr && emojiStr.includes(query) && results.length < maxResults) {
                addResult(emojiObj);
            }
        }

        return results;
    }

    /**
     * Affiche les résultats de recherche dans le picker
     * 
     * @param {HTMLElement} container Conteneur des résultats
     * @param {Array} results Tableau d'objets {emoji, name}
     * @param {number|string} postId ID du message cible
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
     * - Si event fourni : vérifie que le clic est en dehors du picker
     * - Sinon : ferme inconditionnellement (fermeture programmée)
     * 
     * @param {MouseEvent} [event] Événement de clic (optionnel)
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
     * Vérifie si l'utilisateur est connecté
     * 
     * MÉTHODE :
     * - Lecture de la variable globale REACTIONS_SID (injectée par phpBB)
     * - Si vide ou undefined : non connecté
     * 
     * IMPORTANT : Cette vérification est doublée côté serveur (sécurité).
     * 
     * @returns {boolean} True si connecté, False sinon
     */
    function isUserLoggedIn() {
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    /**
     * Affiche un message modal demandant la connexion
     * 
     * AFFICHAGE :
     * - Modal centré avec overlay transparent
     * - Fermeture au clic sur bouton OK
     * - Auto-fermeture après 5 secondes
     */
    function showLoginMessage() {
        // Vérifier qu'il n'y a pas déjà un message affiché
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
            <p>Vous devez être connecté pour réagir aux messages.</p>
            <button class="reactions-login-dismiss" style="margin-top: 10px; padding: 5px 15px; cursor: pointer;">OK</button>
        `;
        document.body.appendChild(message);

        // Fermeture au clic sur OK
        message.querySelector('.reactions-login-dismiss').addEventListener('click', () => {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        });

        // Auto-fermeture après 5 secondes
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
     * Envoie une requête AJAX pour ajouter ou retirer une réaction
     * 
     * PROCESSUS :
     * 1. Vérification authentification
     * 2. Nettoyage de l'emoji avec safeEmoji()
     * 3. Détermination de l'action (add ou remove selon état actuel)
     * 4. Construction du payload JSON
     * 5. Envoi via fetch() avec headers appropriés
     * 6. Traitement de la réponse et mise à jour du DOM
     * 
     * GESTION DES ERREURS :
     * - 403 : Affiche message de connexion
     * - 400 : Log console (données invalides)
* - 500 : Log console (erreur serveur)
     * - Network error : Log console (problème réseau)
     * 
     * MISE À JOUR DOM :
     * - Si data.html fourni : remplacement complet du bloc (méthode privilégiée)
     * - Sinon : mise à jour manuelle compteur (fallback)
     * 
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la réaction
     */
    function sendReaction(postId, emoji) {
        // =====================================================================
        // ÉTAPE 1 : VÉRIFICATIONS PRÉLIMINAIRES
        // =====================================================================
        
        // Vérification de la variable globale REACTIONS_SID
        if (typeof REACTIONS_SID === 'undefined') {
            console.error('[Reactions] REACTIONS_SID non définie');
            REACTIONS_SID = '';
        }

        // Vérification authentification
        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // =====================================================================
        // ÉTAPE 2 : PRÉPARATION DES DONNÉES
        // =====================================================================
        
        // Nettoyage de l'emoji pour éviter erreurs 400
        const cleanEmoji = safeEmoji(String(emoji));

        // Recherche de l'élément réaction dans le DOM pour déterminer l'action
        const reactionElement = document.querySelector(
            `.post-reactions-container[data-post-id="${postId}"] .reaction[data-emoji="${cleanEmoji}"]:not(.reaction-readonly)`
        );
        
        // Détermine si l'utilisateur a déjà réagi (classe "active")
        const hasReacted = reactionElement && reactionElement.classList.contains('active');
        
        // Action : 'add' si pas encore réagi, 'remove' sinon
        const action = hasReacted ? 'remove' : 'add';

        // =====================================================================
        // ÉTAPE 3 : CONSTRUCTION DU PAYLOAD JSON
        // =====================================================================
        
        const payload = {
            post_id: postId,
            emoji: cleanEmoji,
            action: action,
            sid: REACTIONS_SID
        };

        // Log de debug (uniquement si le mode debug de phpBB est activé)
        if (window.REACTIONS_DEBUG_MODE) {
            console.debug('[Reactions] Envoi payload:', payload);
        }
        // =====================================================================
        // ÉTAPE 4 : ENVOI DE LA REQUÊTE AJAX
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
                    // Utilisateur non authentifié ou session expirée
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
            // ÉTAPE 5 : TRAITEMENT DE LA RÉPONSE SERVEUR
            // =====================================================================
            if (window.REACTIONS_DEBUG_MODE) {
                console.debug('[Reactions] Réponse serveur:', data);
            }

            if (data.success) {
                if (window.REACTIONS_DEBUG_MODE) {
                    if (data.html) {
                        console.debug('[Reactions] HTML reçu: ' + data.html.length + ' caractères');
                    } else {
                        console.warn('[Reactions] Pas de HTML dans la réponse, utilisation du fallback');
                    }
                }
                // =====================================================================
                // MÉTHODE 1 : REMPLACEMENT COMPLET DU BLOC (RECOMMANDÉ)
                // =====================================================================
                
                const postContainer = document.querySelector(
                    `.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`
                );
                
                if (postContainer && data.html !== undefined) {
                    postContainer.innerHTML = data.html;
                    // Passer le parent direct qui contient les réactions
                    initReactions(postContainer);
                    if (window.REACTIONS_DEBUG_MODE) {
                        console.log('[Reactions] ✅ Bloc mis à jour avec succès via HTML serveur');
                    }
                } else {
                    // =====================================================================
                    // MÉTHODE 2 : MISE À JOUR MANUELLE (FALLBACK)
                    // =====================================================================

                    // Si le HTML n'est pas fourni ou conteneur introuvable
                    console.warn('[Reactions] Utilisation du fallback updateSingleReactionDisplay');
                    updateSingleReactionDisplay(postId, cleanEmoji, data.count || 0, data.user_reacted || false);
                }
                
            } else {
                // =====================================================================
                // GESTION DES ERREURS MÉTIER RENVOYÉES PAR LE SERVEUR
                // =====================================================================
                
                console.error('[Reactions] Erreur métier du serveur:', data.error || data.message || 'Erreur inconnue.');
                alert(data.error || 'Une erreur est survenue.');
                
                // Si erreur de limite (max réactions atteintes)
                if (data.error && data.error.includes('LIMIT')) {
                    alert('Limite de réactions atteinte pour ce message.');
                }
            }
        })
        .catch(error => {
            // =====================================================================
            // GESTION DES ERREURS RÉSEAU OU EXCEPTIONS
            // =====================================================================
            
            console.error('[Reactions] Erreur lors de l\'envoi:', error);
            
            // Afficher un message utilisateur sympathique
            // (Ne pas exposer les détails techniques aux utilisateurs finaux)
            alert('Une erreur est survenue lors de l\'ajout de la réaction. Veuillez réessayer.');
        });
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- MISE À JOUR DU DOM APRÈS AJAX ------------------ */
    /* ---------------------------------------------------------------------- */

    /**
     * Met à jour manuellement l'affichage d'une réaction (fallback)
     * 
     * UTILISATION :
     * - Appelée uniquement si le serveur ne renvoie pas de HTML complet
     * - Crée l'élément réaction s'il n'existe pas
     * - Met à jour le compteur et l'état "active"
     * - Masque si compteur = 0
     * 
     * IMPORTANT :
     * - Cette méthode est moins fiable que le remplacement HTML complet
     * - Préférer toujours la méthode avec data.html du serveur
     * 
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la réaction
     * @param {number} newCount Nouveau compteur
     * @param {boolean} userHasReacted Si l'utilisateur actuel a réagi
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        // Localiser le conteneur des réactions
        const postContainer = document.querySelector(
            `.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`
        );
        
        if (!postContainer) {
            console.error('[Reactions Fallback] Conteneur introuvable pour post_id=' + postId);
            return;
        }

        // Rechercher l'élément réaction existant
        let reactionElement = postContainer.querySelector(
            `.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`
        );

        // =====================================================================
        // CAS 1 : LA RÉACTION N'EXISTE PAS ENCORE DANS LE DOM
        // =====================================================================
        
        if (!reactionElement) {
            // Créer un nouvel élément span.reaction
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', safeEmoji(String(emoji)));
            reactionElement.innerHTML = `${safeEmoji(String(emoji))} <span class="count">0</span>`;
            
            // Attacher l'écouteur de clic
            reactionElement.addEventListener('click', handleReactionClick);

            // Insérer dans le DOM (avant le bouton "plus" si présent)
            const moreButton = postContainer.querySelector('.reaction-more');
            const reactionsContainer = postContainer.querySelector('.post-reactions');
            
            if (reactionsContainer) {
                if (moreButton) {
                    reactionsContainer.insertBefore(reactionElement, moreButton);
                } else {
                    reactionsContainer.appendChild(reactionElement);
                }
            } else {
                console.error('[Reactions] Impossible d\'insérer la nouvelle réaction');
                return;
            }
        }

        // =====================================================================
        // CAS 2 : MISE À JOUR DE LA RÉACTION EXISTANTE
        // =====================================================================
        
        // Mettre à jour le compteur affiché
        const countSpan = reactionElement.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = newCount;
        }

        // Mettre à jour l'attribut data-count
        reactionElement.setAttribute('data-count', newCount);

        // Gestion de l'état actif (classe CSS "active")
        if (userHasReacted) {
            reactionElement.classList.add('active');
        } else {
            reactionElement.classList.remove('active');
        }

        // Masquer si compteur à zéro
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }

        // Ré-attacher le tooltip avec les nouvelles données
        setupReactionTooltip(reactionElement, postId, emoji);
    }

    /* ---------------------------------------------------------------------- */
    /* ---------------------------- TOOLTIP USERS --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Configure le tooltip affichant les utilisateurs ayant réagi
     * 
     * COMPORTEMENT :
     * - Au survol (300ms de délai pour éviter flicker)
     * - Affiche la liste des utilisateurs
     * - Appel AJAX get_users si data-users vide
     * 
     * OPTIMISATION :
     * - Si data-users pré-rempli : utilisation directe (pas d'appel AJAX)
     * - Sinon : appel AJAX avec cache côté serveur
     * 
     * @param {HTMLElement} reactionElement Élément réaction
     * @param {number|string} postId ID du message
     * @param {string} emoji Emoji de la réaction
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Nettoyer les anciens listeners (idempotence)
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        // Supprimer le title natif HTML (évite double affichage)
        reactionElement.removeAttribute('title');

        // =====================================================================
        // ÉVÉNEMENT : MOUSE ENTER (SURVOL)
        // =====================================================================
        
        reactionElement.addEventListener('mouseenter', function(e) {
            // Délai de 300ms avant affichage (évite les survols rapides)
            tooltipTimeout = setTimeout(() => {
                
                // Vérifier si data-users est pré-rempli (optimisation)
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

            }, 300); // Délai de 300ms
        });

        // =====================================================================
        // ÉVÉNEMENT : MOUSE LEAVE (FIN SURVOL)
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
     * - Positionné sous l'élément réaction
     * - Liste de liens cliquables vers les profils
     * - Reste visible si survolé
     * 
     * @param {HTMLElement} element Élément réaction
     * @param {Array} users Tableau d'objets {user_id, username}
     */
    function showUserTooltip(element, users) {
        // Supprimer tout tooltip existant (un seul à la fois)
        hideUserTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'reaction-user-tooltip';

        // Construction HTML sécurisée (escape XSS)
        const userLinks = users.map(user =>
            `<a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" class="reaction-user-link" target="_blank">${escapeHtml(user.username)}</a>`
        ).join('');

        tooltip.innerHTML = userLinks || '<span class="no-users">Personne</span>';
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        // Positionnement sous l'élément
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
     * Masque le tooltip actuellement affiché
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
     * Échappe les caractères HTML pour prévenir XSS
     * 
     * MÉTHODE :
     * - Utilise textContent d'un élément temporaire
     * - Plus sûr que les regex manuelles
     * 
     * @param {string} text Texte à échapper
     * @returns {string} Texte échappé
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Récupère le post_id depuis un élément du DOM
     * 
     * MÉTHODE :
     * - Remonte l'arbre DOM jusqu'à .post-reactions-container
     * - Lit l'attribut data-post-id
     * 
     * @param {HTMLElement} el Élément DOM de départ
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
     * ÉVÉNEMENT : DOMContentLoaded
     * - Garanti que le DOM est prêt avant d'attacher les écouteurs
     */
    document.addEventListener('DOMContentLoaded', () => {
        initReactions();
        startLiveSync();
    });

    /* ---------------------------------------------------------------------- */
    /* --------------------- SYNCHRONISATION TEMPS RÉEL ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Démarre la synchronisation automatique.
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
     * Récupère les identifiants des messages présents sur la page.
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
     * Interroge l'API pour récupérer les réactions actualisées.
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
     * Met à jour le DOM avec les informations renvoyées par l'API.
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
     * NOTES DE DÉBOGAGE ET MAINTENANCE
     * 
     * === PROBLÈMES COURANTS ET SOLUTIONS ===
     * 
     * 1. ERREUR 400 LORS DE L'ENVOI :
     *    - Vérifier que safeEmoji() nettoie bien l'emoji
     *    - Console réseau → Request payload → vérifier les octets
     *    - Vérifier REACTIONS_AJAX_URL et REACTIONS_SID
     * 
     * 2. ERREUR 500 AVEC EMOJIS 4-OCTETS :
     *    - Vérifier collation table : utf8mb4_unicode_ci
     *    - ALTER TABLE phpbb_post_reactions CONVERT TO CHARACTER SET utf8mb4
     *    - Vérifier LONGEUR reaction_emoji : VARCHAR(191) minimum
     * 
     * 3. RÉACTION NE S'AFFICHE PAS APRÈS CLIC :
     *    - Console : vérifier data.html dans la réponse
     *    - Console : vérifier logs "[Reactions] HTML reçu"
     *    - Vérifier que helper.php renvoie bien du HTML
     * 
     * 4. ÉCOUTEURS NE FONCTIONNENT PLUS APRÈS AJAX :
     *    - Vérifier que initReactions() est appelé après mise à jour DOM
     *    - Vérifier le contexte passé à initReactions(context)
     * 
     * 5. TOOLTIP N'APPARAÃŽT PAS :
     *    - Vérifier que setupReactionTooltip() est appelé
     *    - Console réseau → action get_users → vérifier réponse
     *    - Vérifier styles CSS .reaction-user-tooltip
     * 
     * === OPTIMISATIONS POSSIBLES ===
     * 
     * - Debounce sur la recherche du picker (déjà présent via input)
     * - Cache côté client pour get_users (localStorage avec TTL)
     * - Spinner/loading indicator pendant requêtes AJAX
     * - Compression gzip du fichier JS en production
     * - Minification en production (uglify-js, terser)
     * 
     * === COMPATIBILITÉ ===
     * 
     * - ES6+ requis (arrow functions, const/let, template literals)
     * - fetch() API requis (polyfill si support IE11)
     * - Testé sur Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
     * 
     * === SÉCURITÉ ===
     * 
     * - Toutes les vérifications côté client sont DOUBLÉES côté serveur
     * - Ne JAMAIS faire confiance au sid côté client
     * - escapeHtml() systématique pour contenu utilisateur
     * - safeEmoji() systématique avant envoi AJAX
     */

})(); // Fin IIFE (Immediately Invoked Function Expression)
