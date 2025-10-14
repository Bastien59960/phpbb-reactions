<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/language/fr/ucp_reactions.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/ucp_reactions.php
 *
 * RÃ´le :
 * Ce fichier contient les chaÃ®nes de langue franÃ§aises pour la page de prÃ©fÃ©rences
 * des rÃ©actions dans le panneau de contrÃ´le de l'utilisateur (UCP).
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
    'UCP_REACTIONS_TITLE'           => 'PrÃ©fÃ©rences des rÃ©actions',
    'UCP_REACTIONS_EXPLAIN'         => 'Choisissez comment Ãªtre averti lorsque des membres rÃ©agissent Ã  vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'M\'avertir des nouvelles rÃ©actions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Recevoir une notification instantanÃ©e dans la cloche du forum.',
    'UCP_REACTIONS_EMAIL'           => 'M\'avertir des nouvelles rÃ©actions (e-mail)',
    'UCP_REACTIONS_EMAIL_EXPLAIN'   => 'Recevoir un rÃ©sumÃ© pÃ©riodique par e-mail des nouvelles rÃ©actions.',
    'UCP_REACTIONS_SAVED'           => 'Vos prÃ©fÃ©rences de rÃ©actions ont Ã©tÃ© enregistrÃ©es.',
));
