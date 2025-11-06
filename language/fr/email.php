<?php
/**
 * =============================================================================
 * Fichier : /language/fr/email.php
 * Extension : bastien59960/reactions
 * =============================================================================
 *
 * @package   bastien59960/reactions
 * @author    Bastien (bastien59960)
 * @copyright (c) 2025 Bastien59960
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * @description
 * Contient les chaînes de langue françaises pour les e-mails envoyés par l'extension.
 * Ce fichier est utilisé par la tâche cron pour construire l'e-mail de résumé des réactions.
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    // =========================================================================
    // SUJET DE L'E-MAIL (Email Subject)
    // =========================================================================
    'REACTIONS_DIGEST_SUBJECT' => 'Nouvelles réactions à vos messages',

    // =========================================================================
    // EN-TÊTE ET INTRODUCTION (Header and Introduction)
    // =========================================================================
    'REACTIONS_DIGEST_HELLO' => 'Bonjour %1$s,',
    'REACTIONS_DIGEST_INTRO' => 'Voici un résumé des nouvelles réactions à vos messages sur « %1$s » :',

    // =========================================================================
    // CONTENU DES POSTS (Posts Content)
    // =========================================================================
    'REACTIONS_DIGEST_POST_TITLE' => 'Réactions sur votre message « %1$s »',

    // =========================================================================
    // LABELS POUR LES RÉACTIONS (Reaction Labels)
    // =========================================================================
    'REACTIONS_DIGEST_REACTION_FROM' => 'Réaction de',
    'REACTIONS_DIGEST_ON_DATE'       => 'le',
    'REACTIONS_DIGEST_VIEW_POST'     => 'Voir le message',

    // =========================================================================
    // PIED DE PAGE ET SIGNATURE (Footer and Signature)
    // =========================================================================
    'REACTIONS_DIGEST_SIGNATURE' => "Cordialement,\nL'équipe de %s", // %s est remplacé par le nom du forum
    'REACTIONS_DIGEST_FOOTER'    => 'Vous recevez cet e-mail car vous avez choisi de recevoir les résumés de réactions.',
    'REACTIONS_DIGEST_UNSUBSCRIBE' => 'Pour gérer vos préférences de notification, veuillez visiter votre Panneau de Contrôle de l\'Utilisateur.',
));
