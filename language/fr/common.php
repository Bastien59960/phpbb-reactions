<?php
/**
 * Fichier de langue français pour l'extension Reactions
 * 
 * Ce fichier contient toutes les chaînes de caractères en français
 * utilisées par l'extension Reactions. Il inclut :
 * - Messages d'interface utilisateur
 * - Messages d'erreur et de succès
 * - Textes pour les notifications
 * - Messages d'administration
 * - Tooltips et aides contextuelles
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
    'ACP_REACTIONS_ENABLED'     => 'Activer les réactions',
    'ACP_REACTIONS_MAX_PER_POST' => 'Nombre maximum de types de réactions par post',
    'ACP_REACTIONS_MAX_PER_USER' => 'Nombre maximum de réactions par utilisateur et par post',
    'ACP_REACTIONS_EXPLAIN'     => 'Configurez les paramètres des réactions aux messages.',
    
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

    // =============================================================================
    // NOTIFICATIONS
    // =============================================================================
    'REACTIONS_NOTIFICATION_TITLE'      => '%1$s a réagi à votre message',
    'REACTIONS_NOTIFICATION_TITLE_PLURAL' => '%1$s et %2$d autres personnes ont réagi à votre message',
    'REACTIONS_NOTIFICATION_AND_OTHERS' => '%1$s et %2$d autre(s)',
    'REACTIONS_NOTIFICATION_EMAIL_SUBJECT' => 'Nouvelles réactions à votre message "%2$s"',
    'REACTIONS_NOTIFICATION_TYPE' => 'Réactions aux messages',
    'REACTIONS_NOTIFICATION_GROUP' => 'Notifications de réactions',

    'NOTIFICATION_TYPE_REACTION' => '<strong>%1$s</strong> a réagi %2$s à votre message',

));
