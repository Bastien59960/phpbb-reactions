<?php
/**
 * Fichier : language/fr/acp/common.php — bastien59960/reactions/language/fr/acp/common.php
 *
 * Fichier de langue française pour la partie ACP (panneau d'administration) de l'extension Reactions.
 *
 * Ce fichier contient toutes les chaînes utilisées dans l'administration de l'extension en français.
 *
 * Points clés :
 *   - Fournit toutes les chaînes traduisibles pour l'ACP de l'extension
 *   - Utilisé par phpBB pour afficher les pages de configuration admin de Reactions
 *
 * Ce fichier doit être synchronisé avec la version anglaise pour garantir la cohérence.
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

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// however where there are multiple placeholders in one string, please use the numbered format.
//

$lang = array_merge($lang, array(
    // Titre principal ACP
    'ACP_REACTIONS_TITLE'                   => 'Réglages des réactions',
    'ACP_REACTIONS_SETTINGS'                => 'Configuration des réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Ici, vous pouvez gérer les paramètres pour l\'extension de réactions aux messages.',
    'ACP_REACTIONS_IMPORT'                  => 'Importer les réactions anciennes',
    'ACP_REACTIONS_IMPORT_EXPLAIN'          => 'Ici, vous pouvez importer les réactions anciennes depuis la table des réactions anciennes.',

    // Page de paramètres ACP
    'ACP_REACTIONS_SETTINGS'                => 'Configuration des réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Ici, vous pouvez gérer les paramètres pour l\'extension de réactions aux messages.',

    // Champs de configuration
    'REACTIONS_SPAM_TIME'                   => 'Fenêtre anti-spam des notifications',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'Le temps en minutes à attendre avant d\'envoyer une notification groupée à l\'auteur du message. Mettre à 0 pour désactiver les notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Nombre maximal de types de réaction par message',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'Le nombre maximal de types de réaction uniques qu\'un seul message peut recevoir.',
    'REACTIONS_MAX_PER_USER'                => 'Nombre maximal de réactions par utilisateur par message',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'Le nombre maximal de réactions qu\'un seul utilisateur peut ajouter à un seul message.',

    // Termes généraux
    'MINUTES'                               => 'Minutes',
));
