<?php
/**
 * File: ucp_reactions.php
 * Path: bastien59960/reactions/language/en/ucp_reactions.php
 * Author: Bastien (bastien59960)
 * GitHub: https://github.com/bastien59960/reactions
 *
 * Role:
 * This file contains the English language strings for the reactions preferences
 * page in the User Control Panel (UCP).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    'UCP_REACTIONS_TITLE'           => 'Reactions preferences',
    'UCP_REACTIONS_EXPLAIN'         => 'Choose how to be notified when members react to your posts.',
    'UCP_REACTIONS_NOTIFY'          => 'Notify me about new reactions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Receive an instant notification in the forum\'s notification bell.',
    'UCP_REACTIONS_CRON_EMAIL'           => 'Notify me about new reactions (e-mail)',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN'   => 'Receive a periodic e-mail summary of new reactions.',
    'UCP_REACTIONS_SAVED'           => 'Your reaction preferences have been saved.',
]);