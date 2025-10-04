<?php
/**
 * Reactions Extension for phpBB 3.3
 * Fichier de langue français pour les notifications
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

// Fusionner le tableau de langues existant
$lang = array_merge($lang, array(
    'NOTIFICATION_TYPE_REACTION' => 'Quelqu\'un a réagi à votre message',
    'NOTIFICATION_GROUP_REACTIONS' => 'Notifications de réactions',
));
