/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2024 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * 
 * Fichier : reactions.js
 * Chemin : /styles/prosilver/template/js/reactions.js
 * 
 * @brief      Gère toute l'interactivité côté client pour l'extension "Post Reactions".
 *
 * Ce script est le cœur de l'expérience utilisateur. Il prend en charge :
 * - L'affichage et l'interaction avec la palette d'emojis (picker), incluant la recherche et les catégories.
 * - L'envoi des requêtes AJAX pour ajouter, retirer et synchroniser les réactions.
 * - La mise à jour dynamique du DOM sans rechargement de page.
 * - L'affichage des tooltips listant les utilisateurs qui ont réagi.
 * - La synchronisation en temps réel des réactions sur la page.
 */

/* ========================================================================== */
/* ========================= FONCTIONS UTILITAIRES ========================== */
/* ========================================================================== */

// Note : La fonction `toggle_visible` a été retirée car elle n'était pas utilisée
// dans le code de production et servait principalement au débogage.
// Si vous en avez besoin pour des tests, vous pouvez la réintégrer ici.

// Exemple de la fonction retirée pour référence :
/*
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
*/

/* ========================================================================== */
/* ========================= MODULE PRINCIPAL ============================== */
/* ========================================================================== */

(function () {
    'use strict';

    /* ---------------------------------------------------------------------- */
    /* ---------------------- ÉTAT ET CONFIGURATION GLOBALE ----------------- */
    /* ---------------------------------------------------------------------- */

    /** @type {HTMLElement|null} Référence vers la palette d'emojis (picker) actuellement ouverte. `null` si aucune n'est affichée. */
    let currentPicker = null;

    /** @type {HTMLElement|null} Référence vers le tooltip des utilisateurs actuellement affiché. */
    let currentTooltip = null;

    /** @type {number|null} ID du timer (setTimeout) pour la fermeture différée du tooltip, afin de permettre à l'utilisateur de le survoler. */
    let leaveTimeout = null;

    /** @type {Object|null} Cache pour les données des emojis chargées depuis `categories.json`, afin d'éviter des chargements multiples. */
    let allEmojisData = null;

    /** @const {Object} Options par défaut de l'extension. Elles peuvent être surchargées par l'objet `window.REACTIONS_OPTIONS`. */
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
    
    /** @const {Object} Chaînes de langue pour l'interface JavaScript. Surchargées par `window.REACTIONS_LANG`. */
    const L = (typeof window.REACTIONS_LANG === 'object') ? window.REACTIONS_LANG : {
        SEARCH: 'Rechercher...',
        CLOSE: 'Fermer',
        FREQUENTLY_USED: 'Utilisé fréquemment',
        NO_EMOJI_FOUND: 'Aucun emoji trouvé',
        LOGIN_REQUIRED: 'Vous devez être connecté pour réagir aux messages.',
    };

    /** @const {Object} Fusionne les options par défaut avec les options personnalisées fournies par le template phpBB. */
    const options = (typeof window !== 'undefined' && typeof window.REACTIONS_OPTIONS === 'object')
        ? Object.assign({}, DEFAULT_OPTIONS, window.REACTIONS_OPTIONS)
        : Object.assign({}, DEFAULT_OPTIONS);

    /**
     * Applique les options de dimensionnement en tant que variables CSS sur l'élément racine (`:root`).
     * Permet de contrôler le style des composants (picker, emojis) directement depuis le CSS.
     */
    function applyOptionStyles() {
        const root = document.documentElement;
        root.style.setProperty('--reactions-post-emoji-size', options.postEmojiSize + 'px');
        root.style.setProperty('--reactions-picker-width', options.pickerWidth + 'px');
        root.style.setProperty('--reactions-picker-height', options.pickerHeight + 'px');
        root.style.setProperty('--reactions-picker-emoji-size', options.pickerEmojiSize + 'px');
    }

    applyOptionStyles();

    /** @type {number|null} ID de l'intervalle (setInterval) pour la synchronisation en temps réel. */
    let liveSyncTimer = null;

    /** @type {boolean} Indicateur pour éviter les requêtes de synchronisation concurrentes. `true` si une requête est en cours. */
    let liveSyncInFlight = false;

    /**
     * @const {string[]} Liste des emojis affichés dans la section "Utilisé fréquemment" du picker.
     * 
     * @important Cette liste doit idéalement être synchronisée avec une configuration côté serveur
     *            pour garantir la cohérence, surtout si elle devient personnalisable.
     */
    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    /* ---------------------------------------------------------------------- */
    /* ------------------------- SÉCURITÉ ET NETTOYAGE ----------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Nettoie une chaîne (potentiellement un emoji) pour retirer les caractères de contrôle invisibles.
     * 
     * Cette fonction est CRITIQUE pour éviter les erreurs 400 côté serveur.
     * Elle retire les caractères de contrôle ASCII qui peuvent corrompre
     * le JSON lors de la transmission AJAX.
     * 
     * PLAGE NETTOYÉE :
     * - 0x00-0x08 : NULL, SOH, STX, ETX, EOT, ENQ, ACK, BEL, BS
     * - 0x0B, 0x0C, 0x0E-0x1F, 0x7F : Autres caractères de contrôle.
     * Elle ne touche pas aux séquences UTF-8 valides qui composent les emojis modernes.
     * 
     * @param {string} e La chaîne à nettoyer.
     * @returns {string} Chaîne nettoyée
     */
    function safeEmoji(e) {
        if (typeof e !== 'string') {
            e = String(e || ''); // Forcer conversion en string
        }
        // Regex : retire caractères de contrôle ASCII dangereux
        return e.replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, '');
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------- INITIALISATION & EVENTS ----------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Point d'entrée pour l'initialisation des fonctionnalités sur une portion du DOM.
     * 
     * Cette fonction est appelée au chargement de la page (`DOMContentLoaded`) et après
     * chaque mise à jour AJAX du contenu des réactions (par `sendReaction` et `applyLiveSyncPayload`).
     * Elle attache les écouteurs nécessaires pour les boutons "plus" et les tooltips.
     * Note : Les clics sur les réactions elles-mêmes sont gérés par un écouteur global (délégation).
     *
     * @param {HTMLElement} [context=document] Contexte DOM (document ou sous-élément)
     */
    function initReactions(context) {
        context = context || document;
        if (!(context instanceof Element || context instanceof Document)) {
            console.warn('[Reactions] initReactions: paramètre context invalide', context);
            return;
        }

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
     * Attache les écouteurs de clic sur les boutons "plus"
     * 
     * Le bouton "plus" (+) ouvre la palette d'emojis pour ajouter une nouvelle réaction.
     * La fonction est idempotente : elle retire l'ancien écouteur avant d'en ajouter un nouveau pour éviter les doublons.
     * @param {HTMLElement} context Le conteneur DOM dans lequel chercher les boutons.
     */
    function attachMoreButtonEvents(context) {
        context.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    /**
     * Attache les écouteurs de survol pour afficher les tooltips des utilisateurs.
     * 
     * Pour chaque réaction, un survol déclenche l'affichage d'un tooltip listant
     * les utilisateurs qui ont réagi. Les données sont lues depuis `data-users` ou récupérées via AJAX.
     * @param {HTMLElement} context Le conteneur DOM dans lequel chercher les réactions.
     */
    function attachTooltipEvents(context) {
        context.querySelectorAll('.post-reactions .reaction-wrapper').forEach(wrapper => {
            const emoji = wrapper.getAttribute('data-emoji');
            const postId = getPostIdFromReaction(wrapper);
            if (emoji && postId) {
                setupReactionTooltip(wrapper, postId, emoji);
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- HANDLERS CLICK ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Gère les clics sur les réactions via la DÉLÉGATION D'ÉVÉNEMENTS.
     * 
     * Un seul écouteur est attaché à `document`. Il intercepte tous les clics et agit
     * uniquement si la cible est un bouton de réaction (`.reaction`). Cette approche est
     * robuste et performante, car elle fonctionne même si les réactions sont
     * ajoutées/supprimées dynamiquement, sans avoir besoin de ré-attacher des écouteurs.
     * 
     * @param {MouseEvent} event L'événement de clic global.
     */
    document.addEventListener('click', function(event) {
        // Cible le bouton de réaction, même si le clic a eu lieu sur un de ses enfants (ex: <span>).
        const reactionButton = event.target.closest('.reaction:not(.reaction-readonly)');
    
        // Si le clic n'est pas sur un bouton de réaction, on arrête tout.
        if (!reactionButton) {
            return;
        }
    
        event.preventDefault(); // Empêche le comportement par défaut uniquement si c'est une réaction.
        const wrapper = reactionButton.closest('.reaction-wrapper');
        const emoji = wrapper.getAttribute('data-emoji');
        const postId = getPostIdFromReaction(wrapper);
        
        // Validation des données
        if (!emoji || !postId) { // Sécurité : ne rien faire si les données sont invalides
            console.warn('[Reactions] Données manquantes sur la réaction cliquée');
            return;
        }
    
        // Vérification authentification
        if (!isUserLoggedIn()) {
            showLoginMessage(L.LOGIN_REQUIRED);
            return;
        }
    
        // Envoi de la réaction au serveur
        sendReaction(postId, emoji); // Appel unique et centralisé
    });
    
    /**
     * Gère le clic sur le bouton "plus" (ouverture du picker)
     * 
     * COMPORTEMENT :
     * 1. Ferme tout picker déjà ouvert (un seul à la fois)
     * 2. Vérifie que l'utilisateur est connecté.
     * 3. Crée et affiche un nouveau picker d'emojis.
     * 4. Tente de charger la liste complète d'emojis depuis `categories.json`.
     * 5. En cas d'échec, affiche un picker de secours avec les emojis les plus courants.
     * 6. Positionne le picker sous le bouton cliqué.
     * 
     * @param {MouseEvent} event L'événement de clic sur le bouton "+".
     */
    function handleMoreButtonClick(event) {
        event.preventDefault(); // Garder pour éviter le comportement par défaut si c'est un lien
        event.stopPropagation(); // Empêche la fermeture immédiate par le listener global

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

        const shouldLoadJson = options.useJson !== false &&
            typeof window.REACTIONS_JSON_PATH === 'string' &&
            window.REACTIONS_JSON_PATH.trim() !== '';

        if (shouldLoadJson) {
            // Log de débogage pour vérifier l'URL utilisée pour le fetch.
            if (window.REACTIONS_DEBUG_MODE) {
                console.log('[Reactions] Tentative de chargement du JSON depuis :', window.REACTIONS_JSON_PATH);
            }

            fetch(window.REACTIONS_JSON_PATH)
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
    
    /**
     * Gère le clic sur un emoji DANS le picker.
     * 
     * Cet écouteur est attaché au conteneur du picker et utilise la délégation
     * pour capturer les clics sur les cellules d'emoji (`.emoji-cell`).
     * 
     * @param {MouseEvent} event 
     */
    function handlePickerEmojiClick(event) {
        const target = event.target.closest('.emoji-cell');
        if (target && target.dataset.emoji && target.dataset.postId) {
            sendReaction(target.dataset.postId, target.dataset.emoji);
            closeAllPickers();
        }
    }


    /* ---------------------------------------------------------------------- */
    /* ---------------------------- BUILD PICKER ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Construit l'interface complète du picker d'emojis.
     * 
     * STRUCTURE DU PICKER :
     * 1. Header : Champ de recherche et bouton de fermeture.
     * 2. Onglets de catégories : Permettent de naviguer rapidement entre les sections.
     * 3. Corps :
     *    - Section "Utilisé fréquemment".
     *    - Conteneur principal scrollable avec toutes les catégories d'emojis.
     *    - Conteneur pour les résultats de recherche (affiché/masqué dynamiquement).
     * 
     * RECHERCHE :
     * - La recherche se fait sur les noms d'emojis et sur les mots-clés français (via `EMOJI_KEYWORDS_FR`).
     * - Les résultats sont affichés en temps réel.
     * 
     * @param {HTMLElement} picker Le conteneur du picker à remplir.
     * @param {number|string} postId L'ID du message auquel la réaction sera associée.
     * @param {Object} emojiData Les données JSON des emojis, structurées par catégories.
     */
    function buildEmojiPicker(picker, postId, emojiData) {
        const hasEmojiData = emojiData && typeof emojiData === 'object' && emojiData.emojis && Object.keys(emojiData.emojis).length > 0;
        const enableCategories = options.showCategories !== false && hasEmojiData;
        const enableSearch = options.showSearch !== false;

        // Attacher le listener pour les clics sur les emojis via délégation
        picker.addEventListener('click', handlePickerEmojiClick);

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
            searchInput.placeholder = L.SEARCH;
            searchInput.autocomplete = 'off';
            header.appendChild(searchInput);
        }

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'emoji-picker-close';
        closeBtn.title = L.CLOSE;
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
        frequentTitle.textContent = L.FREQUENTLY_USED;

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
     * Crée une cellule d'emoji (<button>) cliquable pour le picker.
     * 
     * SÉCURITÉ :
     * - L'emoji est nettoyé avec `safeEmoji()` avant d'être utilisé.
     * - Les attributs `data-emoji` et `data-post-id` sont utilisés pour la délégation d'événements.
     * 
     * @param {string} emoji L'emoji à afficher.
     * @param {number|string} postId L'ID du message cible.
     * @param {string} [name=''] Le nom descriptif de l'emoji (pour le `title` au survol).
     * @returns {HTMLElement} Bouton de la cellule emoji
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cleanEmoji = safeEmoji(String(emoji));
        
        const cell = document.createElement('button');
        cell.classList.add('emoji-cell');
        cell.textContent = cleanEmoji;
        cell.setAttribute('data-emoji', cleanEmoji);
        cell.title = name;
        cell.setAttribute('data-post-id', postId); // Ajout du post-id pour la délégation
        return cell;
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- RECHERCHE EMOJI --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Filtre la liste complète des emojis en fonction d'une requête textuelle.
     * 
     * SOURCES DE RECHERCHE (par ordre de priorité) :
     * 1. Mots-clés français (depuis `EMOJI_KEYWORDS_FR` si disponible).
     * 2. Nom officiel anglais de l'emoji (ex: "grinning face").
     * 3. L'emoji lui-même (permet de rechercher en collant un emoji dans le champ).
     * 
     * OPTIMISATIONS :
     * - La recherche est limitée à 100 résultats pour garantir de bonnes performances.
     * - Un `Set` est utilisé pour s'assurer que chaque emoji n'apparaît qu'une seule fois dans les résultats.
     * 
     * @param {string} query Le texte de recherche (doit être en minuscules).
     * @param {Object} emojiData L'objet contenant toutes les données des emojis.
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
     * Affiche les résultats de la recherche dans le conteneur approprié du picker.
     * 
     * @param {HTMLElement} container L'élément DOM où afficher les résultats.
     * @param {Array} results Le tableau d'objets emoji retourné par `searchEmojis`.
     * @param {number|string} postId L'ID du message, nécessaire pour créer les cellules d'emoji.
     */
    function displaySearchResults(container, results, postId) {
        container.innerHTML = '';

        if (results.length === 0) {
            const noResults = document.createElement('div');
            noResults.classList.add('emoji-no-results');
            noResults.textContent = L.NO_EMOJI_FOUND;
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
     * Construit un picker de secours si `categories.json` n'a pas pu être chargé.
     * 
     * Ce picker affiche uniquement les emojis de la liste `COMMON_EMOJIS` et un message
     * informant l'utilisateur que la liste complète n'est pas disponible.
     * @param {HTMLElement} picker Le conteneur du picker à remplir.
     * @param {number|string} postId L'ID du message cible.
     */
    function buildFallbackPicker(picker, postId) {
        // CORRECTION : Réutiliser la délégation d'événement pour la cohérence.
        picker.addEventListener('click', handlePickerEmojiClick);

        picker.innerHTML = `
            <div class="emoji-picker-header">
                <button type="button" class="emoji-picker-close" title="${L.CLOSE}" aria-label="Fermer"></button>
            </div>
            <div class="emoji-picker-body">
                <div class="emoji-frequent-section">
                    <div class="emoji-category-title">${L.FREQUENTLY_USED}</div>
                    <div class="emoji-grid"></div>
                </div>
                <div style="padding: 16px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0;">Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.</div>
            </div>
        `;

        const grid = picker.querySelector('.emoji-grid');
        COMMON_EMOJIS.forEach(emoji => grid.appendChild(createEmojiCell(emoji, postId)));

        picker.querySelector('.emoji-picker-close').addEventListener('click', (e) => { e.stopPropagation(); closeAllPickers(); });
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- FERMER PICKER / GESTION UI --------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Ferme le picker d'emojis actuellement ouvert.
     * 
     * Cette fonction est appelée par un écouteur global sur `document`.
     * Si un événement de clic est fourni, elle vérifie que le clic a eu lieu
     * en dehors du picker avant de le fermer.
     * @param {MouseEvent} [event] L'événement de clic qui a déclenché la fermeture (optionnel).
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
     * Vérifie si l'utilisateur est considéré comme connecté côté client.
     * 
     * La vérification se base sur la présence de la variable globale `REACTIONS_SID`,
     * qui est injectée dans la page par phpBB pour les utilisateurs connectés.
     * @important Cette vérification est une première barrière côté client ; la véritable validation d'authentification est effectuée côté serveur.
     * @returns {boolean} `true` si l'utilisateur est connecté, `false` sinon.
     */
    function isUserLoggedIn() {
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    /**
     * Affiche un message modal demandant la connexion
     *
     * Crée et affiche une boîte de dialogue modale simple pour informer les utilisateurs
     * non connectés qu'ils doivent se connecter pour interagir.
     * Le message se ferme automatiquement après 5 secondes ou lors d'un clic sur "OK".
     * @param {string} text Le message à afficher.
     */
    function showLoginMessage(text) {
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
            <p>${escapeHtml(text)}</p>
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
     * Envoie la requête AJAX pour ajouter ou retirer une réaction.
     * 
     * PROCESSUS :
     * 1. Vérifie l'authentification de l'utilisateur.
     * 2. Détermine l'action ('add' ou 'remove') en fonction de l'état actuel de la réaction dans le DOM.
     * 3. Prépare le payload JSON avec `post_id`, `emoji` (nettoyé), `action` et `sid`.
     * 4. Affiche un indicateur de chargement sur la réaction concernée.
     * 5. Envoie la requête `fetch` à l'endpoint AJAX.
     * 6. Traite la réponse :
     *    - Si succès et `data.html` est fourni, remplace le conteneur des réactions (méthode préférée).
     *    - Sinon, tente une mise à jour manuelle (fallback).
     * 7. Gère les erreurs (403, 400, 500, etc.) en affichant des messages appropriés.
     * 8. Retire l'indicateur de chargement.
     * 
     * @param {number|string} postId L'ID du message concerné.
     * @param {string} emoji L'emoji de la réaction.
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
            `.post-reactions-container[data-post-id="${postId}"] .reaction-wrapper[data-emoji="${cleanEmoji}"]`
        );

        // Détermine si l'utilisateur a déjà réagi (classe "active" sur le wrapper)
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
            console.log('[Reactions] Envoi payload:', payload);
        }
        // =====================================================================
        // AJOUT D'UN INDICATEUR DE CHARGEMENT
        // =====================================================================
        if (reactionElement) {
            reactionElement.classList.add('loading');
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
            // Si la réponse n'est pas OK (ex: 400, 403, 500), on veut quand même lire le JSON
            // pour récupérer le message d'erreur du serveur.
            if (!response.ok) {
                return response.json().then(errorData => {
                    // On propage une erreur enrichie avec les données du serveur.
                    const error = new Error(errorData.error || `HTTP ${response.status}`);
                    error.response = response;
                    error.data = errorData;
                    throw error;
                });
            }
            // Si la réponse est OK (200), on continue normalement.
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
                // Ce bloc est intentionnellement laissé vide.
                // Toutes les erreurs (4xx, 5xx) sont maintenant gérées par le bloc .catch()
                // pour une gestion centralisée et plus claire.
            }
        })
        .finally(() => {
            if (reactionElement) {
                reactionElement.classList.remove('loading');
            }
        })
        .catch(error => {
            // =====================================================================
            // GESTION DES ERREURS RÉSEAU OU EXCEPTIONS
            // =====================================================================
            
            console.error('[Reactions] Erreur lors de l\'envoi:', error);

            // Gestion des erreurs en fonction du code de statut HTTP
            const status = error.response ? error.response.status : null;
            const serverMessage = error.data ? error.data.error : null;

            switch (status) {
                case 403: // Forbidden
                    showLoginMessage();
                    break;
                case 429: // Too Many Requests (limite atteinte)
                case 400: // Bad Request (emoji invalide, etc.)
                    // Affiche le message d'erreur spécifique envoyé par le serveur.
                    alert(serverMessage || 'Une erreur de validation est survenue.');
                    break;
                default:
                    // Fallback pour les erreurs réseau ou les erreurs 500.
                    alert('Une erreur est survenue lors de la communication avec le serveur. Veuillez réessayer.');
                    break;
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- MISE À JOUR DU DOM APRÈS AJAX ------------------ */
    /* ---------------------------------------------------------------------- */

    /**
     * Met à jour manuellement l'affichage d'une seule réaction (méthode de secours).
     * 
     * Cette fonction est utilisée en fallback si la réponse AJAX ne contient pas
     * le bloc HTML complet (`data.html`). Elle modifie directement le DOM pour
     * refléter le nouvel état de la réaction.
     * 
     * ÉTAPES :
     * 1. Localise le conteneur de la réaction.
     * 2. Crée l'élément de réaction s'il n'existe pas.
     * 3. Met à jour le compteur et l'attribut `data-count`.
     * 4. Ajoute ou retire la classe `active` pour indiquer si l'utilisateur a réagi.
     * 5. Masque la réaction si son compteur tombe à zéro.
     * 
     * @param {number|string} postId L'ID du message.
     * @param {string} emoji L'emoji de la réaction.
     * @param {number} newCount Le nouveau nombre total de cette réaction.
     * @param {boolean} userHasReacted `true` si l'utilisateur courant a réagi.
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
        let wrapperElement = postContainer.querySelector(
            `.reaction-wrapper[data-emoji="${emoji}"]`
        );

        // =====================================================================
        // CAS 1 : LA RÉACTION N'EXISTE PAS ENCORE DANS LE DOM
        // =====================================================================
        
        if (!wrapperElement) {
            wrapperElement = document.createElement('div');
            wrapperElement.className = 'reaction-wrapper';
            wrapperElement.setAttribute('data-emoji', safeEmoji(String(emoji)));
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'reaction';
            button.innerHTML = `${safeEmoji(String(emoji))} <span class="count">0</span>`;
            wrapperElement.appendChild(button);
            
            // Insérer dans le DOM (avant le bouton "plus" si présent)
            const moreButton = postContainer.querySelector('.reaction-more');
            const reactionsContainer = postContainer.querySelector('.post-reactions');
            
            if (reactionsContainer) {
                if (moreButton) {
                    reactionsContainer.insertBefore(wrapperElement, moreButton);
                } else {
                    reactionsContainer.appendChild(wrapperElement);
                }
                // Ré-attacher les événements sur le nouveau wrapper
                setupReactionTooltip(wrapperElement, postId, emoji);
            } else {
                console.error('[Reactions] Impossible d\'insérer la nouvelle réaction');
                return;
            }
        }

        // Mettre à jour le compteur affiché
        const countSpan = wrapperElement.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = newCount;
        }

        // Mettre à jour l'attribut data-count
        wrapperElement.setAttribute('data-count', newCount);

        // Gestion de l'état actif (classe CSS "active")
        if (userHasReacted) {
            wrapperElement.classList.add('active');
        } else {
            wrapperElement.classList.remove('active');
        }

        // Masquer si compteur à zéro
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }
    }

    /* ---------------------------------------------------------------------- */
    /* ---------------------------- TOOLTIP USERS --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Configure les événements de survol pour afficher le tooltip des utilisateurs.
     * 
     * COMPORTEMENT :
     * - `mouseenter` : Après un court délai (300ms) pour éviter les affichages intempestifs,
     *   le tooltip est affiché.
     * - `mouseleave` : Le tooltip est masqué après un court délai, sauf si la souris
     *   se déplace sur le tooltip lui-même.
     * 
     * OPTIMISATION :
     * - Si l'attribut `data-users` est déjà présent sur l'élément, ses données sont utilisées directement.
     * - Sinon, une requête AJAX (`action: 'get_users'`) est envoyée pour récupérer la liste des utilisateurs.
     * 
     * @param {HTMLElement} reactionElement L'élément `.reaction-wrapper` sur lequel attacher les événements.
     * @param {number|string} postId L'ID du message.
     * @param {string} emoji L'emoji concerné.
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Nettoyer les anciens listeners (idempotence)
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        // Supprimer le title natif HTML (évite double affichage)
        reactionElement.querySelector('.reaction')?.removeAttribute('title'); // Le title est maintenant sur le wrapper

        // =====================================================================
        // ÉVÉNEMENT : MOUSE ENTER (SURVOL)
        // =====================================================================
        
        reactionElement.addEventListener('mouseenter', function(e) {
            clearTimeout(leaveTimeout);
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
            clearTimeout(tooltipTimeout); // Annule l'ouverture si pas encore affiché
            leaveTimeout = setTimeout(() => {
                hideUserTooltip();
            }, 200); // Délai avant de cacher
        });
    }

    /**
     * Crée et affiche le tooltip avec la liste des utilisateurs.
     * 
     * Le tooltip est une liste `<ul>` positionnée de manière absolue sous la réaction.
     * Chaque utilisateur est un lien vers son profil. Le tooltip reste visible
     * si l'utilisateur déplace sa souris dessus.
     * 
     * @param {HTMLElement} element L'élément de réaction de référence pour le positionnement.
     * @param {Array} users Un tableau d'objets `{user_id, username}`.
     */
    function showUserTooltip(element, users) {
        // Supprimer tout tooltip existant (un seul à la fois)
        hideUserTooltip();

        const tooltip = document.createElement('ul');
        tooltip.className = 'reaction-user-tooltip';

        // Construction HTML sécurisée (escape XSS)
        const userLinks = users.map(user =>
            `<li><a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" class="reaction-user-link" target="_blank">${escapeHtml(user.username)}</a></li>`
        ).join('');

        tooltip.innerHTML = userLinks || '<li><span class="no-users">Personne</span></li>';
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        // Positionnement sous l'élément
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = `${rect.bottom + window.scrollY}px`; // Collé en dessous
        tooltip.style.left = `${rect.left + window.scrollX}px`; // Alignement à gauche
        tooltip.style.transform = 'none'; // Pas de transformation
        tooltip.style.zIndex = '10001';

        // Garder visible si l'utilisateur survole le tooltip
        tooltip.addEventListener('mouseenter', () => {
            clearTimeout(leaveTimeout);
        });
        tooltip.addEventListener('mouseleave', () => {
            hideUserTooltip();
        });
    }

    /**
     * Masque et supprime le tooltip actuellement affiché.
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
     * Échappe les caractères HTML d'une chaîne de texte pour prévenir les attaques XSS.
     * 
     * Cette méthode robuste utilise les capacités natives du navigateur pour
     * convertir les caractères spéciaux (`<`, `>`, `&`, etc.) en leurs entités HTML.
     * @param {string} text Le texte à sécuriser.
     * @returns {string} Le texte sécurisé.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Récupère l'ID du message (`post_id`) à partir d'un élément enfant.
     * 
     * La fonction remonte l'arbre DOM depuis l'élément fourni jusqu'à trouver
     * le conteneur principal `.post-reactions-container` et lit son attribut `data-post-id`.
     * @param {HTMLElement} element L'élément DOM de départ (ex: un bouton de réaction).
     * @returns {string|null} L'ID du message, ou `null` s'il n'est pas trouvé.
     */
    function getPostIdFromReaction(element) {
        const container = element.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- BOOTSTRAP ON READY ------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Point d'entrée principal au chargement de la page.
     * 
     * Une fois le DOM entièrement chargé, cette fonction initialise les réactions
     * sur toute la page et démarre le mécanisme de synchronisation en temps réel.
     */
    document.addEventListener('DOMContentLoaded', () => {
        initReactions();
        startLiveSync();
    });

    /* ---------------------------------------------------------------------- */
    /* --------------------- SYNCHRONISATION TEMPS RÉEL ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * Démarre le processus de synchronisation automatique en temps réel.
     * 
     * Cette fonction configure un `setInterval` qui appellera `performLiveSync`
     * à un intervalle régulier défini dans les options (`syncInterval`).
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
     * Collecte tous les ID de messages visibles sur la page.
     * 
     * Scanne le DOM à la recherche de conteneurs de réactions et extrait
     * les `data-post-id` pour les envoyer au serveur lors de la synchronisation.
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
     * Exécute une requête de synchronisation vers le serveur.
     * 
     * Collecte les ID des messages visibles, envoie-les au serveur via une requête AJAX
     * (`action: 'sync'`), et met à jour le DOM avec les données fraîches reçues
     * en réponse. Empêche les requêtes concurrentes avec le flag `liveSyncInFlight`.
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
     * Applique les données de synchronisation à un conteneur de réaction spécifique.
     * 
     * Si le HTML reçu du serveur est différent du contenu actuel, il le remplace
     * et ré-initialise les écouteurs d'événements sur ce conteneur.
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
     * ### Problèmes courants et solutions
     * 
     * 1.  **Erreur 400 (Bad Request) lors de l'envoi :**
     *     - Cause probable : L'emoji contient des caractères invalides.
     *     - Solution : Vérifier que `safeEmoji()` est bien appliquée et nettoie correctement l'emoji. Inspecter le payload de la requête dans l'onglet "Réseau".
     * 
     * 2.  **Les écouteurs d'événements ne fonctionnent plus après un clic :**
     *     - Cause probable : Le DOM a été mis à jour par AJAX, mais les nouveaux éléments n'ont pas eu leurs écouteurs attachés.
     *     - Solution : S'assurer que `initReactions(container)` est appelé après chaque remplacement de `innerHTML`.
     * 
     * 3.  **Le picker d'emojis ne s'affiche pas ou est vide :**
     *     - Cause probable : Échec du chargement de `categories.json` (erreur 404, JSON invalide).
     *     - Solution : Vérifier le chemin `REACTIONS_JSON_PATH` et la validité du fichier JSON.
     * 
     * ### Optimisations possibles
     * 
     * -   **Indicateur de chargement :** Ajouter un spinner visuel pendant les requêtes AJAX pour améliorer le retour utilisateur.
     * -   **Cache pour les tooltips :** Utiliser `localStorage` pour mettre en cache les listes d'utilisateurs et réduire les appels AJAX `get_users`.
     * -   **Virtual Scrolling :** Pour le picker d'emojis, si la liste devient très grande, utiliser une technique de "virtual scrolling" pour n'afficher que les éléments visibles.
     * 
     * === COMPATIBILITÉ ===
     * 
     * - ES6+ requis (arrow functions, const/let, template literals)
     * - fetch() API requis (polyfill si support IE11)
     * - Testé sur Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
     * 
     * === SÉCURITÉ ===
     * 
     * - Toute logique côté client (ex: `isUserLoggedIn`) est une commodité pour l'UX, mais la VRAIE sécurité est assurée côté serveur.
     * - `escapeHtml()` est utilisé pour tout contenu généré par l'utilisateur (noms d'utilisateur) afin de prévenir les attaques XSS.
     * - `safeEmoji()` est utilisé pour nettoyer les données avant de les envoyer au serveur.
     */    
})(); // Fin IIFE (Immediately Invoked Function Expression)
