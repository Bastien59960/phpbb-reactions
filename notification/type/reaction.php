<?php
/**
 * Fichier : notification/type/reaction.php — bastien59960/reactions/notification/type/reaction.php
 *
 * Type de notification "Réaction à un message" pour l'extension Reactions.
 *
 * Ce fichier définit la classe de notification utilisée pour informer un utilisateur lorsqu'un autre réagit à l'un de ses messages (notification cloche immédiate).
 *
 * Points clés de la logique métier :
 *   - Génération et affichage des notifications dans l'UCP et la cloche
 *   - Détermination des destinataires (auteur du message, sauf auto-notification)
 *   - Génération des URLs et titres personnalisés
 *   - Intégration avec le système de langue et de templates
 *   - Gestion des données personnalisées (emoji, utilisateur réacteur)
 *
 * Ce type de notification est enregistré dans phpBB et utilisé par le contrôleur AJAX lors de l'ajout d'une réaction.
 *
 * IMPORTANT - Architecture des notifications :
 * 
 * 📋 NOM DU TYPE : 'notification.type.reaction'
 *    - Défini dans get_type()
 *    - Stocké dans phpbb_notification_types
 *    - Créé par la migration (migrations/release_1_0_0.php)
 *    - Activé/désactivé par ext.php
 * 
 * ⚠️  ERREUR CORRIGÉE : 
 *    Le constructeur NE DOIT PLUS insérer le type en base de données.
 *    Cette insertion est gérée UNIQUEMENT par la migration.
 *    Raison : phpBB instancie TOUS les types à chaque chargement de l'UCP,
 *    ce qui causait des tentatives d'insertion en double → erreur SQL 1062
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

// Vérification de sécurité : empêche l'accès direct au fichier
if (!defined('IN_PHPBB')) {
    exit;
}

// Import des classes phpBB nécessaires
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

/**
 * Classe de notification pour les réactions aux messages
 */
class reaction extends base
{
    // =========================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =========================================================================
    
    /** @var driver_interface Base de données */
    protected $db;

    /** @var language Gestionnaire de langues */
    protected $language;

    /** @var user Utilisateur courant */
    protected $user;

    /** @var auth Gestionnaire d'autorisations */
    protected $auth;

    /** @var config Configuration du forum */
    protected $config;

    /** @var helper Helper de contrôleur pour les URLs */
    protected $helper;

    /** @var request_interface Gestionnaire de requêtes */
    protected $request;

    /** @var template Moteur de templates */
    protected $template;

    /** @var user_loader Chargeur d'utilisateurs */
    protected $user_loader;

    /** @var string Chemin racine de phpBB */
    protected $phpbb_root_path;

    /** @var string Extension des fichiers PHP */
    protected $php_ext;

    /** @var string Nom de la table des notifications */
    protected $notifications_table;

   /**
     * Constructeur de la classe de notification
     * 
     * CORRECTION CRITIQUE :
     * - Appel du constructeur parent avec les arguments de base
     * - Ajout des arguments spécifiques ensuite
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table,
        config $config,
        user_loader $user_loader,
        helper $helper,
        request_interface $request,
        template $template
    ) {
        // Appel du constructeur parent avec les 7 premiers arguments
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        // Assignation des propriétés supplémentaires
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

        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur initialisé - Type: ' . $this->get_type());
        }
    }

    // =========================================================================
    // PROTECTION: set_notification_manager pour éviter l'exception fatale
    // =========================================================================
    /**
     * phpBB appelle set_notification_manager lors de l'initialisation.
     * Si la migration n'a pas encore créé la ligne dans phpbb_notification_types,
     * get_notification_type_id() peut lancer phpbb\notification\exception.
     *
     * Ici on appelle proprement parent::set_notification_manager() et on
     * attrape l'exception NOTIFICATION_TYPE_NOT_EXIST afin d'éviter un fatal error.
     */
    public function set_notification_manager(\phpbb\notification\manager $notification_manager)
    {
        $this->notification_manager = $notification_manager;

        try {
            parent::set_notification_manager($notification_manager);
        } catch (\phpbb\notification\exception $e) {
            // Si le type n'existe pas encore en base, on logge en DEBUG et on continue.
            if (defined('DEBUG')) {
                error_log('[Reactions Notification] set_notification_manager failed: ' . $e->getMessage());
            }
            $this->notification_type_id = null;
        }
    }

