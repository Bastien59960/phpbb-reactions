/**
 * JavaScript pour l'extension Reactions
 *
 * Ce fichier gère toute l'interactivité côté client pour les réactions aux messages.
 * Il contient:
 *  - Gestion des clics sur les réactions existantes
 *  - Affichage de la palette d'emojis (picker)
 *  - Requêtes AJAX vers le serveur pour add/remove/get/get_users
 *  - Tooltips affichant la liste des utilisateurs ayant réagi
 *  - Recherche d'emojis avec support FR via EMOJI_KEYWORDS_FR
 *  - Diverses protections pour éviter les erreurs 400/500 côté serveur
 *
 * NOTES IMPORTANTES:
 *  - Le serveur attend du JSON UTF-8. Les emojis sont souvent multi-octets (UTF-8 mb4).
 *    Pour réduire les erreurs 400 liées à un mauvais encodage on nettoie les caractères
 *    de contrôle avant envoi: safeEmoji().
 *  - Le backend doit être configuré en utf8mb4 pour accepter les emojis 4-octets.
 *    Voir migrations / sql (ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 ...).
 *
 * Copyright (c) 2025 Bastien59960
 * Licence: GNU General Public License v2 (GPL-2.0)
 *
 * --------------------------------------------------------------------------
 * Structure du fichier:
 *  - Utilitaires généraux
 *  - Variables globales / constantes
 *  - Initialisation et attache des événements
 *  - Gestion du picker (construction + recherche)
 *  - Envoi AJAX (sendReaction)
 *  - Mise à jour DOM après réponse serveur (updateSingleReactionDisplay)
 *  - Tooltip utilisateur (setup, show, hide)
 *  - Fonctions utilitaires (escapeHtml, getPostIdFromReaction, etc.)
 * --------------------------------------------------------------------------
 */

/* ========================================================================== */
/* ========================= FONCTIONS UTILITAIRES ========================== */
/* ========================================================================== */

/**
 * Basculer la visibilité d'un élément
 *
 * @param {string} id ID de l'élément à basculer
 *
 * Cette fonction est volontairement simple et sert dans quelques petits cas
 * d'UI utilitaires. Elle n'est pas critique pour la logique des réactions,
 * mais facilite certains tests manuels.
 */
