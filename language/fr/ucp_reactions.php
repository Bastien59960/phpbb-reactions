<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/language/fr/ucp_reactions.php
 * Auteur : Bastien (bastien5s9960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/ucp_reactions.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises pour la page de préférences
 * des réactions dans le panneau de contrôle de l'utilisateur (UCP).
 *
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
