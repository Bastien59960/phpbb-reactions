<?php
/**
 * Fichier : language/fr/ucp_reactions.php — bastien59960/reactions/language/fr/ucp_reactions.php
 *
 * Fichier de langue française pour les préférences UCP (panneau de contrôle utilisateur) de l'extension Reactions.
 *
 * Ce fichier contient toutes les chaînes utilisées dans le panneau de préférences utilisateur pour les réactions (notifications, résumé e-mail, etc.) en français.
 *
 * Points clés :
 *   - Fournit toutes les chaînes traduisibles pour les préférences utilisateur liées aux réactions
 *   - Utilisé par phpBB pour afficher la page de préférences UCP de l'extension
 *
 * Ce fichier doit être synchronisé avec la version anglaise pour garantir la cohérence.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'UCP_REACTIONS_TITLE' => 'Préférences des réactions',
    'UCP_REACTIONS_NOTIFY' => 'Activer les notifications internes (cloche)',
    'UCP_REACTIONS_CRON_EMAIL' => 'Activer le résumé périodique par e-mail',
    'UCP_REACTIONS_SAVED' => 'Vos préférences de réactions ont été enregistrées.',
));