function toggle_visible(id) {
    var x = document.getElementById(id);
    if (!x) {
        // Si l'élément n'existe pas, laisser silencieusement
        return;
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

    // Palette d'emojis actuellement ouverte (DOM element) — null si aucune
    let currentPicker = null;

    // Tooltip courant affichant les users — null si aucune
    let currentTooltip = null;

    // Contenu JSON chargé depuis categories.json (structure emojiData)
    let allEmojisData = null;

    /**
     * Liste des 10 emojis courantes utilisées par défaut
     *
     * Ces emojis sont affichés en priorité dans l'interface utilisateur.
     * Ils doivent idéalement être synchronisés avec la config serveur
     * (ajax.php / listener.php) si tu veux une correspondance exacte.
     *
     * NOTE: Conserver les emojis courantes ici évite un fetch inutile si le JSON
     * categories.json n'est pas accessible (fallback).
     */
    const COMMON_EMOJIS = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    /* ---------------------------------------------------------------------- */
    /* ------------------------- FONCTIONS D'AIDE EMOJI ---------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * safeEmoji(e)
     *
     * Nettoie une chaîne emoji pour retirer les caractères de contrôle non
     * imprimables qui peuvent casser json_encode / json_decode côté serveur ou
     * provoquer des erreurs 400 si le JSON contient des octets invalides.
     *
     * - Retire les caractères de contrôle ASCII dans les plages communes
     * - Ne modifie pas les séquences valides UTF-8 pour les emojis (ZWJ, skin
     *   tone, etc.)
     *
     * @param {string} e Chaîne pouvant contenir un emoji
     * @returns {string} Chaîne nettoyée
     *
     * Raison: certaines palettes (ou anciennes bibliothèques) insèrent des
     * caractères invisibles ou des retours chariot qui, lorsqu'enveloppés dans
     * JSON, aboutissent à un json_decode() rejeté côté PHP si l'encodage n'est
     * pas propre.
     */
    function safeEmoji(e) {
        if (typeof e !== 'string') {
            // Forcer la conversion en chaîne pour éviter exceptions
            e = String(e || '');
        }
        // Enlève caractères de contrôle (sauf tab/newline si jamais utile — ici on les retire tous)
        // Plage: 0x00..0x08, 0x0B,0x0C, 0x0E..0x1F, 0x7F
        return e.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------- INITIALISATION & EVENTS ----------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * initReactions()
     *
     * Point d'entrée principal appelé au DOMContentLoaded. Il attache tous les
     * écouteurs nécessaires: clics sur réactions, clics sur "plus", tooltips,
     * et fermeture des pickers au clic général.
     */
    function initReactions() {
        // Attache événements sur les réactions affichées initialement
        attachReactionEvents();

        // Attache événements sur les boutons "plus" pour ouvrir le picker
        attachMoreButtonEvents();

        // Attache les tooltips (hover) pour chaque réaction (liste utilisateurs)
        attachTooltipEvents();

        // Fermeture globale des pickers si on clique ailleurs
        document.addEventListener('click', closeAllPickers);
    }

    /**
     * attachReactionEvents()
     *
     * Recherche tous les éléments .post-reactions .reaction (sauf readonly) et
     * attache un listener click. On retire d'abord d'éventuels anciens listeners
     * pour éviter la double exécution (re-render, hot-reload, etc.).
     */
    function attachReactionEvents() {
        document.querySelectorAll('.post-reactions .reaction:not(.reaction-readonly)').forEach(reaction => {
            // Retirer avant d'ajouter: pattern idempotent
            reaction.removeEventListener('click', handleReactionClick);
            reaction.addEventListener('click', handleReactionClick);
        });
    }

    /**
     * attachMoreButtonEvents()
     *
     * Attache l'écouteur sur le bouton "..." (ou similaire) qui ouvre la
     * palette d'emojis pour ajouter une réaction personnalisée.
     */
    function attachMoreButtonEvents() {
        document.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    /**
     * attachTooltipEvents()
     *
     * Parcourt toutes les réactions et configure le tooltip hover pour afficher
     * la liste des utilisateurs ayant cliqué sur la réaction.
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

    /* ---------------------------------------------------------------------- */
    /* -------------------------- HANDLERS CLICK ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * handleReactionClick(event)
     *
     * Gestion du clic sur une réaction existante (ex: un badge "👍 2").
     * - Empêche propagation et default
     * - Récupère postId & emoji depuis le DOM
     * - Vérifie que l'utilisateur est loggé
     * - Appelle sendReaction()
     *
     * Note: on se base sur l'attribut data-emoji qui contient la version affichée
     * (doit coller aux valeurs envoyées au serveur).
     */
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

        // IMPORTANT: envoi via sendReaction qui applique safeEmoji
        sendReaction(postId, emoji);
    }

    /**
     * handleMoreButtonClick(event)
     *
     * Ouvre la palette (picker) d'emojis pour le post ciblé. Le picker est
     * construit dynamiquement à partir d'un JSON categories.json si disponible,
     * sinon on propose un fallback minimal (COMMON_EMOJIS).
     */
    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // Fermer les pickers déjà ouverts (un seul picker à la fois)
        closeAllPickers();

        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        if (!postId) return;

        // Crée le conteneur picker
        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        currentPicker = picker;


        
        // Essaye de charger la version complète des emojis (catégories)
        fetch('./ext/bastien59960/reactions/styles/prosilver/theme/categories.json')
            .then(res => {
                // Si le status est 404 ou autre, res.json() jettera ou renverra une erreur
                if (!res.ok) {
                    throw new Error('categories.json not found or network error');
                }
                return res.json();
            })
            .then(data => {
                allEmojisData = data;
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => {
                // Si erreur de chargement, on affiche un picker restreint (COMMON_EMOJIS)
                console.error('Erreur de chargement categories.json', err);
                buildFallbackPicker(picker, postId);
            });

        // Ajout dans le body pour positionnement absolu
        document.body.appendChild(picker);

        // Position du picker par rapport au bouton
        const rect = button.getBoundingClientRect();
        picker.style.position = 'absolute';
        picker.style.top = `${rect.bottom + window.scrollY}px`;
        picker.style.left = `${rect.left + window.scrollX}px`;
        picker.style.zIndex = 10000;
    }

    /* ---------------------------------------------------------------------- */
    /* ---------------------------- BUILD PICKER ---------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * buildEmojiPicker(picker, postId, emojiData)
     *
     * Construit le DOM complet du picker:
     *  - onglets catégories
     *  - header (recherche + fermeture)
     *  - section "Utilisé fréquemment"
     *  - grille principale par catégories
     *  - zone de recherche (affiche remplacements dynamiques)
     *
     * Remarques:
     *  - emojiData.emojis attendu au format: { categoryName: { subcat: [{ emoji, name }, ...] } }
     *  - la recherche utilise searchEmojis() qui supporte EMOJI_KEYWORDS_FR
     */
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

        // Parcours des catégories fournies par categories.json
        Object.entries(emojiData.emojis).forEach(([category, subcategories]) => {
            const catTitle = document.createElement('div');
            catTitle.className = 'emoji-category-title';
            catTitle.textContent = category;
            catTitle.dataset.categoryName = category;
            categoriesContainer.appendChild(catTitle);

            const grid = document.createElement('div');
            grid.className = 'emoji-grid';

            // subcategories est typiquement un objet contenant des listes
            Object.values(subcategories).flat().forEach(emojiObj => {
                // emojiObj: { emoji: '😄', name: 'smile' }
                grid.appendChild(createEmojiCell(emojiObj.emoji, postId, emojiObj.name));
            });
            categoriesContainer.appendChild(grid);
        });

        mainContent.appendChild(categoriesContainer);
        pickerBody.appendChild(mainContent);

        // Conteneur pour les résultats de recherche (remplace la vue principale)
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
                    mainContent.scrollTop = 0; // Remonte la liste principale
                } else {
                    // On tente de matcher l'index des catégories fournies
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
        // Taper dans la recherche filtre les emojis via searchEmojis()
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

        // Met le focus sur la zone de recherche après ouverture
        setTimeout(() => searchInput.focus(), 50);
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- CRÉATEURS DOM ----------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * createEmojiCell(emoji, postId, name='')
     *
     * Crée un élément bouton qui représente une cellule d'emoji dans le picker.
     * - emoji: chaîne affichée
     * - postId: id du post cible (sera passée à sendReaction)
     * - name: texte descriptif (title)
     *
     * Le click sur la cellule envoie la réaction au serveur et ferme le picker.
     */
    function createEmojiCell(emoji, postId, name = '') {
        const cleanEmoji = safeEmoji(String(emoji)); // 🧩 FIX : normalisation côté client
        const cell = document.createElement('button');
        cell.classList.add('emoji-cell');
        cell.textContent = cleanEmoji;               // affiche la version nettoyée
        cell.setAttribute('data-emoji', cleanEmoji); // important: stocker la valeur "propre"
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
     * searchEmojis(query, emojiData)
     *
     * Recherche dans la structure emojiData et retourne une liste d'objets emojiObj.
     * Supporte la table EMOJI_KEYWORDS_FR (optionnelle) pour permettre recherche FR.
     *
     * Retour: Array d'emojiObj { emoji, name, ... }
     */
    function searchEmojis(query, emojiData) {
        const results = new Set(); // Evite doublons en fonction de l'objet
        const maxResults = 100;

        // Table de mots-clés français (optionnelle injectée globalement)
        const keywordsFr = typeof EMOJI_KEYWORDS_FR !== 'undefined' ? EMOJI_KEYWORDS_FR : {};

        // Flatten: récupérer tous emojiObj dans emojiData.emojis
        // Structure attendue: emojiData.emojis = { cat: { subcat: [emojiObj...] } }
        const allEmojis = Object.values(emojiData.emojis).flatMap(Object.values).flat();

        for (const emojiObj of allEmojis) {
            if (results.size >= maxResults) break;

            // Sécurité: s'assurer que emojiObj possède la structure attendue
            if (!emojiObj || !emojiObj.emoji) continue;

            // Recherche via mots-clés FR si disponibles
            if (keywordsFr[emojiObj.emoji] && keywordsFr[emojiObj.emoji].some(kw => kw.toLowerCase().includes(query))) {
                results.add(emojiObj);
                continue;
            }

            // Recherche par nom anglais
            if (emojiObj.name && emojiObj.name.toLowerCase().includes(query)) {
                results.add(emojiObj);
                continue;
            }

            // Recherche par emoji littéral (utile si l'utilisateur colle un emoji)
            if (emojiObj.emoji && emojiObj.emoji.includes(query)) {
                results.add(emojiObj);
            }
        }

        return Array.from(results);
    }

    /**
     * displaySearchResults(container, results, postId)
     *
     * Affiche dans le DOM les résultats renvoyés par searchEmojis().
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
     * buildFallbackPicker(picker, postId)
     *
     * Si categories.json est indisponible, on propose une version basique
     * contenant seulement COMMON_EMOJIS. Utile pour clusters qui bloquent
     * l'accès au JSON (permissions, CSP, etc).
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
     * closeAllPickers(event)
     *
     * Si un picker est ouvert et que le clic est en dehors de celui-ci, le ferme.
     * - event peut être undefined si fermeture programmée
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
     * isUserLoggedIn()
     *
     * Vérifie la présence de la variable globale REACTIONS_SID injectée depuis phpBB.
     * Si sid vide ou pas défini, on considère que l'utilisateur n'est pas loggué.
     *
     * Remarque: côté serveur phpBB devrait effectuer la vraie validation.
     */
    function isUserLoggedIn() {
        return typeof REACTIONS_SID !== 'undefined' && REACTIONS_SID !== '';
    }

    /**
     * showLoginMessage()
     *
     * Affiche un petit message modal invitant à la connexion.
     * Permet d'éviter des erreurs côté serveur (403) en informant l'utilisateur.
     */
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
            <button class="reactions-login-dismiss" style="margin-top: 10px; padding: 5px 15px;">OK</button>
        `;
        document.body.appendChild(message);

        // Ferme au clic sur le bouton OK
        message.querySelector('.reactions-login-dismiss').addEventListener('click', () => {
            if (message.parentNode) message.parentNode.removeChild(message);
        });

        // Fermeture automatique au timeout si jamais on veut masquer le message
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
     * sendReaction(postId, emoji)
     *
     * Envoie une requête AJAX vers le backend (REACTIONS_AJAX_URL) pour ajouter ou
     * retirer une réaction selon l'état courant.
     *
     * - Recherche l'élément réaction existant dans le DOM (data-emoji)
     * - Détermine l'action: 'add' ou 'remove'
     * - Envoie JSON sécurisé: { post_id, emoji, action, sid }
     *
     * PROTECTION:
     *  - SAFE: on applique safeEmoji() to avoid invalid bytes that produce 400.
     *  - HEADERS: Content-Type/Accept pour clarifier que l'on veut JSON en retour.
     *
     * @param {string|number} postId Id du message
     * @param {string} emoji Emoji (peut être multi-octet)
     */
    function sendReaction(postId, emoji) {
        // REACTIONS_SID injectée serveur => si absente on loggue et on force string vide
        if (typeof REACTIONS_SID === 'undefined') {
            console.error('REACTIONS_SID is not defined');
            REACTIONS_SID = '';
        }

        if (!isUserLoggedIn()) {
            showLoginMessage();
            return;
        }

        // 🧩 FIX: Nettoyage des emojis avant envoi pour éviter erreur 400
        const cleanEmoji = safeEmoji(String(emoji));

        // Trouve l'élément réaction correspondant (s'il existe)
        const reactionElement = document.querySelector(`.post-reactions-container[data-post-id="${postId}"] .reaction[data-emoji="${cleanEmoji}"]:not(.reaction-readonly)`);
        const hasReacted = reactionElement && reactionElement.classList.contains('active');
        const action = hasReacted ? 'remove' : 'add';

        // Construction du payload JSON
        const payload = {
            post_id: postId,
            emoji: cleanEmoji,
            action: action,
            sid: REACTIONS_SID
        };

        // DEBUG: log utile pour reproduire en local (à enlever en prod)
        // console.debug('Sending reaction payload:', payload);

        // Envoi du fetch (JSON)
        console.debug('REACTIONS payload', payload)

        fetch(REACTIONS_AJAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', // serveur attendu
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            // Gestion des statuts HTTP
            if (!response.ok) {
                if (response.status === 403) {
                    // L'utilisateur n'est pas authentifié (ou sid invalide)
                    showLoginMessage();
                    throw new Error('User not logged in');
                }
                // Autres erreurs réseau/serveur
                throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            // data attendu: { success: bool, count: int, user_reacted: bool, ... }
            if (data.success) {
                // Mise à jour du DOM: compteur et état actif/inactif
                updateSingleReactionDisplay(postId, cleanEmoji, data.count, data.user_reacted);
            } else {
                // Si serveur renvoie success=false on loggue le message
                console.error('Erreur de réaction :', data.error || data.message);
                // Si message indique problème d'auth on propose le login
                if (data.error && data.error.toLowerCase().includes('logged in')) {
                    showLoginMessage();
                }
            }
        })
        .catch(error => {
            // Erreurs réseau ou internes au then()
            console.error('Erreur AJAX (sendReaction):', error);
        });
    }

    /* ---------------------------------------------------------------------- */
    /* --------------------- MISE A JOUR DU DOM APRÈS AJAX ------------------ */
    /* ---------------------------------------------------------------------- */

    /**
     * updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted)
     *
     * Met à jour l'affichage d'une réaction unique pour un post:
     * - Si la réaction n'existe pas encore, la crée (span.reaction)
     * - Met à jour le compteur affiché
     * - Ajoute/enlève la classe 'active' selon userHasReacted
     * - Cache l'élément si newCount === 0
     * - Ré-attache le tooltip avec la nouvelle info
     *
     * IMPORTANT:
     *  - On travaille uniquement sur le container .post-reactions-container[data-post-id=...]
     *  - Les actuelles classes CSS (reaction, reaction-readonly, reaction-more) sont utilisées
     */
    function updateSingleReactionDisplay(postId, emoji, newCount, userHasReacted) {
        const postContainer = document.querySelector(`.post-reactions-container[data-post-id="${postId}"]:not(.post-reactions-readonly)`);
        if (!postContainer) return;

        // On tente de trouver l'élément reaction correspondant (si déjà existant)
        let reactionElement = postContainer.querySelector(`.reaction[data-emoji="${emoji}"]:not(.reaction-readonly)`);

        if (!reactionElement) {
            // Si introuvable, on crée et on l'insère avant/à côté du bouton "plus"
            reactionElement = document.createElement('span');
            reactionElement.classList.add('reaction');
            reactionElement.setAttribute('data-emoji', safeEmoji(String(emoji)));
            reactionElement.innerHTML = `${safeEmoji(String(emoji))} <span class="count">0</span>`;
            // Attacher le click pour mutualiser la logique (handleReactionClick)
            reactionElement.addEventListener('click', handleReactionClick);

            const moreButton = postContainer.querySelector('.reaction-more');
            if (moreButton) {
                if (moreButton.nextSibling) {
                    moreButton.parentNode.insertBefore(reactionElement, moreButton.nextSibling);
                } else {
                    moreButton.parentNode.appendChild(reactionElement);
                }
            } else {
                // Si pas de bouton "plus", on ajoute à la liste des reactions
                const reactionsList = postContainer.querySelector('.post-reactions');
                if (reactionsList) {
                    reactionsList.appendChild(reactionElement);
                }
            }
        }

        // Mettre à jour le compteur
        const countSpan = reactionElement.querySelector('.count');
        if (countSpan) {
            countSpan.textContent = newCount;
        }

        // Mettre à jour l'attribut data-count (utile si d'autres scripts le lisent)
        reactionElement.setAttribute('data-count', newCount);

        // Gestion de l'état actif (user a réagi)
        if (userHasReacted) {
            reactionElement.classList.add('active');
        } else {
            reactionElement.classList.remove('active');
        }

        // Cacher si 0
        if (newCount === 0) {
            reactionElement.style.display = 'none';
        } else {
            reactionElement.style.display = '';
        }

        // CORRIGÉ : Un seul tooltip par réaction — (ré-attache/update)
        setupReactionTooltip(reactionElement, postId, emoji);
    }

    /* ---------------------------------------------------------------------- */
    /* ---------------------------- TOOLTIP USERS --------------------------- */
    /* ---------------------------------------------------------------------- */

    /**
     * setupReactionTooltip(reactionElement, postId, emoji)
     *
     * Configure le hover sur une réaction pour afficher la liste des users.
     * - On supprime le title natif (qui peut gêner)
     * - On attend 300ms avant de déclencher l'appel (évite flicker)
     * - Si data-users est présent (préchargé), on l'utilise; sinon appel AJAX
     *
     * Amélioration: on nettoie l'emoji avec safeEmoji avant de l'envoyer au serveur
     * pour la requête get_users (évite 400).
     */
    function setupReactionTooltip(reactionElement, postId, emoji) {
        let tooltipTimeout;

        // Retirer anciens listeners (au cas où)
        reactionElement.onmouseenter = null;
        reactionElement.onmouseleave = null;

        // Supprimer le title natif pour ne pas avoir l'infobulle du navigateur
        reactionElement.removeAttribute('title');

        reactionElement.addEventListener('mouseenter', function(e) {
            // Déclenche la requête après un petit délai (300ms)
            tooltipTimeout = setTimeout(() => {
                // 1) Si data-users est présent et non vide, on l'utilise (évite appel)
                const usersData = reactionElement.getAttribute('data-users');
                if (usersData && usersData !== '[]') {
                    try {
                        const users = JSON.parse(usersData);
                        if (users && users.length > 0) {
                            showUserTooltip(reactionElement, users);
                            return; // on a affiché les users
                        }
                    } catch (err) {
                        // Si parsing échoue, on retombe sur l'appel AJAX
                        console.error('Erreur parsing users data:', err);
                    }
                }

                // 2) Sinon: appel AJAX "get_users"
                // 🧩 FIX: emoji nettoyé avant requête
                const cleanEmoji = safeEmoji(String(emoji));

                const payload = {
                    post_id: postId,
                    emoji: cleanEmoji,
                    action: 'get_users',
                    sid: REACTIONS_SID
                };

                fetch(REACTIONS_AJAX_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network error: ' + res.status);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success && Array.isArray(data.users) && data.users.length > 0) {
                        showUserTooltip(reactionElement, data.users);
                    } else {
                        // Pas d'erreurs graves: on n'affiche rien si aucune user
                    }
                })
                .catch(err => {
                    console.error('Erreur chargement users:', err);
                    // Pas d'UI intrusive si échec fetch (évite spam)
                });

            }, 300);
        });

        reactionElement.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            hideUserTooltip();
        });
    }

    /**
     * showUserTooltip(element, users)
     *
     * Affiche la tooltip au-dessus de la réaction avec la liste d'utilisateurs.
     * - users: array d'objets { user_id, username }
     * - La tooltip est positionnée sous l'élément reaction (bottom)
     */
    function showUserTooltip(element, users) {
        // Supprime tout tooltip existant (un seul ui global)
        hideUserTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'reaction-user-tooltip';

        // Construire HTML sûr (escape usernames)
        const userLinks = users.map(user =>
            `<a href="./memberlist.php?mode=viewprofile&u=${user.user_id}" class="reaction-user-link" target="_blank">${escapeHtml(user.username)}</a>`
        ).join('');

        tooltip.innerHTML = userLinks || '<span class="no-users">Personne</span>';
        document.body.appendChild(tooltip);
        currentTooltip = tooltip;

        // Positionnement simple: aligner à gauche de l'élément et sous celui-ci
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
        tooltip.style.left = `${rect.left + window.scrollX}px`;
        tooltip.style.zIndex = '10001';

        // Garder le tooltip visible si l'utilisateur y survole la souris
        tooltip.addEventListener('mouseenter', () => {});
        tooltip.addEventListener('mouseleave', () => {
            hideUserTooltip();
        });
    }

    /**
     * hideUserTooltip()
     *
     * Retire la tooltip courante si elle est affichée.
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
     * escapeHtml(text)
     *
     * Échappe le texte pour insertions dans le DOM via innerHTML.
     * On utilise une balise temporaire pour s'assurer du bon échappement.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * getPostIdFromReaction(el)
     *
     * Navigue vers l'ancêtre .post-reactions-container et lit data-post-id
     * Retourne null si introuvable
     */
    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    /* ---------------------------------------------------------------------- */
    /* -------------------------- BOOTSTRAP ON READY ------------------------- */
    /* ---------------------------------------------------------------------- */

    // Initialisation à l'événement DOMContentLoaded
    document.addEventListener('DOMContentLoaded', initReactions);

    /* ====================================================================== */
    /* ========== FIN DU MODULE PRINCIPAL - COMMENTAIRES ADDITIONNELS ======= */
    /* ====================================================================== */

    /*
     * NOTES DE DÉPLOIEMENT & DEBUG:
     *
     * - Si tu obtiens toujours une erreur 400 lors de l'envoi via le picker:
     *     1) Ajoute un console.log('raw payload', JSON.stringify(payload)) avant fetch
     *     2) Vérifie dans les logs Apache/PHP ce que reçoit le serveur (raw bytes)
     *     3) Vérifie la présence de REACTIONS_AJAX_URL et REACTIONS_SID dans le scope global
     *
     * - Si tu obtiens une erreur 500 lors d'un add (emoji 4-octet):
     *     1) Vérifie la collation de la table phpbb_post_reactions: elle doit être utf8mb4
     *     2) Exécute: ALTER TABLE phpbb_post_reactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     *     3) Vérifie la définition de reaction_emoji (VARCHAR(191) recommandé si indexée)
     *
     * - Pour debug rapide côté client:
     *     - Ouvre la console réseau (Network) -> requête POST vers REACTIONS_AJAX_URL
     *     - Vérifie Request payload et Response body (json)
     *
     * - Remarques de sécurité:
     *     - Ne jamais faire confiance au sid côté client: le serveur doit valider
     *       l'utilisateur, le token, et les permissions.
     *     - Eviter d'exposer des données sensibles dans les tooltips (ne montrer
     *       que username et un lien public).
     *
     * - Améliorations possibles:
     *     - Debounce sur la recherche du picker (déjà présent par input)
     *     - Mécanisme de caching côté client pour les get_users par post+emoji
     *     - Indication visuelle d'envoi/chargement (spinner) au clic sur reaction
     *
     */

})(); // fin IIFE (module reactions)

