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
use phpbb\notification\type\base;           // Classe parente pour tous les types de notification
use phpbb\user;                              // Gestion de l'utilisateur courant
use phpbb\auth\auth;                         // Gestion des autorisations
use phpbb\db\driver\driver_interface;        // Interface de base de données
use phpbb\config\config;                     // Configuration du forum
use phpbb\template\template;                 // Moteur de templates
use phpbb\controller\helper;                 // Helper pour générer les URLs
use phpbb\user_loader;                       // Chargeur d'informations utilisateur
use phpbb\request\request_interface;         // Gestion des requêtes HTTP
use phpbb\language\language;                 // Gestion des langues

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
     * Ce constructeur NE FAIT PLUS d'insertion SQL dans phpbb_notification_types.
     * L'insertion du type est gérée par la migration (migrations/release_1_0_0.php).
     * 
     * Pourquoi cette correction ?
     * - phpBB instancie TOUS les types de notification à chaque chargement de l'UCP
     * - Si le constructeur insère en base, cela crée des doublons → erreur SQL 1062
     * - La bonne pratique : migrations pour la structure, constructeur pour l'initialisation
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
     * @param user              $user                Utilisateur courant
     * @param auth              $auth                Autorisations
     * @param config            $config              Configuration
     * @param user_loader       $user_loader         Chargeur d'utilisateurs
     * @param helper            $helper              Helper de contrôleur
     * @param request_interface $request             Requêtes HTTP
     * @param template          $template            Templates
     * @param string            $phpbb_root_path     Chemin racine
     * @param string            $php_ext             Extension PHP
     * @param string            $notifications_table Table notifications
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
        $this->config = $config;
        $this->user_loader = $user_loader;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->notifications_table = $notifications_table;

        // Log de débogage (visible uniquement si DEBUG est activé dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur initialisé - Type: ' . $this->get_type());
        }

        // ⚠️  CORRECTION : PLUS D'INSERTION SQL ICI
        // Le type de notification est créé par la migration (migrations/release_1_0_0.php)
        // et activé/désactivé par ext.php lors de l'activation/désactivation de l'extension
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
     * Ce nom est utilisé dans :
     * - phpbb_notification_types (colonne notification_type_name)
     * - ext.php (méthodes enable_notifications/disable_notifications)
     * - migrations/release_1_0_0.php (création du type)
     * 
     * @return string Le nom canonique du type
     */
    public function get_type()
    {
        return 'notification.type.reaction';
    }

    /**
     * Indique si ce type de notification est disponible
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
    // Ces méthodes sont statiques car elles sont appelées AVANT
    // la création d'une instance de la classe

    /**
     * Extrait l'ID du message depuis les données de notification
     * 
     * @param array $data Données de la notification (contient post_id)
     * @return int L'ID du message réagi
     */
    public static function get_item_id($data)
    {
        return (int) ($data['post_id'] ?? 0);
    }

    /**
     * Extrait l'ID du sujet parent depuis les données
     * 
     * @param array $data Données de la notification (contient topic_id)
     * @return int L'ID du sujet contenant le message
     */
    public static function get_item_parent_id($data)
    {
        return (int) ($data['topic_id'] ?? 0);
    }

    /**
     * Extrait l'ID de l'auteur du message depuis les données
     * 
     * Cet utilisateur sera le destinataire de la notification
     * (celui qui a écrit le message réagi)
     * 
     * @param array $data Données de la notification (contient post_author)
     * @return int L'ID de l'auteur du message
     */
    public static function get_item_author_id($data)
    {
        return (int) ($data['post_author'] ?? 0);
    }

    // =========================================================================
    // MÉTHODES DE GÉNÉRATION D'URL
    // =========================================================================

    /**
     * Génère l'URL vers le message réagi (méthode d'instance)
     * 
     * Cette URL sera utilisée dans la notification pour que
     * l'utilisateur puisse cliquer et accéder directement au message.
     * 
     * Format : viewtopic.php?p=123#p123
     * 
     * @return string L'URL complète vers le message
     */
    public function get_url()
    {
        $post_id = $this->get_item_id($this->data ?? []);
        
        if (!$post_id) {
            return ''; // Pas d'ID = pas d'URL
        }

        return append_sid(
            "{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    /**
     * Génère l'URL vers le message réagi (méthode statique)
     * 
     * Version statique de get_url(), utilisée par phpBB dans certains contextes
     * 
     * @param array $data Données de la notification
     * @return string L'URL complète vers le message
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
    // MÉTHODES DE LANGUE ET AFFICHAGE
    // =========================================================================

    /**
     * Retourne la clé de langue pour le titre de la notification
     * 
     * Cette clé DOIT correspondre exactement à celle définie dans 
     * language/*/notification/notification.type.reaction.php
     * 
     * Format du message : "%s a réagi à votre message avec %s"
     * - %s = nom de l'utilisateur qui a réagi
     * - %s = emoji utilisé
     * 
     * @return string La clé de langue (SANS le préfixe L_)
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION';
    }

    /**
     * Spécifie le fichier de langue à charger pour ce type
     * 
     * phpBB chargera automatiquement :
     * - language/fr/notification/notification.type.reaction.php (français)
     * - language/en/notification/notification.type.reaction.php (anglais)
     * 
     * @return string Le chemin relatif du fichier de langue
     */
    public function get_language_file()
    {
        return 'notification/notification.type.reaction';
    }

    // =========================================================================
    // MÉTHODES POUR L'UCP (CENTRE DE CONTRÔLE UTILISATEUR)
    // =========================================================================
    // Ces méthodes définissent comment le type apparaît dans l'UCP

    /**
     * Retourne le nom du type affiché dans l'UCP
     * 
     * Cette clé est définie dans language/*/notification/notification.type.reaction.php
     * 
     * @return string La clé de langue pour le nom
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE';
    }

    /**
     * Retourne la description du type affichée dans l'UCP
     * 
     * Cette clé est définie dans language/*/notification/notification.type.reaction.php
     * 
     * @return string La clé de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC';
    }

    // =========================================================================
    // DÉTERMINATION DES DESTINATAIRES
    // =========================================================================

    /**
     * Trouve les utilisateurs qui doivent recevoir cette notification
     * 
     * LOGIQUE :
     * - On notifie l'auteur du message (post_author)
     * - SAUF si c'est lui-même qui a réagi (pas d'auto-notification)
     * 
     * @param array $type_data Données de la réaction
     * @param array $options   Options supplémentaires (non utilisé ici)
     * @return array Liste des IDs utilisateur à notifier
     */
    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? 0);
        $reacter = (int) ($type_data['reacter'] ?? 0);

        // Notifier l'auteur UNIQUEMENT s'il n'est pas le réacteur
        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    /**
     * Retourne les utilisateurs dont il faut charger les données
     * 
     * Dans notre cas, on n'a pas besoin de charger de données
     * utilisateur supplémentaires (le nom est déjà dans $type_data)
     * 
     * @return array Liste vide (pas de chargement nécessaire)
     */
    public function users_to_query()
    {
        return [];
    }

    // =========================================================================
    // GESTION DES EMAILS
    // =========================================================================
    // Les emails sont désactivés ici car ils sont gérés par le CRON

    /**
     * Désactive l'envoi immédiat d'emails
     * 
     * Les emails sont gérés par la tâche CRON notification_task
     * qui regroupe les réactions sur une période (anti-spam)
     * 
     * @return bool|string False = pas d'email immédiat
     */
    public function get_email_template()
    {
        return false;
    }

    /**
     * Variables du template d'email (non utilisé car emails désactivés)
     * 
     * @return array Variables pour le template d'email
     */
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

    /**
     * Génère le titre de la notification pour un utilisateur spécifique
     * 
     * Retourne un tableau contenant :
     * - [0] : La clé de langue
     * - [1] : Les paramètres à insérer dans la traduction
     * 
     * @param int    $user_id ID de l'utilisateur destinataire
     * @param object $lang    Objet de langue (non utilisé, on utilise get_title())
     * @return array [clé_langue, [paramètres]]
     */
    public function get_title_for_user($user_id, $lang)
    {
        return [
            $this->get_title(), // Clé : NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION
            [
                $this->data['reacter_username'] ?? '', // Param 1 : Nom du réacteur
                $this->data['emoji'] ?? '',             // Param 2 : Emoji
            ],
        ];
    }

    /**
     * Données de rendu pour l'affichage personnalisé
     * 
     * Ces données peuvent être utilisées dans un template personnalisé
     * si on veut un affichage plus riche qu'un simple texte
     * 
     * @param int $user_id ID de l'utilisateur destinataire
     * @return array Données pour le rendu
     */
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
            'reaction_emoji' => ['VCHAR_UNI', 10], // VARCHAR UNICODE, 10 caractères max
        ];
    }

    /**
     * Prépare les données à insérer en base pour cette notification
     * 
     * Fusionne les données standards (créées par parent::create_insert_array)
     * avec nos données personnalisées (l'emoji)
     * 
     * @param array $type_data      Données de la réaction
     * @param array $pre_create_data Données pré-calculées (optionnel)
     * @return array Tableau complet à insérer en BDD
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        // Créer le tableau de base avec les champs standards
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        
        // Ajouter notre champ personnalisé : l'emoji
        $insert_array['reaction_emoji'] = isset($type_data['emoji']) ? $type_data['emoji'] : '';
        
        return $insert_array;
    }
}
