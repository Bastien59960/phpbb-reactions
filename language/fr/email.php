<?php
/**
 * Fichier : language/fr/email.php
 * Extension : bastien59960/reactions
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises spécifiquement
 * utilisées dans les templates d'e-mail de l'extension.
 *
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    'REACTIONS_DIGEST_SUBJECT'      => 'Résumé des nouvelles réactions sur vos messages',
    'REACTIONS_DIGEST_GREETING'     => 'Bonjour %s,',
    'REACTIONS_DIGEST_INTRO'        => 'Voici un résumé des nouvelles réactions que vos messages ont reçues récemment.',
    'REACTIONS_DIGEST_SIGNATURE'    => 'Merci de votre participation sur %s.',
    'REACTIONS_DIGEST_POST_SUBJECT' => 'Sujet',
    'REACTIONS_DIGEST_POST_LINK'    => 'Lien',
    'REACTIONS_DIGEST_REACTIONS'    => 'Réactions reçues :',
    'REACTIONS_DIGEST_BY'           => 'par',
    'REACTIONS_DIGEST_ON'           => 'le',
    'REACTIONS_DIGEST_PROFILE'      => 'Profil',
    'REACTIONS_PREFERENCES_HINT'    => 'Astuce : Vous pouvez modifier ou désactiver ces notifications depuis votre Panneau de Contrôle Utilisateur > Préférences des réactions.',
]);