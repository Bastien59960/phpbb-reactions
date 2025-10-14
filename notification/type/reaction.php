<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/notification/type/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/notification/type/reaction.php
 *
 * Rôle :
 * Définit le type de notification "Réaction à un message". Cette classe est
 * responsable de la création des notifications instantanées (dans la cloche)
 * lorsqu'un utilisateur réagit au message d'un autre.
 *
 * Informations reçues :
 * - Via le `notification_manager` : un tableau de données contenant `post_id`,
 *   `topic_id`, `post_author`, `reacter`, `reacter_username`, et `emoji`.
 *
 * Elle implémente les méthodes requises par phpBB pour trouver les destinataires,
 * générer le texte et le lien de la notification, et la stocker en base de données.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

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

// Vérification de sécurité : empêche l'accès direct au fichier
if (!defined('IN_PHPBB')) {
    exit;
}

/**
 * Classe de notification pour les réactions aux messages
 * 
 * Cette classe hérite de la classe de base des notifications phpBB
 * et implémente toutes les méthodes nécessaires pour gérer les
 * notifications de réactions.
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

    /** @var config|null Configuration du forum */
    protected $config;

    /** @var helper|null Helper de contrôleur pour les URLs */
    protected $helper;

    /** @var request_interface|null Gestionnaire de requêtes */
    protected $request;

    /** @var template|null Moteur de templates */
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
     * IMPORTANT : L'ORDRE DES ARGUMENTS DOIT CORRESPONDRE À services.yml
     * 
     * Les 7 premiers arguments sont requis par la classe parente (base) :
     * 1. db                  → Base de données
     * 2. language            → Gestionnaire de langues
     * 3. user                → Utilisateur courant
     * 4. auth                → Autorisations
     * 5. phpbb_root_path     → Chemin racine phpBB
     * 6. php_ext             → Extension PHP
     * 7. notifications_table → Table des notifications
     * 
     * Les 5 suivants sont spécifiques à cette extension :
     * 8. config              → Configuration du forum
     * 9. user_loader         → Chargeur d'utilisateurs
     * 10. helper             → Helper de contrôleur
     * 11. request            → Gestionnaire de requêtes
     * 12. template           → Moteur de templates
     * 
     * @param driver_interface  $db                  Base de données
     * @param language          $language            Gestionnaire de langues
     * @param user|null         $user                Utilisateur courant
     * @param auth              $auth                Autorisations
     * @param string            $phpbb_root_path     Chemin racine
     * @param string            $php_ext             Extension PHP
     * @param string            $notifications_table Table notifications
     * @param config|null       $config              Configuration
     * @param user_loader       $user_loader         Chargeur d'utilisateurs
     * @param helper|null       $helper              Helper de contrôleur
     * @param request_interface|null $request        Requêtes HTTP
     * @param template|null     $template            Templates
     */
    public function __construct(
        driver_interface $db,
        language $language,
        ?user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table,
        config $config,
        user_loader $user_loader,
        helper $helper,
        ?request_interface $request,
        ?template $template
    ) {
        // Appeler le constructeur de la classe parente avec ses 7 arguments requis
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        // Stocker toutes les dépendances dans les propriétés de la classe
        $this->db = $db;
        $this->language = $language;
        $this->user = $user;
        $this->auth = $auth;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->notifications_table = $notifications_table;
        $this->config = $config;
        $this->user_loader = $user_loader;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;

        // Log de débogage (visible uniquement si DEBUG est activé dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur initialisé - DB: ' . get_class($db));
        }

        // =====================================================================
        // INSERTION AUTOMATIQUE DU TYPE DE NOTIFICATION EN BASE DE DONNÉES
        // =====================================================================
        // Cette section s'assure que le type "notification.type.reaction"
        // existe dans la table phpbb_notification_types
        
        $type_name = self::get_type(); // Récupère "notification.type.reaction"
        $types_table = 'phpbb_notification_types';

        // V├®rifier si la colonne notification_type_name existe dans la table
        $col_check_sql = 'SHOW COLUMNS FROM ' . $types_table . " LIKE 'notification_type_name'";
        $col_result = $this->db->sql_query($col_check_sql);
        $col_exists = $this->db->sql_fetchrow($col_result);
        $this->db->sql_freeresult($col_result);

        if ($col_exists) {
            // Vérifier si le type existe déjà
            $sql = 'SELECT notification_type_id 
                    FROM ' . $types_table . ' 
                    WHERE notification_type_name = \'' . $this->db->sql_escape($type_name) . '\' 
                    LIMIT 1';
            $result = $this->db->sql_query($sql);
            $exists = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            // Si le type n'existe pas, l'insérer
            if (!$exists) {
                $proto_data = array(
                    'notification_type_name'    => $type_name,
                    'notification_type_enabled' => 1, // Activé par défaut
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $proto_data));
                
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions Notification] Type inséré: ' . $type_name);
                }
            }
        } else {
            // La colonne n'existe pas : log d'avertissement
            if (defined('DEBUG') && DEBUG) {
                error_log('[Reactions Notification] ATTENTION: colonne notification_type_name manquante');
            }
        }
    }

    // =========================================================================
    // MÉTHODES D'IDENTIFICATION DU TYPE DE NOTIFICATION
    // =========================================================================

    /**
     * Retourne le nom unique du type de notification
     * 
     * IMPORTANT : Ce nom DOIT être "notification.type.reaction"
     * C'est le nom stocké en base de données et utilisé par phpBB
     * pour identifier ce type de notification.
     * 
     * @return string Le nom canonique du type
     */
    public static function get_type()
    {
        return 'notification.type.reaction';
    }

    /**
     * Indique si ce type de notification est disponible (méthode statique)
     * 
     * Cette méthode permet de désactiver temporairement un type
     * de notification sans le supprimer. Dans notre cas, les
     * réactions sont toujours disponibles.
     * 
     * @return bool True = disponible, False = désactivé
     */
    public function is_available()
    {
        return true;
    }

    // =========================================================================
    // MÉTHODES D'IDENTIFICATION DES ÉLÉMENTS (STATIQUES)
    // ========================================================================= 
    // Ces m├®thodes sont statiques car elles sont appel├®es AVANT
    // la cr├®ation d'une instance de la classe

    /**
     * Extrait l'ID du message depuis les donn├®es de notification
     * 
     * @param array $data Donn├®es de la notification (contient post_id)
     * @return int L'ID du message réagi
     */
    public static function get_item_id($data) 
    {
        return (int) ($data['post_id'] ?? 0);
    }

    /**
     * Extrait l'ID du sujet parent depuis les donn├®es
     * 
     * @param array $data Donn├®es de la notification (contient topic_id)
     * @return int L'ID du sujet contenant le message
     */
    public static function get_item_parent_id($data) 
    {
        return (int) ($data['topic_id'] ?? 0);
    }

    /**
     * Extrait l'ID de l'auteur du message depuis les donn├®es
     * 
     * Cet utilisateur sera le destinataire de la notification
     * (celui qui a écrit le message réagi)
     * 
     * @param array $data Donn├®es de la notification (contient post_author)
     * @return int L'ID de l'auteur du message
     */
    public static function get_item_author_id($data) 
    {
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
    }

    // =========================================================================
    // MÉTHODES DE GÉNÉRATION D'URL
    // ========================================================================= 

    /**
     * G├®n├¿re l'URL vers le message r├®agi (m├®thode d'instance)
     * 
     * Cette URL sera utilis├®e dans la notification pour que
     * l'utilisateur puisse cliquer et acc├®der directement au message.
     * 
     * Format : viewtopic.php?p=123#p123
     * 
     * @return string L'URL compl├¿te vers le message
     */
    public function get_url()
    {
        $post_id = self::get_item_id($this->notification_data);
        
        if (!$post_id) {
            return ''; // Pas d'ID = pas d'URL
        }

        return append_sid(
            "{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    /**
     * G├®n├¿re l'URL vers le message r├®agi (m├®thode statique)
     * 
     * Version statique de get_url(), utilis├®e par phpBB dans certains contextes
     * 
     * @param array $data Donn├®es de la notification
     * @return string L'URL compl├¿te vers le message
     */
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
    // M├ëTHODES DE LANGUE ET AFFICHAGE
    // =========================================================================

    /**
     * Retourne la cl├® de langue pour le titre de la notification
     * 
     * CORRECTION IMPORTANTE : Cette cl├® DOIT correspondre exactement
     * ├á celle d├®finie dans notification/notification.type.reaction.php
     * 
     * Format du message : "%s a r├®agi ├á votre message avec %s"
     * - %s = nom de l'utilisateur qui a r├®agi
     * - %s = emoji utilis├®
     * 
     * @return string La cl├® de langue (SANS le pr├®fixe L_)
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Sp├®cifie le fichier de langue ├á charger pour ce type
     * 
     * phpBB chargera automatiquement le fichier `language/{iso}/notification/reaction.php`
     * de cette extension.
     * 
     * @return string Le chemin au format `vendor/ext:path/filename`
     */
    public function get_language_file()
    {
        return 'bastien59960/reactions:notification/reaction';
    }

    // =========================================================================
    // M├ëTHODES POUR L'UCP (CENTRE DE CONTR├öLE UTILISATEUR)
    // =========================================================================
    // Ces m├®thodes d├®finissent comment le type appara├«t dans l'UCP

    /**
     * Retourne le nom du type affich├® dans l'UCP
     * 
     * Doit correspondre à une clé dans le fichier de langue de la notification.
     * 
     * @return string La cl├® de langue pour le nom
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Retourne la description du type affich├®e dans l'UCP
     * 
     * Doit correspondre à une clé dans le fichier de langue de la notification.
     * 
     * @return string La cl├® de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_TYPE_REACTION_EXPLAIN';
    }

    // =========================================================================
    // D├ëTERMINATION DES DESTINATAIRES
    // =========================================================================

    /**
     * Trouve les utilisateurs qui doivent recevoir cette notification
     * 
     * LOGIQUE :
     * - On notifie l'auteur du message (post_author)
     * - SAUF si c'est lui-m├¬me qui a r├®agi (pas d'auto-notification)
     * 
     * @param array $type_data Donn├®es de la r├®action
     * @param array $options   Options suppl├®mentaires (non utilis├® ici)
     * @return array Liste des IDs utilisateur à notifier
     */
    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? ($type_data['poster_id'] ?? 0));
        $reacter = (int) ($type_data['reacter'] ?? ($type_data['reacter_id'] ?? 0));

        // Notifier l'auteur UNIQUEMENT s'il n'est pas le réacteur
        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    /**
     * Retourne les utilisateurs dont il faut charger les donn├®es
     * 
     * Dans notre cas, on n'a pas besoin de charger de donn├®es
     * utilisateur supplémentaires (le nom est déjà dans $type_data)
     * 
     * @return array Liste vide (pas de chargement n├®cessaire)
     */
    public function users_to_query()
    {
        return [];
    }

    // =========================================================================
    // GESTION DES E-MAILS
    // ========================================================================= 
    // Les e-mails sont désactivés ici car ils sont gérés par le CRON

    /**
     * Désactive l'envoi immédiat d'e-mails
     * 
     * Les e-mails sont gérés par la tâche CRON notification_task
     * qui regroupe les réactions sur une période (anti-spam)
     * 
     * @return bool|string False = pas d'e-mail immédiat
     */
    public function get_email_template()
    {
        return false;
    }

    /**
     * Variables du template d'e-mail (non utilisé car e-mails désactivés)
     * 
     * @return array Variables pour le template d'email
     */
    public function get_email_template_variables()
    {
        return [
            'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
            'EMOJI'            => $this->notification_data['emoji'] ?? '',
            'POST_ID'          => self::get_item_id($this->notification_data),
        ];
    }

    // =========================================================================
    // AFFICHAGE PERSONNALISÉ DE LA NOTIFICATION
    // =========================================================================

    /**
     * Génère le titre de la notification pour un utilisateur spécifique
     * 
     * Retourne un tableau contenant :
     * - [0] : La clé de langue
     * - [1] : Les paramètres à insérer dans la traduction
     * 
     * @param int    $user_id ID de l'utilisateur destinataire (non utilisé ici)
     * @param object $lang    Objet de langue (non utilisé, on utilise get_title())
     * @return array [clé_langue, [paramètres]]
     */
    public function get_title_for_user($user_id, $lang)
    {
        return [
            $this->get_title(), // Clé : NOTIFICATION_TYPE_REACTION
            [
                $this->notification_data['reacter_username'] ?? '', // Param 1 : Nom du réacteur
                $this->notification_data['emoji'] ?? '',             // Param 2 : Emoji
            ],
        ];
    }

    /**
     * Donn├®es de rendu pour l'affichage personnalis├®
     * 
     * Ces données peuvent être utilisées dans un template personnalisé
     * si on veut un affichage plus riche qu'un simple texte
     * 
     * @param int $user_id ID de l'utilisateur destinataire
     * @return array Donn├®es pour le rendu
     */
    public function get_render_data($user_id)
    {
        return [
            'emoji'            => $this->notification_data['emoji'] ?? '',
            'reacter_username' => $this->notification_data['reacter_username'] ?? '',
            'post_id'          => self::get_item_id($this->notification_data),
        ];
    }

    // =========================================================================
    // PERSISTANCE EN BASE DE DONNÉES
    // =========================================================================

    /**
     * Définit les colonnes supplémentaires pour cette notification
     * 
     * phpBB va AUTOMATIQUEMENT créer une colonne "reaction_emoji"
     * dans la table phpbb_notifications si elle n'existe pas
     * 
     * @return array Définition des colonnes personnalisées
     */
    public function get_insert_sql() 
    {
        return [
            'reaction_emoji' => ['VCHAR_UNI', 191], // VARCHAR UNICODE, 191 pour supporter les emojis composés
        ];
    }

    /**
     * Prépare les données à insérer en base pour cette notification
     * 
     * Fusionne les données standards (créées par parent::create_insert_array)
     * avec nos données personnalisées (l'emoji)
     * 
     * @param array $type_data      Donn├®es de la r├®action
     * @param array $pre_create_data Données pré-calculées (optionnel)
     * @return array Tableau complet à insérer en BDD
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        // Cr├®er le tableau de base avec les champs standards
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        
        // Ajouter notre champ personnalisé : l'emoji
        $insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
        
        return $insert_array;
    }
    
}
