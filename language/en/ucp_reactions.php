<?php
/**
 * File: language/en/ucp_reactions.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Role:
 * This file contains the English language strings for the Reactions preferences
 * page in the User Control Panel (UCP).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    'UCP_REACTIONS_TITLE'           => 'Reactions Preferences',
    'UCP_REACTIONS_NOTIFY'          => 'Notify me about new reactions (board notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Receive an instant notification in the forum bell.',
    'UCP_REACTIONS_EMAIL'           => 'Notify me about new reactions (e-mail)',
    'UCP_REACTIONS_EMAIL_EXPLAIN'   => 'Receive a periodic e-mail summary of new reactions.',
    'UCP_REACTIONS_SAVED'           => 'Your reaction preferences have been saved.',
]);