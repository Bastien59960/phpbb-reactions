<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions
 *
 * Définit le type de notification pour l'envoi groupé d'e-mails (digest).
 * Cette classe est essentielle pour que le système de messagerie de phpBB
 * puisse trouver et utiliser les templates d'e-mail de l'extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB'))
{
    exit;
}

class reaction_email_digest extends \phpbb\notification\type\base
{
    public function __construct(
        // Dépendances pour le parent
        \phpbb\db\driver\driver_interface $db,
        \phpbb\language\language $language,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table,
        // Dépendances pour cette classe
        \phpbb\template\template $template,
        \phpbb\config\config $config
    )
    {
        // 1. Appel explicite du constructeur parent
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        // 2. Stockage des dépendances spécifiques
        $this->template = $template;
        $this->config = $config;
    }

    /**
     * Retourne le nom unique du type de notification.
     *
     * @return string
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Indique si ce type de notification est disponible.
     *
     * @return bool
     */
    public function is_available()
    {
        return true;
    }

    /**
     * Spécifie le nom du template d'e-mail à utiliser.
     * phpBB cherchera 'reaction_digest.html' et 'reaction_digest.txt'.
     *
     * @return string|bool Le nom du template ou false si aucun e-mail n'est envoyé.
     */
    public function get_email_template()
    {
        return 'reaction_digest';
    }

    /**
     * Trouve l'utilisateur à notifier. Dans notre cas, c'est l'auteur du message.
     */
    public function find_users_for_notification($data, $options = [])
    {
        // Le tableau d'utilisateurs est passé directement par la tâche cron via la clé 'users'
        return $data['users'];
    }

    /**
     * Prépare les données pour le template d'e-mail.
     * C'est ici que nous assignons toutes nos variables.
     */
    public function get_email_template_variables()
    {
        // Variables globales
        $vars = [
            'USERNAME'         => $this->notification_data['author_name'],
            'DIGEST_SINCE'     => $this->notification_data['since_time_formatted'],
            'DIGEST_UNTIL'     => date('d/m/Y H:i'),
            'DIGEST_SIGNATURE' => sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $this->config['sitename']),
        ];

        // Assignation des blocs (posts et réactions)
        if (!empty($this->notification_data['posts']))
        {
            foreach ($this->notification_data['posts'] as $post_data)
            {
                $this->template->assign_block_vars('posts', [
                    'SUBJECT_PLAIN'     => $post_data['SUBJECT_PLAIN'],
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                foreach ($post_data['reactions'] as $reaction)
                {
                    $this->template->assign_block_vars('posts.reactions', $reaction);
                }
            }
        }

        return $vars;
    }


    /**
     * Cette notification est gérée par une tâche cron et n'est pas créée directement.
     * Ces méthodes ne sont donc pas utilisées mais doivent exister.
     */
    public static function get_item_id($data) { return 0; }

    /**
     * Méthodes abstraites requises par l'interface, mais non utilisées pour ce type.
     * Elles doivent exister et retourner une valeur par défaut valide.
     */
    public static function get_item_parent_id($data)
    {
        return 0;
    }

    public function users_to_query()
    {
        return [];
    }

    public function get_title() { return ''; }
    public function get_url() { return ''; }
}