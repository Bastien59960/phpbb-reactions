<?php
/**
 * File: language/fr/acp/common.php
 * Extension: bastien59960/reactions
 *
 * Description:
 * Chaînes de langue françaises pour le Panneau de Contrôle Administration (ACP)
 * de l'extension Reactions.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Titres des modules ---
    'ACP_REACTIONS_TITLE'                => 'Réactions aux messages',
    'ACP_REACTIONS_SETTINGS'             => 'Paramètres',
    'ACP_REACTIONS_IMPORT'               => 'Importer les réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'     => 'Configure le comportement et les limites appliquées aux réactions.',

    // --- Descriptions des paramètres ---
    'REACTIONS_SPAM_TIME'                => 'Délai entre les résumés par e-mail',
    'REACTIONS_SPAM_TIME_EXPLAIN'        => 'Délai minimum (en minutes) entre l\'envoi de deux résumés par e-mail au même utilisateur.',
    'REACTIONS_MAX_PER_POST'             => 'Maximum de types par message',
    'REACTIONS_MAX_PER_POST_EXPLAIN'     => 'Nombre maximum de types de réactions différents autorisés sur un seul message.',
    'REACTIONS_MAX_PER_USER'             => 'Maximum par utilisateur',
    'REACTIONS_MAX_PER_USER_EXPLAIN'     => 'Nombre maximum de réactions qu\'un utilisateur peut ajouter à un seul message.',

    'REACTIONS_DISPLAY_SETTINGS'         => 'Paramètres d\'affichage et du sélecteur',
    'REACTIONS_POST_EMOJI_SIZE'          => 'Taille des emojis sous les messages',
    'REACTIONS_POST_EMOJI_SIZE_EXPLAIN'  => 'Définit la taille (en pixels) des réactions affichées sous chaque message.',
    'REACTIONS_PICKER_EMOJI_SIZE'        => 'Taille des icônes du sélecteur',
    'REACTIONS_PICKER_EMOJI_SIZE_EXPLAIN'=> 'Taille (en pixels) des emojis du sélecteur ainsi que des icônes d\'onglet/catégorie.',
    'REACTIONS_PICKER_WIDTH'             => 'Largeur du sélecteur',
    'REACTIONS_PICKER_WIDTH_EXPLAIN'     => 'Largeur (en pixels) de la palette d\'emojis.',
    'REACTIONS_PICKER_HEIGHT'            => 'Hauteur du sélecteur',
    'REACTIONS_PICKER_HEIGHT_EXPLAIN'    => 'Hauteur (en pixels) de la palette d\'emojis.',
    'REACTIONS_PICKER_SHOW_CATEGORIES'   => 'Afficher les catégories',
    'REACTIONS_PICKER_SHOW_CATEGORIES_EXPLAIN' => 'Décochez pour masquer les onglets de catégories et n\'afficher que les emojis rapides.',
    'REACTIONS_PICKER_SHOW_SEARCH'       => 'Afficher la recherche',
    'REACTIONS_PICKER_SHOW_SEARCH_EXPLAIN' => 'Décochez pour supprimer le champ de recherche de la palette.',
    'REACTIONS_PICKER_USE_JSON'          => 'Charger le jeu complet d\'emojis',
    'REACTIONS_PICKER_USE_JSON_EXPLAIN'  => 'Décochez pour ne pas charger le fichier JSON externe et n\'afficher que les 10 emojis fréquents.',
    'REACTIONS_SYNC_INTERVAL'            => 'Intervalle de rafraîchissement',
    'REACTIONS_SYNC_INTERVAL_EXPLAIN'    => 'Temps (en millisecondes) entre les mises à jour automatiques des réactions.',

    // --- Messages du journal d'administration ---
    'LOG_REACTIONS_IMPORT_START'         => '<strong>Tentative d\'importation des réactions</strong><br>• Recherche de données d\'une ancienne extension de réactions.',
    'LOG_REACTIONS_IMPORT_EMPTY'         => '<strong>Importation des réactions ignorée</strong><br>• D\'anciennes tables ont été trouvées mais étaient vides.',
    'LOG_REACTIONS_IMPORT_SUCCESS'       => '<strong>Importation des réactions terminée</strong><br>• %1$d réactions importées (%2$d ignorées).<br>• %3$d utilisateurs et %4$d messages affectés.',
]);