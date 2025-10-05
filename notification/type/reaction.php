<?php
/**
 * Notification type: reaction
 *
 * Fournit la logique de notification pour les réactions (cloche).
 *
 * - Implémente les méthodes requises par phpbb\notification\type\type_interface
 * - Retourne les utilisateurs à notifier (find_users_for_notification)
 * - Fournit les variables pour le template de notification (si jamais activé)
 *
 * Ce fichier corrige l'erreur fatale liée aux méthodes abstraites manquantes.
 *
 * Copyright (c) 2025 Bastien59960
 * Licence: GNU General Public License v2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;

/**
 * Class reaction
 *
 * Notification type for a reaction added to a post.
 *
 * Minimal, safe implementation:
 * - users_to_query(): retourne [] (aucune requête additionnelle requise)
 * - get_email_template(): retourne false (pas d'e-mail par défaut)
 *
 * Adapte les méthodes si tu veux activer l'envoi d'e-mails ou personnaliser
 * la sélection des utilisateurs.
 */
class reaction extends base
{
    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\request\request_interface */
    protected $request;

    /**
     * Constructeur
     *
     * @param user              $user
     * @param auth              $auth
     * @param template          $template
     * @param config            $config
     * @param request_interface $request
     */
    public function __construct(user $user, auth $auth, template $template, config $config, request_interface $request)
    {
        $this->user = $user;
        $this->auth = $auth;
        $this->template = $template;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Type string identifiant la notification
     *
     * @return string
     */
    public function get_type()
    {
        return 'reaction';
    }

    /**
     * Indique si la notification est disponible (activation extension, etc.)
     *
     * @return bool
     */
    public function is_available()
    {
        return true;
    }

    /**
     * Retourne l'ID de l'item (post_id) pour cette notification
     *
     * @return int
     */
    public function get_item_id()
    {
        return (int) ($data['post_id'] ?? 0);
    }

    /**
     * Retourne l'URL relative à la notification (redirige vers le message)
     *
     * @return string
     */
    public function get_url()
    {
        $post_id = $this->get_item_id();
        if (!$post_id) {
            return '';
        }

        // Redirige vers viewtopic.php?p=POST_ID#pPOST_ID
        return append_sid("{$this->config['script_path']}/viewtopic.{$this->config['php_ext']}", 'p=' . $post_id) . '#p' . $post_id;
    }

    /**
     * Retourne le titre affiché dans l'alerte (lang key ou texte formaté)
     *
     * Utilise la langue phpBB (clé à fournir dans le fichier language).
     *
     * @return string
     */
    public function get_title()
    {
        // Exemple: 'USER reacted with EMOJI to your post' — clé lang à définir.
        // On renvoie une clé lang; phpBB la résoudra via la langue active.
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Trouve les utilisateurs qui doivent être notifiés pour cet événement.
     *
     * Par défaut, notifie l'auteur du post (sauf s'il s'agit du même utilisateur).
     * Retourne un tableau d'IDs utilisateurs.
     *
     * @param array $options
     * @return array Array of user_id integers
     */
    public function find_users_for_notification(array $options = [])
    {
        $users = [];

        // Si post_author présent dans data, on notifie cet utilisateur (sauf si c'est l'auteur de l'action)
        if (!empty($this->data['post_author'])) {
            $post_author = (int) $this->data['post_author'];
            $reacter = (int) ($this->data['reacter'] ?? 0);

            if ($post_author && $post_author !== $reacter) {
                $users[] = $post_author;
            }
        }

        // Optionnel: tu peux ajouter ici d'autres utilisateurs à notifier (ex: watchers)
        return $users;
    }

    /**
     * Méthode requise par type_interface.
     *
     * Retourne la liste des données d'utilisateurs à interroger (clé(s) réseau)
     * pour enrichir la notification via Notification Manager.
     *
     * Dans notre implémentation minimale, nous n'avons pas besoin de requêtes
     * additionnelles (les données sont présentes ou seront récupérées par phpBB),
     * donc on renvoie un tableau vide.
     *
     * @return array
     */
    public function users_to_query()
    {
        // Format attendu : array('user_id_field') ou tableau vide si inutile
        // Voir phpBB core pour exemples plus complexes (post, topic, etc.)
        return [];
    }

    /**
     * Méthode requise par type_interface.
     *
     * Si tu veux permettre l'envoi d'un e-mail pour cette notification,
     * retourne le nom du template email (ex: 'reaction_notify'), sinon false.
     *
     * Ici on désactive les e-mails par défaut (false).
     *
     * @return string|false
     */
    public function get_email_template()
    {
        // Si tu fournis un template email (dans ext/.../email/), retourne son nom.
        // Ex: return 'reaction_notify';
        return false;
    }

    /**
     * Si get_email_template() retourne un template, cette méthode doit retourner
     * les variables à passer au template email.
     *
     * @return array
     */
    public function get_email_template_variables()
    {
        // Exemple minimal — si un template email est activé, adapte ces clés
        return [
            'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
            'EMOJI'            => $this->data['emoji'] ?? '',
            'POST_ID'          => $this->get_item_id(),
        ];
    }

    /**
     * Retourne les données de langue pour la notification (clé + variables).
     *
     * phpBB utilisera ces valeurs pour construire la ligne de notification.
     *
     * @return array ['lang_key' => string, 'lang_vars' => array]
     */
    public function get_title_for_user($user_id, $lang)
    {
        // Si tu gères plusieurs langues, utilise $lang pour choisir la traduction.
        $lang_key = $this->get_title();
        $lang_vars = [
            $this->data['reacter_username'] ?? '',
            $this->data['emoji'] ?? '',
        ];

        return [$lang_key, $lang_vars];
    }

    /**
     * Fournit des variables supplémentaires utiles pour l'affichage dans la liste
     * des notifications (render template).
     *
     * @param int $user_id
     * @return array
     */
    public function get_render_data($user_id)
    {
        return [
            'emoji' => $this->data['emoji'] ?? '',
            'reacter_username' => $this->data['reacter_username'] ?? '',
            'post_id' => $this->get_item_id(),
        ];
    }

    /**
     * Optionnel: retourne le nom du fichier de langue utilisé (par ex. 'language/en')
     *
     * @return string|null
     */
    public function get_language_file()
    {
        // Si tu as des clés langue spécifiques, retourne le nom du fichier (sans extension).
        // Ex: return 'bastien59960/reactions';
        return 'bastien59960/reactions';
    }
}
