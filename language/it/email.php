<?php
/**
 * Fichier : email.php
 * Chemin : bastien59960/reactions/language/en/email.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Contient les chaînes de langue anglaises pour les e-mails envoyés par
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
    // EMAIL SUBJECT
    // =========================================================================
    'REACTIONS_DIGEST_SUBJECT' => 'New reactions to your posts',

    // =========================================================================
    // HEADER AND INTRODUCTION
    // =========================================================================
    'REACTIONS_DIGEST_HELLO' => 'Hello %1$s,',
    'REACTIONS_DIGEST_INTRO' => 'Here is a summary of new reactions to your posts on "%1$s":',

    // =========================================================================
    // POSTS CONTENT
    // =========================================================================
    'REACTIONS_DIGEST_POST_TITLE' => 'Reactions on your post "%1$s"',

    // =========================================================================
    // REACTION LABELS
    // =========================================================================
    'REACTIONS_DIGEST_REACTION_FROM' => 'Reaction from',
    'REACTIONS_DIGEST_ON_DATE'       => 'on',
    'REACTIONS_DIGEST_VIEW_POST'     => 'View post',

    // =========================================================================
    // FOOTER AND SIGNATURE
    // =========================================================================
    'REACTIONS_DIGEST_SIGNATURE' => "Sincerely,\nThe %s Team", // %s is replaced by the forum name
    'REACTIONS_DIGEST_FOOTER'      => 'You are receiving this email because you have chosen to receive reaction summaries.',
    'REACTIONS_DIGEST_UNSUBSCRIBE' => 'To manage your notification preferences, please visit your User Control Panel.',
));