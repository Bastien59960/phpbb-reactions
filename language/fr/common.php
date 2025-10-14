<?php
/**
 * Fichier : common.php
 * Chemin : bastien59960/reactions/language/fr/common.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/common.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises générales pour l'interface
 * utilisateur (UI), les messages d'erreur, les tooltips, et les options de
 * configuration dans le panneau d'administration (ACP).
 *
 * Il est chargé sur la plupart des pages où l'extension est active.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// Vérification de sécurité
if (!defined('IN_PHPBB'))
{
    exit;
}

// Initialisation du tableau de langue
if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

// =============================================================================
// DÉFINITION DES CHAÎNES DE LANGUE
// =============================================================================

// Fusionner le tableau de langues existant
$lang = array_merge($lang, array(
    // =============================================================================
    // MESSAGES D'ADMINISTRATION (ACP)
    // =============================================================================
    'ACP_REACTIONS_TITLE'       => 'Réglages des réactions',
    'ACP_REACTIONS_SETTINGS'    => 'Configuration des réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN' => 'Ici, vous pouvez gérer les paramètres pour l\'extension de réactions aux messages.',
    'ACP_REACTIONS_ENABLED'     => 'Activer les réactions',
    'ACP_REACTIONS_MAX_PER_POST' => 'Nombre maximum de types de réactions par post',
    'ACP_REACTIONS_MAX_PER_POST_EXPLAIN' => 'Le nombre maximal de types de réaction uniques qu\'un seul message peut recevoir.',
    'ACP_REACTIONS_MAX_PER_USER' => 'Nombre maximum de réactions par utilisateur et par post',
    'ACP_REACTIONS_MAX_PER_USER_EXPLAIN' => 'Le nombre maximal de réactions qu\'un seul utilisateur peut ajouter à un seul message.',
    'REACTIONS_SPAM_TIME'       => 'Fenêtre anti-spam des notifications',
    'REACTIONS_SPAM_TIME_EXPLAIN' => 'Le temps en minutes à attendre avant d\'envoyer une notification groupée par e-mail. Mettre à 0 pour désactiver les notifications par e-mail.',
    
    // =============================================================================
    // MESSAGES D'INTERFACE UTILISATEUR
    // =============================================================================
    'REACTION_ADD'              => 'Ajouter une réaction',
    'REACTION_REMOVE'           => 'Retirer votre réaction',
    'REACTION_MORE'             => 'Plus de réactions',
    'REACTION_LOADING'          => 'Chargement...',
    'REACTION_ERROR'            => 'Erreur lors de la réaction',
    'REACTION_SUCCESS_ADD'      => 'Réaction ajoutée avec succès',
    'REACTION_SUCCESS_REMOVE'   => 'Réaction supprimée avec succès',
    
    // =============================================================================
    // MESSAGES D'ERREUR ET DE VALIDATION
    // =============================================================================
    'REACTION_NOT_AUTHORIZED'   => 'Vous n\'êtes pas autorisé à réagir',
    'REACTION_INVALID_POST'     => 'Message invalide',
    'REACTION_INVALID_EMOJI'    => 'Emoji invalide',
    'REACTION_ALREADY_ADDED'    => 'Vous avez déjà réagi avec cet emoji',
    'REACTION_ALREADY_EXISTS'   => 'Vous avez déjà réagi avec cet emoji', // Compatibilité
    'REACTION_NOT_FOUND'        => 'Réaction non trouvée',
    
    // =============================================================================
    // COMPTEURS ET AFFICHAGE
    // =============================================================================
    'REACTION_COUNT_SINGULAR'   => '%d réaction',
    'REACTION_COUNT_PLURAL'     => '%d réactions',
    'REACTIONS_TITLE'           => 'Réactions',
    'NO_REACTIONS'              => 'Aucune réaction pour le moment',
    'REACTIONS_BY_USERS'        => 'Réactions des utilisateurs',
    'REACTION_BY_USER'          => 'Réaction de %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' et ',
    
    // =============================================================================
    // EMOJIS ET INTERFACE
    // =============================================================================
    'REACTIONS_COMMON_EMOJIS'   => 'Emojis courantes',
    'REACTIONS_LOGIN_REQUIRED'  => 'Vous devez être connecté pour réagir aux messages',
    'REACTIONS_JSON_ERROR'      => 'Erreur de chargement des emojis',
    'REACTIONS_FALLBACK_INFO'   => 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.',
    
    // =============================================================================
    // TOOLTIPS ET AIDES CONTEXTUELLES
    // =============================================================================
    'REACTIONS_ADD_TOOLTIP'     => 'Ajouter une réaction',
    'REACTIONS_MORE_TOOLTIP'    => 'Plus d\'emojis',
    'REACTIONS_COUNT_TOOLTIP'   => '%d réaction(s)',
    
    // =============================================================================
    // MESSAGES TECHNIQUES ET DEBUG
    // =============================================================================
    'REACTIONS_DEBUG_ENABLED'   => 'Mode debug des réactions activé',
    'REACTIONS_CSRF_ERROR'      => 'Jeton CSRF invalide',
    'REACTIONS_SERVER_ERROR'    => 'Erreur serveur lors de la réaction',
    
    // =============================================================================
    // LIMITES ET RESTRICTIONS
    // =============================================================================
    'REACTIONS_LIMIT_POST'      => 'Maximum %d types de réactions par message',
    'REACTIONS_LIMIT_USER'      => 'Maximum %d réactions par utilisateur et par message',
    'REACTIONS_LIMIT_REACHED'   => 'Limite de réactions atteinte',
    'REACTION_LIMIT_POST'       => 'Limite de types de réactions par message atteinte',
    'REACTION_LIMIT_USER'       => 'Limite de réactions par utilisateur atteinte',
));
