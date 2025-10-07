<?php
/**
 * Type de notification : Reaction
 * (Fichier corrigé)
 *
 * - Le get_type() retourne maintenant le nom canonique EXACT utilisé en base :
 *   "notification.type.reaction"
 * - Le constructeur vérifie et insère le type canonique s'il manque.
 * - Ajout des méthodes statiques get_item_type_name / get_item_type_description
 *   pour que l'UCP affiche une clé de langue correcte (évite le fallback).
 * - get_insert_sql + create_insert_array fournis pour persistance de l'emoji.
 *
 * Copyright 2025 Bastien59960 — Licence GPL v2
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\template\template;
use phpbb\controller\helper;
use phpbb\user_loader;
use phpbb\request\request_interface;
use phpbb\language\language;

class reaction extends base
{
    /** @var driver_interface */
    protected $db;

    /** @var language */
    protected $language;

    /** @var user */
    protected $user;

    /** @var auth */
    protected $auth;

    /** @var config */
    protected $config;

    /** @var helper */
    protected $helper;

    /** @var request_interface */
    protected $request;

    /** @var template */
    protected $template;

    /** @var user_loader */
    protected $user_loader;

    /** @var string */
    protected $phpbb_root_path;

    /** @var string */
    protected $php_ext;

    /** @var string */
    protected $notifications_table;

    /**
     * Constructeur — arguments compatibles avec services.yml
     *
     * Attention : l'ordre est conforme à base::__construct + extras.
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        config $config,
        user_loader $user_loader,
        helper $helper,
        request_interface $request,
        template $template,
        $phpbb_root_path,
        $php_ext,
        $notifications_table
    ) {
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        $this->db = $db;
        $this->language = $language;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->user_loader = $user_loader;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->notifications_table = $notifications_table;

        // Débogage si DEBUG activé
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur OK - DB driver: ' . get_class($db) . ', Table: ' . $notifications_table);
        }

        // Nom canonique (déjà complet) — correspond exactement à ce qu'on stocke en base
        $type_name = $this->get_type(); // ex: 'notification.type.reaction'
        $types_table = 'phpbb_notification_types';

        // Vérifier si la colonne notification_type_name existe
        $col_check_sql = 'SHOW COLUMNS FROM ' . $types_table . " LIKE 'notification_type_name'";
        $col_result = $this->db->sql_query($col_check_sql);
        $col_exists = $this->db->sql_fetchrow($col_result);
        $this->db->sql_freeresult($col_result);

        if ($col_exists) {
            $sql = 'SELECT notification_type_id FROM ' . $types_table . ' WHERE notification_type_name = \'' . $this->db->sql_escape($type_name) . '\' LIMIT 1';
            $result = $this->db->sql_query($sql);
            $exists = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$exists) {
                $proto_data = array(
                    'notification_type_name' => $type_name,
                    'notification_type_enabled' => 1,
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $proto_data));
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions Notification] Prototype type inséré pour: ' . $type_name);
                }
            }
        } else {
            if (defined('DEBUG') && DEBUG) {
                error_log('[Reactions Notification] Colonne notification_type_name manquante dans ' . $types_table . ' - skip insertion prototype.');
            }
        }
    }

    /**
     * Retourne le nom canonique EXACT stocké en base.
     * IMPORTANT : on garde ici le nom avec 'notification.type.reaction' (conforme à la table).
     */
    public function get_type()
    {
        return 'notification.type.reaction';
    }

    /**
     * Doit être disponible (on ne cache pas le type).
     */
    public function is_available()
    {
        return true;
    }

    /* ---------- Méthodes d'identification (élément et parent) ---------- */

    public static function get_item_id($data)
    {
        return (int) ($data['post_id'] ?? 0);
    }

    public static function get_item_parent_id($data)
    {
        return (int) ($data['topic_id'] ?? 0);
    }

    public static function get_item_author_id($data)
    {
        return (int) ($data['post_author'] ?? 0);
    }

    /* ---------- URL / affichage ---------- */

    public function get_url()
    {
        $post_id = $this->get_item_id($this->data ?? []);
        if (!$post_id) {
            return '';
        }

        return append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'p=' . $post_id) . '#p' . $post_id;
    }

    public static function get_item_url($data)
    {
        global $phpbb_root_path, $phpEx;
        $post_id = self::get_item_id($data);
        if (!$post_id) {
            return '';
        }
        return append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $post_id) . '#p' . $post_id;
    }

    /**
     * Clé de langue pour le titre d'une notification individuelle (ex: "%s a réagi ...")
     * Doit exister dans language/*/notification/notification.type.reaction.php
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Nom du fichier de langue (relative à l'extension)
     * Exemple : language/fr/notification/notification.type.reaction.php
     */
    public function get_language_file()
    {
        return 'notification/notification.type.reaction';
    }

    /* ---------- Méthodes statiques attendues par le manager pour l'UCP ---------- */

    /**
     * Nom affiché dans la liste des types (UCP).
     * Retourne une clé de langue.
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_TITLE';
    }

    /**
     * Description affichée dans l'UCP.
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_DESC';
    }

    /* ---------- Détermination des destinataires ---------- */

    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? 0);
        $reacter = (int) ($type_data['reacter'] ?? 0);

        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    public function users_to_query()
    {
        return [];
    }

    /* ---------- Email (ici désactivé / géré par cron si tu veux) ---------- */

    public function get_email_template()
    {
        // false = pas d'email immédiat (on utilise cron groupé si nécessaire)
        return false;
    }

    public function get_email_template_variables()
    {
        return [
            'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
            'EMOJI'            => $this->data['emoji'] ?? '',
            'POST_ID'          => $this->get_item_id($this->data ?? []),
        ];
    }

    /**
     * Titre traduit pour l'utilisateur (message dans la cloche).
     */
    public function get_title_for_user($user_id, $lang)
    {
        return [
            $this->get_title(),
            [
                $this->data['reacter_username'] ?? '',
                $this->data['emoji'] ?? '',
            ],
        ];
    }

    public function get_render_data($user_id)
    {
        return [
            'emoji'            => $this->data['emoji'] ?? '',
            'reacter_username' => $this->data['reacter_username'] ?? '',
            'post_id'          => $this->get_item_id($this->data ?? []),
        ];
    }

    /* ---------- Persistance en base (colonnes custom) ---------- */

    public function get_insert_sql()
    {
        return [
            'reaction_emoji' => ['VCHAR_UNI', 10],
        ];
    }

    /**
     * Construit l'array d'insertion (parent fait les champs standards).
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        $insert_array['reaction_emoji'] = isset($type_data['emoji']) ? $type_data['emoji'] : '';
        return $insert_array;
    }
}
