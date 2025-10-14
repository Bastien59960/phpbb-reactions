<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions/notification/type/reaction_email_digest.php
 * Fichier : reaction_email_digest.php
 * Chemin : bastien59960/reactions/notification/type/reaction_email_digest.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/notification/type/reaction_email_digest.php
 *
 * Type de notification "Résumé e-mail des réactions" pour l'extension Reactions.
 * Rôle :
 * Définit le type de notification "Résumé e-mail des réactions". Cette classe est
 * utilisée exclusivement par la tâche CRON (`cron/notification_task.php`) pour
 * envoyer un e-mail récapitulatif (digest) des réactions reçues.
 *
 * Ce type est utilisé exclusivement par la tâche cron afin d'envoyer un digest périodique
 * des réactions reçues. Aucune entrée n'est créée dans la cloche phpBB.
 * Contrairement à `reaction.php`, ce type ne crée pas de notification visible
 * dans la cloche. Il sert uniquement de "véhicule" pour envoyer un e-mail
 * formaté avec un template spécifique (`reaction_digest.txt`).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\user;
use phpbb\auth\auth;

class reaction_email_digest extends base
{
    /**
     * Constructeur de la classe.
     *
     * IMPORTANT : L'ordre des arguments doit correspondre à celui de la classe parente.
     *
     * @param driver_interface $db                  Base de données
     * @param language         $language            Gestionnaire de langues
     * @param user             $user                Utilisateur courant
     * @param auth             $auth                Autorisations
     * @param string           $phpbb_root_path     Chemin racine phpBB
     * @param string           $php_ext             Extension PHP
     * @param string           $notifications_table Table des notifications
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table
    ) {
        // Appeler le constructeur de la classe parente avec toutes les dépendances requises.
        parent::__construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table);

        try
        {
            $this->user->add_lang_ext('bastien59960/reactions', 'reactions');
            $this->user->add_lang_ext('bastien59960/reactions', 'notification/notification.type.reaction_email_digest');
        }
        catch (\Throwable $e)
        {
            if (defined('DEBUG'))
            {
                error_log('[Reactions Notification] Unable to load language packs (reaction_email_digest): ' . $e->getMessage());
            }
        }
    }

    /**
     * Identifiant unique du type de notification.
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Clé de langue affichée dans l'UCP (titre).
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Clé de langue affichée dans l'UCP (description).
     */
    public static function get_item_type_description()
    {
        // Note: La clé de langue a été corrigée pour correspondre aux fichiers de langue.
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Utilisateurs à pré-charger.
     * Requis par l'interface, mais non utilisé ici.
     * @return array
     */
    public function users_to_query()
    {
        return [];
    }

    /**
     * Retourne la clé de langue pour le titre de la notification.
     *
     * Requis par l'interface, même si ce type de notification n'a pas de
     * titre individuel affiché dans la cloche. Nous retournons une clé
     * générique pour la conformité.
     *
     * @return string La clé de langue pour le titre.
     */
    public function get_title()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Aucune notification individuelle n'est créée : tableau vide.
     */
    public function find_users_for_notification($data, $options = array())
    {
        return [];
    }

    public function get_language_file()
    {
        return 'bastien59960/reactions';
    }

    public function get_email_template()
    {
        return 'reaction_digest';
    }

    public function get_email_template_variables()
    {
        $author_data = $this->notification_data['author_data'] ?? [];

        $recap_lines_arr = [];

        if (!empty($author_data['posts']))
        {
            foreach ($author_data['posts'] as $post_id => $post_data)
            {
                $post_subject = $post_data['post_subject'] ?: '[sans sujet]';
                foreach ($post_data['reactions'] as $reaction)
                {
                    $when = date('d/m/Y H:i', (int) $reaction['time']);
                    $reactor = $reaction['reacter_name'] ?: ('Utilisateur #' . $reaction['reacter_id']);
                    $emoji = $reaction['emoji'] ?: '?';
                    $recap_lines_arr[] = sprintf(
                        '- Le %s, %s a réagi avec %s à votre message : "%s"',
                        $when, $reactor, $emoji, $post_subject
                    );
                }
            }
        }

        return [
            'USERNAME'    => $this->notification_data['author_name'] ?? ($author_data['author_name'] ?? 'Utilisateur'),
            'SINCE_TIME'  => $this->notification_data['since_time'] ?? 'la dernière fois', // Note: SINCE_TIME n'est plus dans le template
            'RECAP_LINES' => implode("\n", $recap_lines_arr),
        ];
    }

    /**
     * Cette notification n'est pas liée à un item spécifique.
     * Requis par la classe de base.
     */
    public static function get_item_id($data)
    {
        return 0;
    }

    /**
     * Cette notification n'a pas de parent.
     * Requis par la classe de base.
     */
    public static function get_item_parent_id($data)
    {
        return 0;
    }

    /**
     * Cette notification n'a pas d'URL cliquable.
     * Requis par la classe de base.
     */
    public function get_url()
    {
        return '';
    }

    /**
     * Cette notification n'a pas d'URL cliquable (version statique).
     * Requis par la classe de base.
     */
    public static function get_item_url($data)
    {
        return '';
    }

    /**
     * L'auteur est le destinataire, mais ce n'est pas un "item".
     * Requis par la classe de base.
     */
    public static function get_item_author_id($data)
    {
        return 0;
    }
}
