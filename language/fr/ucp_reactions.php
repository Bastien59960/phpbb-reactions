<?php
/**
 * Fichier de langue français pour les préférences UCP des réactions
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
    'UCP_REACTIONS_TITLE' => 'Préférences des réactions',
    'UCP_REACTIONS_NOTIFY' => 'Activer les notifications internes (cloche)',
    'UCP_REACTIONS_CRON_EMAIL' => 'Activer le résumé périodique par e-mail',
    'UCP_REACTIONS_SAVED' => 'Vos préférences de réactions ont été enregistrées.',
));
