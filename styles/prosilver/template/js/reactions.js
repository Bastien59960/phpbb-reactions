(function () {
    'use strict';

    let currentPicker = null;

    // ---------- Initialisation ----------
    function initReactions() {
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
    }

    function attachReactionEvents() {
        document.querySelectorAll('.post-reactions .reaction').forEach(reaction => {
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
        const emoji = el.getAttribute('data-unicode') || el.getAttribute('data-emoji');
        const postId = getPostIdFromReaction(el);
        if (!emoji || !postId) return;

        const isActive = el.classList.contains('active');

        fetch('/forum/reaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                post_id: postId,
                emoji: emoji,
                action: isActive ? 'remove' : 'add'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) updateReactionElement(el, data.count, !isActive);
            else console.error('Erreur serveur:', data.message);
        })
        .catch(err => console.error('Erreur AJAX:', err));
    }

    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        closeAllPickers();

        const button = event.currentTarget;
        const postId = getPostIdFromReaction(button);
        if (!postId) return;

        // Création du picker flottant
        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');
        currentPicker = picker;

        // Charger emojis.json depuis prosilver
        fetch('styles/prosilver/theme/emojis.json')
            .then(res => res.json())
            .then(data => {
                buildEmojiPicker(picker, postId, data);
            })
            .catch(err => console.error('Erreur de chargement emojis.json', err));

        document.body.appendChild(picker);

        // Positionnement sous le bouton
        const rect = button.getBoundingClientRect();
        picker.style.position = 'absolute';
        picker.style.top = `${rect.bottom + window.scrollY}px`;
        picker.style.left = `${rect.left + window.scrollX}px`;
        picker.style.zIndex = 10000;
    }

    function buildEmojiPicker(picker, postId, emojiData) {
        for (const [category, emojis] of Object.entries(emojiData)) {
            // Titre catégorie
            const catTitle = document.createElement('div');
            catTitle.classList.add('emoji-category');
            catTitle.textContent = category;
            picker.appendChild(catTitle);

            // Grid 10 colonnes
            const grid = document.createElement('div');
            grid.classList.add('emoji-grid');

            emojis.forEach(emoji => {
                const cell = document.createElement('div');
                cell.classList.add('emoji-cell');
                cell.textContent = emoji;
                cell.addEventListener('click', () => {
                    addReaction(postId, emoji);
                    closeAllPickers();
                });
                grid.appendChild(cell);
            });

            picker.appendChild(grid);
        }
    }

    function closeAllPickers() {
        if (currentPicker) {
            currentPicker.remove();
            currentPicker = null;
        }
    }

    function addReaction(postId, emoji) {
        const container = document.querySelector(`.post-reactions-container[data-post-id="${postId}"] .post-reactions`);
        if (!container) return;

        let reaction = container.querySelector(`[data-unicode="${emoji}"], [data-emoji="${emoji}"]`);
        if (!reaction) {
            reaction = createReactionElement(emoji);
            const moreBtn = container.querySelector('.reaction-more');
            container.insertBefore(reaction, moreBtn);
        }

        // Simuler un click pour envoyer la réaction au serveur
        reaction.click();
    }

    function createReactionElement(emoji) {
        const div = document.createElement('div');
        div.classList.add('reaction');
        div.setAttribute('data-unicode', emoji);
        div.innerHTML = `<span class="emoji">${emoji}</span><span class="count">0</span>`;
        div.addEventListener('click', handleReactionClick);
        return div;
    }

    function updateReactionElement(el, count, active) {
        const countSpan = el.querySelector('.count');
        if (countSpan) countSpan.textContent = count > 0 ? count : '0';
        el.classList.toggle('active', active);
        el.setAttribute('data-count', count);
    }

    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    document.addEventListener('DOMContentLoaded', initReactions);

})();
