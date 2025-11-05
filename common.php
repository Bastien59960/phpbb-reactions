<?php
/**
 * Fichier : common.php
 * Chemin : language/fr/common.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises générales pour l'interface
 * utilisateur (UI), les messages d'erreur, les tooltips, et les noms des tâches cron.
 *
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
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
    'REACTION_LIMIT_POST'       => 'Limite de types de réactions pour ce message atteinte',
    'REACTION_LIMIT_USER'       => 'Limite de réactions par utilisateur atteinte',
    'REACTIONS_LIMIT_REACHED'   => 'Limite de réactions atteinte',

    // =============================================================================
    // NOTIFICATIONS PAR E-MAIL (CRON DIGEST)
    // =============================================================================
    'REACTIONS_DIGEST_SUBJECT'      => 'Résumé des réactions sur vos messages',
    'REACTIONS_DIGEST_INTRO'        => 'Voici un résumé des nouvelles réactions que vos messages ont reçues récemment.',
    'REACTIONS_DIGEST_SIGNATURE'    => 'Merci de votre participation sur %s.', // %s sera remplacé par le nom du site
    'REACTIONS_PREFERENCES_HINT'    => 'Vous pouvez gérer vos préférences de notification dans votre Panneau de l\'utilisateur.',
    'REACTIONS_BY'                  => 'par',
    'REACTIONS_ON'                  => 'le',
    'NO_SUBJECT'                    => '(Sans sujet)',

    // =============================================================================
    // TÂCHES CRON (ACP)
    // =============================================================================
    'BASTIEN59960_REACTIONS_TEST'              => 'Réactions : Test du système',
    'BASTIEN59960_REACTIONS_TEST_EXPLAIN'  => 'Test périodique pour vérifier que le système de cron de l\'extension Réactions fonctionne correctement.',
    'BASTIEN59960_REACTIONS_NOTIFICATION'          => 'Réactions : Envoi des résumés e-mail',
    'BASTIEN59960_REACTIONS_NOTIFICATION_EXPLAIN' => 'Regroupe les nouvelles réactions et envoie des e-mails de résumé périodiques aux utilisateurs.',
    'LOG_REACTIONS_CRON_TEST_RUN'                   => '<strong>Cron de test des Réactions exécuté</strong><br>» La tâche de test pour l\'extension Réactions s\'est exécutée avec succès.',
));
