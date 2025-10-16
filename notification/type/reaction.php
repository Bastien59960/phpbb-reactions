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
 * Test insertion commentaire pour UTF8
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
use bastien59960\reactions\controller\helper as reactions_helper;
use phpbb\user_loader;
use phpbb\request\request_interface;
use phpbb\language\language;


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
    
    /** @var config|null Configuration du forum */
    protected $config;

    /** @var helper|null Helper de contrôleur pour les URLs */
    protected $reactions_helper; // C'est notre helper personnalisé

    /** @var template|null Moteur de templates */
    protected $template;

    /** @var user_loader Chargeur d'utilisateurs */
    protected $user_loader;

    /** @var language */
    protected $language;

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
     * 8. config            → Configuration
     * 9. user_loader       → Chargeur d'utilisateurs
     * 10. template         → Moteur de templates
     * 11. reactions_helper → Helper personnalisé
     * 
     * @param driver_interface  $db                  Base de données 
     * @param language          $language            Gestionnaire de langues
     * @param user|null         $user                Utilisateur courant
     * @param auth              $auth                Autorisations
     * @param string            $notifications_table Table notifications
     * @param config|null       $config              Configuration 
     * @param template|null     $template            Templates
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        string $phpbb_root_path,
        string $php_ext,
        $notifications_table,
        config $config,
        user_loader $user_loader,
        template $template,
        reactions_helper $reactions_helper
    ) {
        // Appeler le constructeur de la classe parente
		parent::__construct($user, $auth, $db, $phpbb_root_path, $php_ext, $notifications_table, $language);

        // Stocker les dépendances spécifiques à cette classe
        $this->notifications_table = $notifications_table;
        $this->config = $config;
        $this->user_loader = $user_loader;
		$this->template = $template;
        $this->reactions_helper = $reactions_helper;

        try
        {
            $this->user->add_lang_ext('bastien59960/reactions', 'common');
        }
        catch (\Throwable $e)
        {
            if (defined('DEBUG'))
            {
                error_log('[Reactions Notification] Unable to load language packs (reaction): ' . $e->getMessage());
            }
        }

        // Log de débogage (visible uniquement si DEBUG est activé dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur de `reaction` initialisé.');
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
    public function get_type()
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
    // Ces méthodes sont statiques car elles sont appelées AVANT
    // la création d'une instance de la classe
    // Note : Certaines pourraient devenir non-statiques dans les futures versions de phpBB.
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
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
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
     * CORRECTION IMPORTANTE : Cette clé DOIT correspondre exactement
     * à celle définie dans notification/notification.type.reaction.php
     * 
     * Format du message : "%s a réagi à votre message avec %s"
     * - %s = nom de l'utilisateur qui a réagi
     * - %s = emoji utilisé
     * 
     * @return string La clé de langue (SANS le préfixe L_).
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * Spécifie le fichier de langue à charger pour ce type
     * 
     * phpBB chargera automatiquement le fichier `language/{iso}/notification/reaction.php`
     * de cette extension.
     * 
     * @return string Le chemin au format `vendor/ext:path/filename`
     */
    public function get_language_file()
    {
        return 'bastien59960/reactions';
    }

    // =========================================================================
    // MÉTHODES POUR L'UCP (CENTRE DE CONTRÔLE UTILISATEUR)
    // =========================================================================
    // Ces méthodes définissent comment le type apparaît dans l'UCP

    /**
     * Retourne le nom du type affiché dans l'UCP
     * 
     * Doit correspondre à une clé dans le fichier de langue de la notification.
     * 
     * @return string La clé de langue pour le nom
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_TYPE_REACTION_TITLE';
    }

    /**
     * Retourne la description du type affichée dans l'UCP
     * 
     * Doit correspondre à une clé dans le fichier de langue de la notification
     * 
     * @return string La clé de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_TYPE_REACTION_DESC';
    }

    // =========================================================================
    // DÉTERMINATION DES DESTINATAIRES
    // =========================================================================

    /**
     * Trouve les utilisateurs qui doivent recevoir cette notification
     * 
     * LOGIQUE :
     * - On notifie l'auteur du message (poster_id)
     * - SAUF si c'est lui-même qui a réagi (pas d'auto-notification)
     * 
     * @param array $type_data Données de la réaction.
     * @param array $options   Options supplémentaires (non utilisé ici).
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
     * utilisateur supplémentaires (le nom est déjà dans les données de la notification)
     * 
     * @return array Liste vide (pas de chargement nécessaire)
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
    public function get_title_for_user($user_id, $lang = null)
    {
        return [
            $this->get_title(), // Clé : NOTIFICATION_TYPE_REACTION
            [
                $this->notification_data['reacter_username'] ?? 'Quelqu\'un', // Param 1 : Nom du réacteur
                $this->notification_data['emoji'] ?? '?',             // Param 2 : Emoji
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
     * @param array $type_data      Données de la réaction
     * @param array $pre_create_data Données pré-calculées (optionnel)
     * @return array Tableau complet à insérer en BDD
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        // Créer le tableau de base avec les champs standards
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        
        // Ajouter notre champ personnalisé : l'emoji
        $insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
        
        return $insert_array;
    }
    
}
