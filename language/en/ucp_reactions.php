<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /language/en/ucp_reactions.php
 * Rôle : Contient les chaînes de langue anglaises pour la page de préférences
 * des réactions dans le Panneau de Contrôle de l'Utilisateur (UCP).
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