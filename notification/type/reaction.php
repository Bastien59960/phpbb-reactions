<?php
/**
 * Fichier : notification/type/reaction.php ÔÇö bastien59960/reactions/notification/type/reaction.php
 * Fichier : notification/type/reaction.php — bastien59960/reactions/notification/type/reaction.php
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Type de notification "R├®action ├á un message" pour l'extension Reactions.
 * Type de notification "Réaction à un message" pour l'extension Reactions.
 *
 * Ce fichier d├®finit la classe de notification utilis├®e pour informer un utilisateur lorsqu'un autre r├®agit ├á l'un de ses messages (notification cloche imm├®diate).
 * Ce fichier définit la classe de notification utilisée pour informer un utilisateur lorsqu'un autre réagit à l'un de ses messages (notification cloche immédiate).
 *
 * Points cl├®s de la logique m├®tier :
 *   - G├®n├®ration et affichage des notifications dans l'UCP et la cloche
 *   - D├®termination des destinataires (auteur du message, sauf auto-notification)
 *   - G├®n├®ration des URLs et titres personnalis├®s
 *   - Int├®gration avec le syst├¿me de langue et de templates
 *   - Gestion des donn├®es personnalis├®es (emoji, utilisateur r├®acteur)
 * Points clés de la logique métier :
 *   - Génération et affichage des notifications dans l'UCP et la cloche
 *   - Détermination des destinataires (auteur du message, sauf auto-notification)
 *   - Génération des URLs et titres personnalisés
 *   - Intégration avec le système de langue et de templates
 *   - Gestion des données personnalisées (emoji, utilisateur réacteur)
 *
 * Ce type de notification est enregistr├® dans phpBB et utilis├® par le contr├┤leur AJAX lors de l'ajout d'une r├®action.
 * Ce type de notification est enregistré dans phpBB et utilisé par le contrôleur AJAX lors de l'ajout d'une réaction.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

// V├®rification de s├®curit├® : emp├¬che l'acc├¿s direct au fichier
if (!defined('IN_PHPBB')) {
    exit;
}

// Import des classes phpBB n├®cessaires
// Import des classes phpBB nécessaires
use phpbb\notification\type\base;           // Classe parente pour tous les types de notification
use phpbb\user;                              // Gestion de l'utilisateur courant
use phpbb\auth\auth;                         // Gestion des autorisations
use phpbb\db\driver\driver_interface;        // Interface de base de donn├®es
use phpbb\db\driver\driver_interface;        // Interface de base de données
use phpbb\config\config;                     // Configuration du forum
use phpbb\template\template;                 // Moteur de templates
use phpbb\controller\helper;                 // Helper pour g├®n├®rer les URLs
use phpbb\controller\helper;                 // Helper pour générer les URLs
use phpbb\user_loader;                       // Chargeur d'informations utilisateur
use phpbb\request\request_interface;         // Gestion des requ├¬tes HTTP
use phpbb\request\request_interface;         // Gestion des requêtes HTTP
use phpbb\language\language;                 // Gestion des langues

// Vérification de sécurité : empêche l'accès direct au fichier
if (!defined('IN_PHPBB')) {
    exit;
}

/**
 * Classe de notification pour les r├®actions aux messages
 * 
 * Cette classe h├®rite de la classe de base des notifications phpBB
 * et impl├®mente toutes les m├®thodes n├®cessaires pour g├®rer les
 * notifications de r├®actions.
 */
class reaction extends base
{
    // =========================================================================
    // PROPRI├ëT├ëS DE LA CLASSE
    // =========================================================================
    

    /** @var driver_interface Base de donn├®es */
    protected $db;

    /** @var language Gestionnaire de langues */
    protected $language;

    /** @var user Utilisateur courant */
    /** @var user|null Utilisateur courant */
    protected $user;

    /** @var auth Gestionnaire d'autorisations */
    protected $auth;

    /** @var config Configuration du forum */
    /** @var config|null Configuration du forum */
    protected $config;

    /** @var helper Helper de contr├┤leur pour les URLs */
    /** @var helper|null Helper de contr├┤leur pour les URLs */
    protected $helper;

