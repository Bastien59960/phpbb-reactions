<?php
/**
 * Fichier : language/fr/email.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
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
    'REACTIONS_DIGEST_SUBJECT'      => 'Résumé des nouvelles réactions sur vos messages',
    'REACTIONS_DIGEST_SIGNATURE'    => 'Merci de votre participation sur %s.',
));