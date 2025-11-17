<?php
/**
 * Fichier : reaction_email_digest.php
 * Chemin : bastien59960/reactions/notification/type/reaction_email_digest.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit le type de notification pour l'envoi groupé d'e-mails (digest).
 * Cette classe est utilisée par la tâche cron pour construire et envoyer les
 * e-mails de résumé contenant les nouvelles réactions.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB'))
{
    exit;
}

use phpbb\notification\type\type_interface;

class reaction_email_digest extends \phpbb\notification\type\base implements type_interface
{
    /** @var \phpbb\language\language */
    protected $language;

    /** @var \phpbb\config\config */
    protected $config;

    public function __construct(
        // Dépendances pour le parent
        \phpbb\db\driver\driver_interface $db,
        \phpbb\language\language $language,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        $phpbb_root_path,                   // ✅ SANS type hint
        $php_ext,                           // ✅ SANS type hint
        $notifications_table,               // ✅ SANS type hint
        \phpbb\config\config $config // 8. Injection de @config
    ) {
        parent::__construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table);
        // On assigne manuellement les dépendances non gérées par le parent.
        $this->config = $config;
        $this->language = $language;
    }

    /**
     * Retourne le nom unique du type de notification.
     *
     * @return string
     */
    public function get_type()
    {
        return 'reaction_email_digest';
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
     * Retourne le nom du type affiché dans l'UCP.
     *
     * @return string La clé de langue pour le nom.
     */
    public static function get_item_type_name()
    {
        // Cette clé doit être définie dans le fichier de langue de la notification.
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Retourne la description du type affichée dans l'UCP.
     *
     * @return string La clé de langue pour la description.
     */
    public static function get_item_type_description()
    {
        // Cette clé doit être définie dans le fichier de langue de la notification.
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Spécifie le fichier de langue à charger.
     * Doit pointer vers le fichier unifié.
     *
     * @return string
     */
    public function get_language_file()
    {
        return 'bastien59960/reactions/reactions';
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