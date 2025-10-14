<?php
/**
 * ============================================================================
 * Fichier : language/fr/acp/common.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description :
 * Ce fichier contient les chaînes de langue françaises pour le Panneau
 * d'Administration (ACP) de l'extension Reactions.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Titres des modules ---
    'ACP_REACTIONS_TITLE'    => 'Réactions aux messages',
    'ACP_REACTIONS_SETTINGS' => 'Paramètres',
    'ACP_REACTIONS_IMPORT'   => 'Importer les réactions',

    // --- Descriptions des paramètres ---
    'ACP_REACTIONS_MAX_PER_POST_EXPLAIN' => 'Nombre maximum de types de réactions différents autorisés sur un seul message.',
    'ACP_REACTIONS_MAX_PER_USER_EXPLAIN' => 'Nombre maximum de réactions qu\'un utilisateur peut ajouter sur un seul message.',
    'ACP_REACTIONS_SPAM_TIME_EXPLAIN'    => 'Délai minimum (en minutes) entre l\'envoi de deux résumés par e-mail pour le même utilisateur.',
]);