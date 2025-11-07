<?php
/**
 * File: ucp_reactions.php
 * Path: bastien59960/reactions/language/fr/ucp_reactions.php
 * Author: Bastien (bastien59960)
 * GitHub: https://github.com/bastien59960/reactions/blob/main/language/en/ucp_reactions.php
 *
 * Rôle:
 * Ce fichier contient les chaînes de langue françaises pour la page de préférences
 * des réactions dans le Panneau de Contrôle Utilisateur (UCP).
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
    'UCP_REACTIONS_TITLE'           => 'Préférences des réactions',
    'UCP_REACTIONS_EXPLAIN'         => 'Choisissez comment être notifié lorsque des membres réagissent à vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'Me notifier des nouvelles réactions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Recevoir une notification lorsque un utilisateur réagit à l\'un de vos messages.',
    'UCP_REACTIONS_CRON_EMAIL'      => 'Me notifier des nouvelles réactions (e-mail)',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN' => 'Recevoir un résumé périodique par e-mail des nouvelles réactions sur vos messages.',
    'UCP_REACTIONS_SAVED'           => 'Vos préférences de réactions ont été sauvegardées.',
));