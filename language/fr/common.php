<?php
/**
 * Fichier : language/fr/common.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

$lang = array_merge($lang ?? [], [
    // =============================================================================
    // USER INTERFACE MESSAGES
    // =============================================================================
    'REACTION_ADD'              => 'Ajouter une réaction',
    'REACTION_REMOVE'           => 'Retirer votre réaction',
    'REACTION_MORE'             => 'Plus de réactions',
    'REACTION_LOADING'          => 'Chargement...',
    'REACTION_ERROR'            => 'Erreur lors de la réaction',
    'REACTION_SUCCESS_ADD'      => 'Réaction ajoutée avec succès',
    'REACTION_SUCCESS_REMOVE'   => 'Réaction retirée avec succès',
    
    // =============================================================================
    // ERROR AND VALIDATION MESSAGES
    // =============================================================================
    'REACTION_NOT_AUTHORIZED'   => 'Vous n\'êtes pas autorisé à réagir',
    'REACTION_INVALID_POST'     => 'Message invalide',
    'REACTION_INVALID_EMOJI'    => 'Emoji invalide',
    'REACTION_ALREADY_ADDED'    => 'Vous avez déjà réagi avec cet emoji',
    'REACTION_ALREADY_EXISTS'   => 'Vous avez déjà réagi avec cet emoji', // Compatibilité
    'REACTION_NOT_FOUND'        => 'Réaction non trouvée',
    
    // =============================================================================
    // COUNTERS AND DISPLAY
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
    // EMOJIS AND INTERFACE
    // =============================================================================
    'REACTIONS_COMMON_EMOJIS'   => 'Emojis courants',
    'REACTIONS_LOGIN_REQUIRED'  => 'Vous devez être connecté pour réagir aux messages',
    'REACTIONS_JSON_ERROR'      => 'Erreur lors du chargement des emojis',
    'REACTIONS_FALLBACK_INFO'   => 'Fichier JSON non accessible. Seuls les emojis courants sont disponibles.',
    
    // =============================================================================
    // TOOLTIPS AND CONTEXTUAL HELP
    // =============================================================================
    'REACTIONS_ADD_TOOLTIP'     => 'Ajouter une réaction',
    'REACTIONS_MORE_TOOLTIP'    => 'Plus d\'emojis',
    'REACTIONS_COUNT_TOOLTIP'   => '%d réaction(s)',
    
    // =============================================================================
    // TECHNICAL AND DEBUG MESSAGES
    // =============================================================================
    'REACTIONS_DEBUG_ENABLED'   => 'Mode debug des réactions activé',
    'REACTIONS_CSRF_ERROR'      => 'Jeton CSRF invalide',
    'REACTIONS_SERVER_ERROR'    => 'Erreur serveur lors de la réaction',
    
    // =============================================================================
    // LIMITS AND RESTRICTIONS
    // =============================================================================
    'REACTIONS_LIMIT_POST'      => 'Maximum de %d types de réactions par message',
    'REACTIONS_LIMIT_USER'      => 'Maximum de %d types de réactions par utilisateur par message',
    'REACTION_LIMIT_POST'       => 'Limite de types de réactions pour ce message atteinte',
    'REACTION_LIMIT_USER'       => 'Limite de réactions par utilisateur atteinte',
    'REACTIONS_LIMIT_REACHED'   => 'Limite de réactions atteinte',

    'NO_SUBJECT'                => '(Pas de sujet)',

    // =============================================================================
    // UCP - PANEL DE CONTRÔLE UTILISATEUR
    // =============================================================================
    'UCP_REACTIONS_TITLE'           => 'Préférences des réactions',
    'UCP_REACTIONS_SETTINGS'        => 'Préférences de réaction',
    'UCP_REACTIONS_EXPLAIN'         => 'Configurez comment vous souhaitez être notifié des réactions à vos messages.',
    'UCP_REACTIONS_NOTIFY'          => 'Notifications instantanées (via la cloche)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Recevoir une notification lorsqu\'un utilisateur réagit à l\'un de vos messages.',
    'UCP_REACTIONS_CRON_EMAIL'      => 'Me notifier des nouvelles réactions (e-mail)',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN' => 'Recevoir un résumé périodique par e-mail des nouvelles réactions sur vos messages.',
    'UCP_REACTIONS_SAVED'           => 'Vos préférences ont été sauvegardées.',
    'UCP_REACTIONS_CONTROLLER_NOT_FOUND' => 'Le contrôleur des réactions est introuvable. L\'extension peut être mal installée.',

    // =============================================================================
    // CRON TASKS (ACP & CLI)
    // =============================================================================
    'TASK_BASTIEN59960_REACTIONS_NOTIFICATION'   => 'Réactions : Envoyer les résumés par e-mail',
    'TASK_BASTIEN59960_REACTIONS_TEST'           => 'Réactions : Journaliser la tâche de test',

    'BASTIEN59960_REACTIONS_TEST'              => 'Réactions : Test du système',
    'BASTIEN59960_REACTIONS_TEST_EXPLAIN'  => 'Test périodique pour vérifier que le système de cron de l\'extension Réactions fonctionne correctement.',
    'BASTIEN59960_REACTIONS_NOTIFICATION'          => 'Réactions : Envoyer les résumés par e-mail',
    'BASTIEN59960_REACTIONS_NOTIFICATION_EXPLAIN' => 'Regroupe les nouvelles réactions et envoie des résumés périodiques par e-mail aux utilisateurs.',
    'LOG_REACTIONS_CRON_TEST_RUN'                   => '<strong>Exécution du cron de test Réactions</strong><br>» La tâche de test pour l\'extension Réactions s\'est exécutée avec succès.',

    // =============================================================================
    // NOTIFICATION KEYS (from reactions.php)
    // =============================================================================
    'NOTIFICATION_GROUP_REACTIONS' => 'Réactions',
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> a réagi à votre message avec %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Réaction instantanée à un message',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Recevoir une notification instantanée dans la cloche du forum lorsqu\'un utilisateur réagit à l\'un de vos messages.',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Résumé périodique des réactions par e-mail',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir un résumé périodique par e-mail des nouvelles réactions sur vos messages.',
]);