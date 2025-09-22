/**
 * Extension Reactions pour phpBB 3.3.15
 * JavaScript complet - gère picker, affichage par défaut, AJAX et MAJ DOM
 */
(function () {
    'use strict';

    // ---------- Configuration ----------
    const DEFAULT_EMOJIS = ['👍', '❤️', '😂', '😮', '😢']; // toujours visibles (00 si count=0)
    const EMOJI_PALETTE = [
        '😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊',
        '😋','😎','😍','😘','🥰','😗','😙','😚','😇','🥳',
        '😈','👿','😠','😡','🤬','😱','😰','😨','😧','😦',
        '😮','😯','😲','🤯','😳','🥺','😢','😭','😤','😪',
        '👍','👎','👌','✌️','🤞','👏','🙌','👐','🤲','🙏',
        '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔',
        '💯','💥','💢','💨','💫','⭐','🌟','✨','⚡','🔥'
    ];

    let currentPickerId = null;

    console.log('Reactions JS — boot');

    // ---------- Initialisation ----------
    function initReactions() {
        ensureDefaultReactions();
        attachReactionEvents();
        attachMoreButtonEvents();
        document.addEventListener('click', closeAllPickers);
        console.log('Reactions extension initialisée');
    }

    // ---------- Ensure default emojis (always visible with "00" if count 0) ----------
    function ensureDefaultReactions() {
        document.querySelectorAll('.post-reactions-container').forEach(container => {
            const reactionContainer = container.querySelector('.post-reactions');
            if (!reactionContainer) return;

            DEFAULT_EMOJIS.forEach(emoji => {
                let existing = reactionContainer.querySelector(`[data-unicode="${emoji}"], [data-emoji="${emoji}"]`);
                if (!existing) {
                    const reaction = createReactionElement(emoji, 0, false, true);
                    reaction.setAttribute('data-unicode', emoji);
                    reaction.setAttribute('data-emoji', emoji);

                    const moreBtn = reactionContainer.querySelector('.reaction-more');
                    if (moreBtn) {
                        reactionContainer.insertBefore(reaction, moreBtn);
                    } else {
                        reactionContainer.appendChild(reaction);
                    }

                    reaction.addEventListener('click', handleReactionClick);
                    reaction.addEventListener('mouseenter', showTooltip);
                    reaction.addEventListener('mouseleave', hideTooltip);
                } else {
                    const countSpan = existing.querySelector('.count');
                    if (countSpan && (countSpan.textContent === '' || countSpan.textContent === '0')) {
                        countSpan.textContent = '00';
                        existing.setAttribute('data-count', 0);
                    }
                    existing.classList.add('default-reaction');
                }
            });
        });
    }

    // ---------- Events attachment ----------
    function attachReactionEvents() {
        document.querySelectorAll('.post-reactions .reaction').forEach(reaction => {
            reaction.removeEventListener('click', handleReactionClick);
            reaction.addEventListener('click', handleReactionClick);
            reaction.removeEventListener('mouseenter', showTooltip);
            reaction.addEventListener('mouseenter', showTooltip);
            reaction.removeEventListener('mouseleave', hideTooltip);
            reaction.addEventListener('mouseleave', hideTooltip);
        });
    }

    function attachMoreButtonEvents() {
        document.querySelectorAll('.reaction-more').forEach(button => {
            button.removeEventListener('click', handleMoreButtonClick);
            button.addEventListener('click', handleMoreButtonClick);
        });
    }

    // ---------- Handlers ----------
    function handleReactionClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const el = event.currentTarget;
        const emoji = el.getAttribute('data-unicode') || el.getAttribute('data-emoji');
        const postId = getPostIdFromReaction(el);

        if (!emoji || !postId) {
            console.error('Impossible de déterminer emoji ou postId (handleReactionClick)');
            return;
        }

        const isActive = el.classList.contains('active');

        // Appel AJAX vers PHPBB (endpoint à adapter côté extension)
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
            if (data.success) {
                updateReactionElement(el, data.count, !isActive);
            } else {
                console.error('Erreur serveur:', data.message);
            }
        })
        .catch(err => console.error('Erreur AJAX:', err));
    }

    function handleMoreButtonClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const container = button.closest('.post-reactions-container');
        const postId = getPostIdFromReaction(button);

        closeAllPickers();

        const picker = createEmojiPicker(postId);
        container.appendChild(picker);
        currentPickerId = postId;
    }

    function closeAllPickers() {
        document.querySelectorAll('.emoji-picker').forEach(picker => picker.remove());
        currentPickerId = null;
    }

    // ---------- Utils ----------
    function getPostIdFromReaction(el) {
        const container = el.closest('.post-reactions-container');
        return container ? container.getAttribute('data-post-id') : null;
    }

    function createReactionElement(emoji, count = 0, active = false, isDefault = false) {
        const div = document.createElement('div');
        div.classList.add('reaction');
        if (active) div.classList.add('active');
        if (isDefault) div.classList.add('default-reaction');

        div.setAttribute('data-unicode', emoji);
        div.setAttribute('data-count', count);

        div.innerHTML = `
            <span class="emoji">${emoji}</span>
            <span class="count">${count > 0 ? count : (isDefault ? '00' : '')}</span>
        `;
        return div;
    }

    function updateReactionElement(el, count, active) {
        const countSpan = el.querySelector('.count');
        if (countSpan) countSpan.textContent = count > 0 ? count : '00';
        el.setAttribute('data-count', count);
        el.classList.toggle('active', active);
    }

    function createEmojiPicker(postId) {
        const picker = document.createElement('div');
        picker.classList.add('emoji-picker');

        EMOJI_PALETTE.forEach(emoji => {
            const btn = document.createElement('button');
            btn.classList.add('emoji-choice');
            btn.textContent = emoji;
            btn.addEventListener('click', () => {
                const container = document.querySelector(`.post-reactions-container[data-post-id="${postId}"] .post-reactions`);
                let reaction = container.querySelector(`[data-unicode="${emoji}"], [data-emoji="${emoji}"]`);

                if (!reaction) {
                    reaction = createReactionElement(emoji, 0, false, false);
                    container.insertBefore(reaction, container.querySelector('.reaction-more'));
                    attachReactionEvents();
                }
                reaction.click();
                closeAllPickers();
            });
            picker.appendChild(btn);
        });

        return picker;
    }

    function showTooltip(event) {
        const el = event.currentTarget;
        const count = el.getAttribute('data-count');
        el.setAttribute('title', `${count} réaction(s)`);
    }

    function hideTooltip(event) {
        event.currentTarget.removeAttribute('title');
    }

    // Lancer init une fois le DOM prêt
    document.addEventListener('DOMContentLoaded', initReactions);

})();
