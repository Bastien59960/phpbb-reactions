<?php
/**
 * Reactions Extension for phpBB 3.3
 * Fichier de langue français - Version corrigée
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

// Fusionner le tableau de langues existant
$lang = array_merge($lang, array(
    // Messages de base
    'REACTION_ADD'              => 'Ajouter une réaction',
    'REACTION_REMOVE'           => 'Retirer votre réaction',
    'REACTION_MORE'             => 'Plus de réactions',
    'REACTION_LOADING'          => 'Chargement...',
    'REACTION_ERROR'            => 'Erreur lors de la réaction',
    'REACTION_SUCCESS_ADD'      => 'Réaction ajoutée avec succès',
    'REACTION_SUCCESS_REMOVE'   => 'Réaction supprimée avec succès',
    
    // Messages d'erreur - CORRECTIONS selon les changements du code
    'REACTION_NOT_AUTHORIZED'   => 'Vous n\'êtes pas autorisé à réagir',
    'REACTION_INVALID_POST'     => 'Message invalide',
    'REACTION_INVALID_EMOJI'    => 'Emoji invalide',
    'REACTION_ALREADY_ADDED'    => 'Vous avez déjà réagi avec cet emoji', // CORRECTION: cohérent avec ajax.php
    'REACTION_ALREADY_EXISTS'   => 'Vous avez déjà réagi avec cet emoji', // Garde les deux pour compatibilité
    'REACTION_NOT_FOUND'        => 'Réaction non trouvée',
    
    // Compteurs et affichage
    'REACTION_COUNT_SINGULAR'   => '%d réaction',
    'REACTION_COUNT_PLURAL'     => '%d réactions',
    'REACTIONS_TITLE'           => 'Réactions',
    'NO_REACTIONS'              => 'Aucune réaction pour le moment',
    'REACTIONS_BY_USERS'        => 'Réactions des utilisateurs',
    'REACTION_BY_USER'          => 'Réaction de %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' et ',
    
    // NOUVEAUX selon cahier des charges et corrections
    'REACTIONS_COMMON_EMOJIS'   => 'Emojis courantes', // Remplace "populaires"
    'REACTIONS_LOGIN_REQUIRED'  => 'Vous devez être connecté pour réagir aux messages',
    'REACTIONS_JSON_ERROR'      => 'Erreur de chargement des emojis',
    'REACTIONS_FALLBACK_INFO'   => 'Fichier JSON non accessible. Seuls les emojis courantes sont disponibles.',
    
    // Tooltips et aides
    'REACTIONS_ADD_TOOLTIP'     => 'Ajouter une réaction',
    'REACTIONS_MORE_TOOLTIP'    => 'Plus d\'emojis',
    'REACTIONS_COUNT_TOOLTIP'   => '%d réaction(s)',
    
    // Messages techniques pour debug (optionnel)
    'REACTIONS_DEBUG_ENABLED'   => 'Mode debug des réactions activé',
    'REACTIONS_CSRF_ERROR'      => 'Jeton CSRF invalide',
    'REACTIONS_SERVER_ERROR'    => 'Erreur serveur lors de la réaction',
    
    // Limites selon cahier des charges
    'REACTIONS_LIMIT_POST'      => 'Maximum %d types de réactions par message',
    'REACTIONS_LIMIT_USER'      => 'Maximum %d réactions par utilisateur et par message',
    'REACTIONS_LIMIT_REACHED'   => 'Limite de réactions atteinte',

    'REACTIONS_NOTIFICATION_TITLE'      => '%1$s a réagi à votre message.',
'REACTIONS_NOTIFICATION_TITLE_PLURAL' => '%1$s et %2$d autres personnes ont réagi à votre message.',
'REACTIONS_NOTIFICATION_AND_OTHERS' => '%1$s et %2$d autre(s)',
'REACTIONS_NOTIFICATION_EMAIL_SUBJECT' => 'Nouvelles réactions à votre message "%2$s"',
));
