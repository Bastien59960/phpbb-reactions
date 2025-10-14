<?php
/**
 * Fichier : language/fr/common.php â€” bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * RÃ´le :
 * Ce fichier contient les chaÃ®nes de langue franÃ§aises gÃ©nÃ©rales pour l'interface
 * utilisateur (UI), les messages d'erreur, les tooltips, et les options de
 * l'extension. Il est chargÃ© sur la plupart des pages oÃ¹ l'extension est active.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// VÃ©rification de sÃ©curitÃ©
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
// DÃ‰FINITION DES CHAÃŽNES DE LANGUE
// =============================================================================

// Fusionner le tableau de langues existant
$lang = array_merge($lang, array(
    // =============================================================================
    // MESSAGES D'INTERFACE UTILISATEUR
    // =============================================================================
    'REACTION_ADD'              => 'Ajouter une rÃ©action',
    'REACTION_REMOVE'           => 'Retirer votre rÃ©action',
    'REACTION_MORE'             => 'Plus de rÃ©actions',
    'REACTION_LOADING'          => 'Chargement...',
    'REACTION_ERROR'            => 'Erreur lors de la rÃ©action',
    'REACTION_SUCCESS_ADD'      => 'RÃ©action ajoutÃ©e avec succÃ¨s',
    'REACTION_SUCCESS_REMOVE'   => 'RÃ©action supprimÃ©e avec succÃ¨s',
    
    // =============================================================================
    // MESSAGES D'ERREUR ET DE VALIDATION
    // =============================================================================
    'REACTION_NOT_AUTHORIZED'   => 'Vous n\'Ãªtes pas autorisÃ© Ã  rÃ©agir',
    'REACTION_INVALID_POST'     => 'Message invalide',
    'REACTION_INVALID_EMOJI'    => 'Emoji invalide',
    'REACTION_ALREADY_ADDED'    => 'Vous avez dÃ©jÃ  rÃ©agi avec cet emoji',
    'REACTION_ALREADY_EXISTS'   => 'Vous avez dÃ©jÃ  rÃ©agi avec cet emoji', // CompatibilitÃ©
    'REACTION_NOT_FOUND'        => 'RÃ©action non trouvÃ©e',
    
    // =============================================================================
    // COMPTEURS ET AFFICHAGE
    // =============================================================================
    'REACTION_COUNT_SINGULAR'   => '%d rÃ©action',
    'REACTION_COUNT_PLURAL'     => '%d rÃ©actions',
    'REACTIONS_TITLE'           => 'RÃ©actions',
    'NO_REACTIONS'              => 'Aucune rÃ©action pour le moment',
    'REACTIONS_BY_USERS'        => 'RÃ©actions des utilisateurs',
    'REACTION_BY_USER'          => 'RÃ©action de %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' et ',
    
    // =============================================================================
    // EMOJIS ET INTERFACE
    // =============================================================================
    'REACTIONS_COMMON_EMOJIS'   => 'Emojis courantes',
    'REACTIONS_LOGIN_REQUIRED'  => 'Vous devez Ãªtre connectÃ© pour rÃ©agir aux messages',
    'REACTIONS_JSON_ERROR'      => 'Erreur de chargement des emojis',
    'REACTIONS_FALLBACK_INFO'   => 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.',
    
    // =============================================================================
    // TOOLTIPS ET AIDES CONTEXTUELLES
    // =============================================================================
    'REACTIONS_ADD_TOOLTIP'     => 'Ajouter une rÃ©action',
    'REACTIONS_MORE_TOOLTIP'    => 'Plus d\'emojis',
    'REACTIONS_COUNT_TOOLTIP'   => '%d rÃ©action(s)',
    
    // =============================================================================
    // MESSAGES TECHNIQUES ET DEBUG
    // =============================================================================
    'REACTIONS_DEBUG_ENABLED'   => 'Mode debug des rÃ©actions activÃ©',
    'REACTIONS_CSRF_ERROR'      => 'Jeton CSRF invalide',
    'REACTIONS_SERVER_ERROR'    => 'Erreur serveur lors de la rÃ©action',
    
    // =============================================================================
    // LIMITES ET RESTRICTIONS
    // =============================================================================
    'REACTIONS_LIMIT_POST'      => 'Maximum %d types de rÃ©actions par message',
    'REACTIONS_LIMIT_USER'      => 'Maximum %d rÃ©actions par utilisateur et par message',
    'REACTION_LIMIT_POST'       => 'Limite de types de rÃ©actions pour ce message atteinte',
    'REACTION_LIMIT_USER'       => 'Limite de rÃ©actions par utilisateur atteinte',
    'REACTIONS_LIMIT_REACHED'   => 'Limite de rÃ©actions atteinte',

));
