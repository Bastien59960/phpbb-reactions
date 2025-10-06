<?php
/**
 * English language file - UCP Reactions settings
 * 
 * Allows users to control how they are notified when someone reacts to their posts:
 * - Enable or disable in-forum notifications (bell icon)
 * - Enable or disable cron-based reaction emails
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

$lang = array_merge($lang, array(
	'UCP_REACTIONS_TITLE'		=> 'Reaction notifications',
	'UCP_REACTIONS_EXPLAIN'		=> 'Choose how you want to be notified when someone reacts to your posts.',
	'UCP_REACTIONS_NOTIFY'		=> 'Receive forum notifications (bell)',
	'UCP_REACTIONS_CRON_EMAIL'	=> 'Receive reaction emails (via cron)',
	'UCP_REACTIONS_SAVED'		=> 'Your reaction notification preferences have been saved.',
));
