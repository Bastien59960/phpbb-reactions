<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/language/fr/ucp_reactions.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/ucp_reactions.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises pour la page de préférences
 * des réactions dans le panneau de contrôle de l'utilisateur (UCP).
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
    'UCP_REACTIONS_EXPLAIN'         => 'Choisissez comment être averti lorsque des membres réagissent à vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'M\'avertir des nouvelles réactions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Recevoir une notification instantanée dans la cloche du forum.',
    'UCP_REACTIONS_CRON_EMAIL'           => 'M\'avertir des nouvelles réactions (e-mail)',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN'   => 'Recevoir un résumé périodique par e-mail des nouvelles réactions.',
    'UCP_REACTIONS_SAVED'           => 'Vos préférences de réactions ont été enregistrées.',
));
