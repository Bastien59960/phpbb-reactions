<?php
/**
 * Fichier : email.php
 * Chemin : bastien59960/reactions/language/fr/email.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Contient les chaînes de langue françaises pour les e-mails envoyés par
 * l'extension. Ce fichier est utilisé par la tâche cron pour construire
 * l'e-mail de résumé des réactions.
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

$lang = array_merge($lang, array(
    // =========================================================================
    // SUJET DE L'E-MAIL
    // =========================================================================
    'REACTIONS_DIGEST_SUBJECT' => 'Nouvelles réactions à vos messages',

    // =========================================================================
    // EN-TÊTE ET INTRODUCTION
    // =========================================================================
    'REACTIONS_DIGEST_HELLO' => 'Bonjour %1$s,',
    'REACTIONS_DIGEST_INTRO' => 'Voici un résumé des nouvelles réactions à vos messages sur "%1$s" :',

    // =========================================================================
    // CONTENU DES MESSAGES
    // =========================================================================
    'REACTIONS_DIGEST_POST_TITLE' => 'Réactions sur votre message "%1$s"',

    // =========================================================================
    // LIBELLÉS DES RÉACTIONS
    // =========================================================================
    'REACTIONS_DIGEST_REACTION_FROM' => 'Réaction de',
    'REACTIONS_DIGEST_ON_DATE'       => 'le',
    'REACTIONS_DIGEST_VIEW_POST'     => 'Voir le message',

    // =========================================================================
    // PIED DE PAGE ET SIGNATURE
    // =========================================================================
    'REACTIONS_DIGEST_SIGNATURE' => "Cordialement,\nL'équipe %s", // %s est remplacé par le nom du forum
    'REACTIONS_DIGEST_FOOTER'    => 'Vous recevez cet e-mail car vous avez choisi de recevoir des résumés de réactions.',
    'REACTIONS_DIGEST_UNSUBSCRIBE' => 'Pour gérer vos préférences de notification, veuillez visiter votre Panneau de Contrôle Utilisateur.',
));