    /** @var request_interface Gestionnaire de requ├¬tes */
    /** @var request_interface|null Gestionnaire de requ├¬tes */
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
     * IMPORTANT : L'ORDRE DES ARGUMENTS DOIT CORRESPONDRE ├Ç services.yml
     * IMPORTANT : L'ORDRE DES ARGUMENTS DOIT CORRESPONDRE À services.yml
     * 
     * Les 7 premiers arguments sont requis par la classe parente (base) :
     * 1. db                  ÔåÆ Base de donn├®es
     * 2. language            ÔåÆ Gestionnaire de langues
     * 3. user                ÔåÆ Utilisateur courant
     * 4. auth                ÔåÆ Autorisations
     * 5. phpbb_root_path     ÔåÆ Chemin racine phpBB
     * 6. php_ext             ÔåÆ Extension PHP
     * 7. notifications_table ÔåÆ Table des notifications
     * 1. db                  → Base de données
     * 2. language            → Gestionnaire de langues
     * 3. user                → Utilisateur courant
     * 4. auth                → Autorisations
     * 5. phpbb_root_path     → Chemin racine phpBB
     * 6. php_ext             → Extension PHP
     * 7. notifications_table → Table des notifications
     * 
     * Les 5 suivants sont sp├®cifiques ├á cette extension :
     * 8. config              ÔåÆ Configuration du forum
     * 9. user_loader         ÔåÆ Chargeur d'utilisateurs
     * 10. helper             ÔåÆ Helper de contr├┤leur
     * 11. request            ÔåÆ Gestionnaire de requ├¬tes
     * 12. template           ÔåÆ Moteur de templates
     * Les 5 suivants sont spécifiques à cette extension :
     * 8. config              → Configuration du forum
     * 9. user_loader         → Chargeur d'utilisateurs
     * 10. helper             → Helper de contrôleur
     * 11. request            → Gestionnaire de requêtes
     * 12. template           → Moteur de templates
     * 
     * @param driver_interface  $db                  Base de donn├®es
     * @param language          $language            Gestionnaire de langues
     * @param user              $user                Utilisateur courant
     * @param auth              $auth                Autorisations
     * @param config            $config              Configuration
     * @param user_loader       $user_loader         Chargeur d'utilisateurs
     * @param helper            $helper              Helper de contr├┤leur
     * @param request_interface $request             Requ├¬tes HTTP
     * @param template          $template            Templates
     * @param string            $phpbb_root_path     Chemin racine
     * @param string            $php_ext             Extension PHP
     * @param string            $notifications_table Table notifications
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        ?user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table,
        config $config,
        user_loader $user_loader,
        helper $helper,
        request_interface $request,
        ?request_interface $request,
        template $template
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

        // Stocker toutes les d├®pendances dans les propri├®t├®s de la classe
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

