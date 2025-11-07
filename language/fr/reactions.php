<?php
/**
 * ============================================================================
 * Fichier : language/fr/reactions.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * Ce fichier centralise toutes les chaînes de langue françaises pour les
 * notifications de l'extension Reactions.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Groupe de notifications (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Réactions',

    // --- Notification instantanée (cloche & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> a réagi à votre message avec %2$s.', // Texte affiché dans la cloche
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Réaction instantanée à un message', // Titre dans l'UCP
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Recevoir une notification instantanée dans la cloche du forum lorsqu\'un utilisateur réagit à l\'un de vos messages.',

    // --- Résumé par e-mail (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Résumé périodique des réactions par e-mail',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir un résumé périodique par e-mail des nouvelles réactions sur vos messages.',

    // =============================================================================
    // CLÉS POUR L'UCP (déplacées depuis ucp_reactions.php)
    // =============================================================================
    'UCP_REACTIONS_TITLE'           => 'Préférences des réactions',
    'UCP_REACTIONS_EXPLAIN'         => 'Choisissez comment être notifié lorsque des membres réagissent à vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'Me notifier des nouvelles réactions (notification)',
    'UCP_REACTIONS_CRON_EMAIL'      => 'Me notifier des nouvelles réactions (e-mail)',
]);