    // =========================================================================
    // MÉTHODES D'IDENTIFICATION DU TYPE DE NOTIFICATION
    // =========================================================================

    /**
     * Retourne le nom unique du type de notification
     * Ce nom doit correspondre EXACTEMENT à celui enregistré par la migration.
     */
    public function get_type()
    {
        // **IMPORTANT** : doit correspondre à la migration et à ext.php
        return 'notification.type.reaction';
    }

    public function is_available()
    {
        return true;
    }

    // =========================================================================
    // MÉTHODES D'IDENTIFICATION DES ÉLÉMENTS (STATIQUES)
    // =========================================================================

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

    // =========================================================================
    // MÉTHODES DE GÉNÉRATION D'URL
    // =========================================================================

    public function get_url()
    {
        $post_id = $this->get_item_id($this->data ?? []);
        
        if (!$post_id) {
            return '';
        }

        return append_sid(
            "{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    public static function get_item_url($data)
    {
        global $phpbb_root_path, $phpEx;
        
        $post_id = self::get_item_id($data);
        
        if (!$post_id) {
            return '';
        }
        
        return append_sid(
            "{$phpbb_root_path}viewtopic.$phpEx", 
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    // =========================================================================
    // MÉTHODES DE LANGUE ET AFFICHAGE
    // =========================================================================

    public function get_title()
    {
        // Utilise une clé de langue cohérente avec phpBB
        return $this->language->lang('NOTIFICATION_REACTION');
    }

    public function get_language_file()
    {
        // Doit correspondre au fichier language/{lang}/reactions.php
        return 'notification/notification.type.reaction';
    }

    // =========================================================================
    // MÉTHODES POUR L'UCP (CENTRE DE CONTRÔLE UTILISATEUR)
    // =========================================================================

    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION';
    }

    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_DESC';
    }

  /**
     * Détermination des destinataires
     * 
     * Cette méthode détermine qui doit recevoir la notification.
     * IMPORTANT : Elle doit retourner un tableau d'user_id
     */
    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? 0);
        $reacter = (int) ($type_data['reacter'] ?? 0);

        // Debug : log les données reçues
        error_log('[Reactions Notification] find_users_for_notification appelée - post_author=' . $post_author . ', reacter=' . $reacter);

        // On notifie l'auteur du post SAUF s'il réagit à son propre message
        if ($post_author > 0 && $post_author !== $reacter) {
            $users[] = $post_author;
            error_log('[Reactions Notification] Utilisateur à notifier : ' . $post_author);
        } else {
            error_log('[Reactions Notification] Aucun utilisateur à notifier (auto-réaction ou auteur invalide)');
        }

        return $users;
    }

    /**
     * Utilisateurs dont les données doivent être chargées
     * 
     * Retourne les IDs des utilisateurs dont on a besoin des infos (le réacteur)
     */
    public function users_to_query()
    {
        return array((int) $this->get_data('reacter'));
    }

    // =========================================================================
    // GESTION DES EMAILS
    // =========================================================================

    public function get_email_template()
    {
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

    // =========================================================================
    // AFFICHAGE PERSONNALISÉ DE LA NOTIFICATION
    // =========================================================================

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

    // =========================================================================
    // PERSISTANCE EN BASE DE DONNÉES
    // =========================================================================

    /**
     * Construit le tableau d'insertion pour une notification
     * Hérité de phpbb\notification\type\base
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        $insert_array['reaction_emoji'] = isset($type_data['emoji']) ? $type_data['emoji'] : '';
        
        return $insert_array;
    }
}
