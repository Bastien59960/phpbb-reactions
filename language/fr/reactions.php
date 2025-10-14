<?php
/**
 * ============================================================================
 * Fichier : language/fr/reactions.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * ðŸ“˜ Description :
 * Ce fichier centralise toutes les chaÃ®nes de langue franÃ§aises pour les
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
    'NOTIFICATION_GROUP_REACTIONS' => 'RÃ©actions',

    // --- Notification instantanÃ©e (cloche & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> a rÃ©agi Ã  votre message avec %2$s',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Quelquâ€™un a rÃ©agi Ã  lâ€™un de vos messages',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Recevoir une notification lorsquâ€™un utilisateur rÃ©agit Ã  lâ€™un de vos messages.',

    // --- RÃ©sumÃ© par e-mail (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'RÃ©sumÃ© e-mail des rÃ©actions',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir pÃ©riodiquement un rÃ©sumÃ© par e-mail des rÃ©actions reÃ§ues sur vos messages.',

    // --- LibellÃ©s des options UCP ---
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => 'RÃ©actions Ã  mes messages',
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'RÃ©sumÃ©s e-mail des rÃ©actions',

    // --- Aides pour les rÃ©sumÃ©s ---
    'REACTIONS_DIGEST_VIEW_POST' => 'Voir ce message',
    'REACTIONS_DIGEST_SIGNATURE' => 'Merci, lâ€™Ã©quipe %s',
]);