        // Log de d├®bogage (visible uniquement si DEBUG est activ├® dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur initialis├® - DB: ' . get_class($db));
        }

        // =====================================================================
        // INSERTION AUTOMATIQUE DU TYPE DE NOTIFICATION EN BASE DE DONN├ëES
        // =====================================================================
        // Cette section s'assure que le type "notification.type.reaction"
        // Cette section s'assure que le type "bastien59960.reactions.notification.type.reaction"
        // existe dans la table phpbb_notification_types
        
        $type_name = $this->get_type(); // R├®cup├¿re "notification.type.reaction"
        $types_table = 'phpbb_notification_types';

        // V├®rifier si la colonne notification_type_name existe dans la table
        $col_check_sql = 'SHOW COLUMNS FROM ' . $types_table . " LIKE 'notification_type_name'";
        $col_result = $this->db->sql_query($col_check_sql);
        $col_exists = $this->db->sql_fetchrow($col_result);
        $this->db->sql_freeresult($col_result);

        if ($col_exists) {
            // V├®rifier si le type existe d├®j├á
            $sql = 'SELECT notification_type_id 
            $sql = 'SELECT notification_type_id
                    FROM ' . $types_table . ' 
                    WHERE notification_type_name = \'' . $this->db->sql_escape($type_name) . '\' 
                    LIMIT 1';
            $result = $this->db->sql_query($sql);
            $exists = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            // Si le type n'existe pas, l'ins├®rer
            if (!$exists) {
                $proto_data = array(
                    'notification_type_name'    => $type_name,
                    'notification_type_enabled' => 1, // Activ├® par d├®faut
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $proto_data));
                
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions Notification] Type ins├®r├®: ' . $type_name);
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
    // M├ëTHODES D'IDENTIFICATION DU TYPE DE NOTIFICATION
    // =========================================================================

    /**
     * Retourne le nom unique du type de notification
     * 
     * IMPORTANT : Ce nom DOIT ├¬tre "notification.type.reaction"
     * IMPORTANT : Ce nom DOIT être "bastien59960.reactions.notification.type.reaction"
     * C'est le nom stock├® en base de donn├®es et utilis├® par phpBB
     * pour identifier ce type de notification.
     * 
     * @return string Le nom canonique du type
     */
    public function get_type()
    public static function get_type()
    {
        return 'notification.type.reaction';
    }

    /**
     * Indique si ce type de notification est disponible
     * 
     * Cette m├®thode permet de d├®sactiver temporairement un type
     * Cette méthode permet de désactiver temporairement un type
     * de notification sans le supprimer. Dans notre cas, les
     * r├®actions sont toujours disponibles.
     * 
     * @return bool True = disponible, False = d├®sactiv├®
     */
    public function is_available()
    {
        return true;
    }

    // =========================================================================
    // M├ëTHODES D'IDENTIFICATION DES ├ëL├ëMENTS (STATIQUES)
    // =========================================================================
    // ========================================================================= 
    // Ces m├®thodes sont statiques car elles sont appel├®es AVANT
    // la cr├®ation d'une instance de la classe

    /**
     * Extrait l'ID du message depuis les donn├®es de notification
     * 
     * @param array $data Donn├®es de la notification (contient post_id)
     * @return int L'ID du message r├®agi
     */
    public static function get_item_id($data)
    {
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
    { 
        return (int) ($data['topic_id'] ?? 0);
    }

    /**
     * Extrait l'ID de l'auteur du message depuis les donn├®es
     * 
     * Cet utilisateur sera le destinataire de la notification
     * (celui qui a ├®crit le message r├®agi)
     * (celui qui a écrit le message réagi)
     * 
     * @param array $data Donn├®es de la notification (contient post_author)
     * @return int L'ID de l'auteur du message
     */
    public static function get_item_author_id($data)
    {
    { 
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
    }

    // =========================================================================
    // M├ëTHODES DE G├ëN├ëRATION D'URL
    // =========================================================================

    /**
     * G├®n├¿re l'URL vers le message r├®agi (m├®thode d'instance)
     * Génère l'URL vers le message réagi (méthode d'instance)
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
        $post_id = $this->get_item_id($this->data ?? []);
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
     * Génère l'URL vers le message réagi (méthode statique)
     * 
     * Version statique de get_url(), utilis├®e par phpBB dans certains contextes
     * 
     * @param array $data Donn├®es de la notification
     * @return string L'URL compl├¿te vers le message
     */
    public static function get_item_url($data)
    {
    { 
        global $phpbb_root_path, $phpEx;
        
        $post_id = self::get_item_id($data);
        
        if (!$post_id) {
            return '';
            return ''; 
        }
        
        return append_sid(
            "{$phpbb_root_path}viewtopic.$phpEx", 
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
     * à celle définie dans notification/notification.type.reaction.php
     * 
     * Format du message : "%s a r├®agi ├á votre message avec %s"
     * - %s = nom de l'utilisateur qui a r├®agi
     * - %s = emoji utilis├®
     * 
     * @return string La cl├® de langue (SANS le pr├®fixe L_)
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION';
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Sp├®cifie le fichier de langue ├á charger pour ce type
     * 
     * phpBB chargera automatiquement :
     * - language/fr/notification/notification.type.reaction.php (fran├ºais)
     * - language/en/notification/notification.type.reaction.php (anglais)
     * 
     * @return string Le chemin relatif du fichier de langue
     */
    public function get_language_file()
    {
        return 'notification/notification.type.reaction';
        return 'bastien59960/reactions:notification/reaction';
    }

    // =========================================================================
    // M├ëTHODES POUR L'UCP (CENTRE DE CONTR├öLE UTILISATEUR)
    // =========================================================================
    // Ces m├®thodes d├®finissent comment le type appara├«t dans l'UCP

    /**
     * Retourne le nom du type affich├® dans l'UCP
     * 
     * CORRECTION : Utilise la cl├® compl├¿te avec le pr├®fixe
     * D├®finie dans notification/notification.type.reaction.php
     * Définie dans notification/notification.type.reaction.php
     * 
     * @return string La cl├® de langue pour le nom
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE';
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Retourne la description du type affich├®e dans l'UCP
     * 
     * CORRECTION : Utilise la cl├® compl├¿te avec le pr├®fixe
     * D├®finie dans notification/notification.type.reaction.php
     * Définie dans notification/notification.type.reaction.php
     * 
     * @return string La cl├® de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC';
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
     * - SAUF si c'est lui-même qui a réagi (pas d'auto-notification)
     * 
     * @param array $type_data Donn├®es de la r├®action
     * @param array $options   Options suppl├®mentaires (non utilis├® ici)
     * @return array Liste des IDs utilisateur ├á notifier
     */
    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? ($type_data['poster_id'] ?? 0));
        $reacter = (int) ($type_data['reacter'] ?? ($type_data['reacter_id'] ?? 0));

        // Notifier l'auteur UNIQUEMENT s'il n'est pas le r├®acteur
        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    /**
     * Retourne les utilisateurs dont il faut charger les donn├®es
     * 
     * Dans notre cas, on n'a pas besoin de charger de donn├®es
     * utilisateur suppl├®mentaires (le nom est d├®j├á dans $type_data)
     * 
     * @return array Liste vide (pas de chargement n├®cessaire)
     */
    public function users_to_query()
    {
        return [];
    }

    // =========================================================================
    // GESTION DES EMAILS
    // =========================================================================
    // Les emails sont d├®sactiv├®s ici car ils sont g├®r├®s par le CRON

    /**
     * D├®sactive l'envoi imm├®diat d'emails
     * 
     * Les emails sont g├®r├®s par la t├óche CRON notification_task
     * qui regroupe les r├®actions sur une p├®riode (anti-spam)
     * 
     * @return bool|string False = pas d'email imm├®diat
     */
    public function get_email_template()
    {
        return false;
    }

    /**
     * Variables du template d'email (non utilis├® car emails d├®sactiv├®s)
     * 
     * @return array Variables pour le template d'email
     */
    public function get_email_template_variables()
    {
        return [
            'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
            'EMOJI'            => $this->data['emoji'] ?? '',
            'POST_ID'          => $this->get_item_id($this->data ?? []),
            'REACTOR_USERNAME' => $this->notification_data['reacter_username'] ?? '',
            'EMOJI'            => $this->notification_data['emoji'] ?? '',
            'POST_ID'          => self::get_item_id($this->notification_data),
        ];
    }

    // =========================================================================
    // AFFICHAGE PERSONNALIS├ë DE LA NOTIFICATION
    // =========================================================================

    /**
     * G├®n├¿re le titre de la notification pour un utilisateur sp├®cifique
     * 
     * Retourne un tableau contenant :
     * - [0] : La cl├® de langue
     * - [1] : Les param├¿tres ├á ins├®rer dans la traduction
     * 
     * @param int    $user_id ID de l'utilisateur destinataire
     * @param object $lang    Objet de langue (non utilis├®, on utilise get_title())
     * @param int    $user_id ID de l'utilisateur destinataire (non utilisé ici)
     * @param object $lang    Objet de langue (non utilisé, on utilise get_title())
     * @return array [cl├®_langue, [param├¿tres]]
     */
    public function get_title_for_user($user_id, $lang)
    {
        return [
            $this->get_title(), // Cl├® : NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION
            [
                $this->data['reacter_username'] ?? '', // Param 1 : Nom du r├®acteur
                $this->data['emoji'] ?? '',             // Param 2 : Emoji
            [ 
                $this->notification_data['reacter_username'] ?? '', // Param 1 : Nom du r├®acteur
                $this->notification_data['emoji'] ?? '',             // Param 2 : Emoji
            ],
        ];
    }

    /**
     * Donn├®es de rendu pour l'affichage personnalis├®
     * 
     * Ces donn├®es peuvent ├¬tre utilis├®es dans un template personnalis├®
     * Ces données peuvent être utilisées dans un template personnalisé
     * si on veut un affichage plus riche qu'un simple texte
     * 
     * @param int $user_id ID de l'utilisateur destinataire
     * @return array Donn├®es pour le rendu
     */
    public function get_render_data($user_id)
    {
        return [
            'emoji'            => $this->data['emoji'] ?? '',
            'reacter_username' => $this->data['reacter_username'] ?? '',
            'post_id'          => $this->get_item_id($this->data ?? []),
            'emoji'            => $this->notification_data['emoji'] ?? '',
            'reacter_username' => $this->notification_data['reacter_username'] ?? '',
            'post_id'          => self::get_item_id($this->notification_data),
        ];
    }

    // =========================================================================
    // PERSISTANCE EN BASE DE DONN├ëES
    // =========================================================================

    /**
     * D├®finit les colonnes suppl├®mentaires pour cette notification
     * 
     * phpBB va AUTOMATIQUEMENT cr├®er une colonne "reaction_emoji"
     * dans la table phpbb_notifications si elle n'existe pas
     * 
     * @return array D├®finition des colonnes personnalis├®es
     */
    public function get_insert_sql()
    {
        return [
            'reaction_emoji' => ['VCHAR_UNI', 10], // VARCHAR UNICODE, 10 caract├¿res max
            'reaction_emoji' => ['VCHAR_UNI', 191], // VARCHAR UNICODE, 191 caractères max pour utf8mb4
        ];
    }

    /**
     * Pr├®pare les donn├®es ├á ins├®rer en base pour cette notification
     * 
     * Fusionne les donn├®es standards (cr├®├®es par parent::create_insert_array)
     * avec nos donn├®es personnalis├®es (l'emoji)
     * 
     * @param array $type_data      Donn├®es de la r├®action
     * @param array $pre_create_data Donn├®es pr├®-calcul├®es (optionnel)
     * @return array Tableau complet ├á ins├®rer en BDD
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        // Cr├®er le tableau de base avec les champs standards
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        
        // Ajouter notre champ personnalis├® : l'emoji
        $insert_array['reaction_emoji'] = isset($type_data['emoji']) ? $type_data['emoji'] : '';
        $insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
        
        return $insert_array;
    }
    
} // Ô£à ACCOLADE FERMANTE AJOUT├ëE (correction de l'erreur ligne 209)
}
