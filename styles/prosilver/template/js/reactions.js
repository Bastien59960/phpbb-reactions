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
                // Supporter ancien et nouveau attributs (data-unicode / data-emoji)
                let existing = reactionContainer.querySelector(`[data-unicode="${emoji}"], [data-emoji="${emoji}"]`);
                if (!existing) {
                    const reaction = createReactionElement(emoji, 0, false, true);
                    // set both attributes for compatibility
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
                    // si existant mais compteur absent/0, forcer l'affichage "00"
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
            // Avoid attaching twice
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

        const isActive = e
