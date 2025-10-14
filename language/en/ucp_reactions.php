<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/language/en/ucp_reactions.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/en/ucp_reactions.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue anglaises pour la page de préférences
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
    'UCP_REACTIONS_TITLE' => 'Reactions preferences',
    'UCP_REACTIONS_NOTIFY' => 'Enable in-forum notifications (bell)',
    'UCP_REACTIONS_CRON_EMAIL' => 'Enable periodic email digest',
    'UCP_REACTIONS_SAVED' => 'Your reactions preferences have been saved.',
));
