<?php
/**
 * Fichier : language/en/email.php — bastien59960/reactions
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
    'REACTIONS_DIGEST_SUBJECT'      => 'New reactions to your posts',
    'REACTIONS_DIGEST_SIGNATURE'    => 'Thank you for your participation on %s.',
));