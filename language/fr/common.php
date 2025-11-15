<?php
/**
 * Fichier : language/fr/common.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

$lang = array_merge($lang, [
    // =============================================================================
    // UCP - PANEL DE CONTRÔLE UTILISATEUR
    // =============================================================================
    'UCP_REACTIONS_TITLE'           => 'Préférences des réactions',
    'UCP_REACTIONS_SETTINGS'        => 'Paramètres des réactions',
    'UCP_REACTIONS_EXPLAIN'         => 'Configurez comment vous souhaitez être notifié des réactions à vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'Notifications instantanées',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Recevoir une notification dans la cloche du forum lorsqu\'un utilisateur réagit à vos messages.',
    'UCP_REACTIONS_CRON_EMAIL'      => 'Résumés par e-mail',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN' => 'Recevoir un résumé périodique par e-mail des nouvelles réactions.',
    'UCP_REACTIONS_SAVED'           => 'Vos préférences ont été sauvegardées.',
    'UCP_REACTIONS_CONTROLLER_NOT_FOUND' => 'Le contrôleur des réactions est introuvable. L\'extension peut être mal installée.',
]);