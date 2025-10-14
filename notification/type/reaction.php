<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/notification/type/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/notification/type/reaction.php
 *
 * RÃ´le :
 * DÃ©finit le type de notification "RÃ©action Ã  un message". Cette classe est
 * responsable de la crÃ©ation des notifications instantanÃ©es (dans la cloche)
 * lorsqu'un utilisateur rÃ©agit au message d'un autre.
 *
 * Informations reÃ§ues :
 * - Via le `notification_manager` : un tableau de donnÃ©es contenant `post_id`,
 *   `topic_id`, `post_author`, `reacter`, `reacter_username`, et `emoji`.
 * Test insertion commentaire pour UTF8
 * Elle implÃ©mente les mÃ©thodes requises par phpBB pour trouver les destinataires,
 * gÃ©nÃ©rer le texte et le lien de la notification, et la stocker en base de donnÃ©es.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
namespace bastien59960\reactions\notification\type;

// Import des classes phpBB nÃ©cessaires
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
 * Classe de notification pour les rÃ©actions aux messages
 * 
 * Cette classe hÃ©rite de la classe de base des notifications phpBB
 * et implÃ©mente toutes les mÃ©thodes nÃ©cessaires pour gÃ©rer les
 * notifications de rÃ©actions.
 */
class reaction extends base
{
    // =========================================================================
    // PROPRIÃ‰TÃ‰S DE LA CLASSE
    // =========================================================================
    
    /** @var config|null Configuration du forum */
    protected $config;

    /** @var helper|null Helper de contrÃ´leur pour les URLs */
    protected $reactions_helper; // C'est notre helper personnalisÃ©

    /** @var template|null Moteur de templates */
    protected $template;

    /** @var user_loader Chargeur d'utilisateurs */
    protected $user_loader;

    /** @var string Nom de la table des notifications */
    protected $notifications_table;

    /**
     * Constructeur de la classe de notification
     * 
     * IMPORTANT : L'ORDRE DES ARGUMENTS DOIT CORRESPONDRE Ã€ services.yml
     * 
     * Les 7 premiers arguments sont requis par la classe parente (base) :
     * 1. db                  â†’ Base de donnÃ©es
     * 2. language            â†’ Gestionnaire de langues
     * 3. user                â†’ Utilisateur courant
     * 4. auth                â†’ Autorisations
     * 5. phpbb_root_path     â†’ Chemin racine phpBB
     * 6. php_ext             â†’ Extension PHP
     * 7. notifications_table â†’ Table des notifications
     * 
     * Les 5 suivants sont spÃ©cifiques Ã  cette extension :
     * 8. config              â†’ Configuration du forum
     * 9. user_loader         â†’ Chargeur d'utilisateurs
     * 10. reactions_helper   â†’ Helper de contrÃ´leur personnalisÃ©
     * 11. request            â†’ Gestionnaire de requÃªtes (injectÃ© mais non utilisÃ©)
     * 12. template           â†’ Moteur de templates
     * 
     * @param driver_interface  $db                  Base de donnÃ©es
     * @param language          $language            Gestionnaire de langues
     * @param user|null         $user                Utilisateur courant
     * @param auth              $auth                Autorisations
     * @param string            $notifications_table Table notifications
     * @param config|null       $config              Configuration 
     * @param request_interface|null $request        RequÃªtes HTTP
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
        ?config $config,
        user_loader $user_loader,
        ?reactions_helper $reactions_helper,
        ?request_interface $request,
        ?template $template
    ) {
        // Appeler le constructeur de la classe parente
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        // Stocker les dÃ©pendances spÃ©cifiques Ã  cette classe
        $this->notifications_table = $notifications_table;
        $this->config = $config;
        $this->user_loader = $user_loader;
        $this->reactions_helper = $reactions_helper;
        $this->template = $template;

        try
        {
            $this->user->add_lang_ext('bastien59960/reactions', 'reactions');
            $this->user->add_lang_ext('bastien59960/reactions', 'notification/notification.type.reaction');
        }
        catch (\Throwable $e)
        {
            if (defined('DEBUG'))
            {
                error_log('[Reactions Notification] Unable to load language packs (reaction): ' . $e->getMessage());
            }
        }

        // Log de dÃ©bogage (visible uniquement si DEBUG est activÃ© dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur de `reaction` initialisÃ©.');
        }
    }

    // =========================================================================
    // MÃ‰THODES D'IDENTIFICATION DU TYPE DE NOTIFICATION
    // =========================================================================

    /**
     * Retourne le nom unique du type de notification
     * 
     * IMPORTANT : Ce nom DOIT Ãªtre "notification.type.reaction"
     * C'est le nom stockÃ© en base de donnÃ©es et utilisÃ© par phpBB
     * pour identifier ce type de notification.
     * 
     * @return string Le nom canonique du type
     */
    public function get_type()
    {
        return 'notification.type.reaction';
    }

    /**
     * Indique si ce type de notification est disponible (mÃ©thode statique)
     * 
     * Cette mÃ©thode permet de dÃ©sactiver temporairement un type
     * de notification sans le supprimer. Dans notre cas, les
     * rÃ©actions sont toujours disponibles.
     * 
     * @return bool True = disponible, False = dÃ©sactivÃ©
     */
    public function is_available()
    {
        return true;
    }

    // =========================================================================
    // MÃ‰THODES D'IDENTIFICATION DES Ã‰LÃ‰MENTS (STATIQUES)
    // ========================================================================= 
    // Ces mÃ©thodes sont statiques car elles sont appelÃ©es AVANT
    // la crÃ©ation d'une instance de la classe
    // Note : Certaines pourraient devenir non-statiques dans les futures versions de phpBB.
    /**
     * Extrait l'ID du message depuis les donnÃ©es de notification
     * 
     * @param array $data DonnÃ©es de la notification (contient post_id)
     * @return int L'ID du message rÃ©agi
     */
    public static function get_item_id($data) 
    {
        return (int) ($data['post_id'] ?? 0);
    }

    /**
     * Extrait l'ID du sujet parent depuis les donnÃ©es
     * 
     * @param array $data DonnÃ©es de la notification (contient topic_id)
     * @return int L'ID du sujet contenant le message
     */
    public static function get_item_parent_id($data) 
    {
        return (int) ($data['topic_id'] ?? 0);
    }

    /**
     * Extrait l'ID de l'auteur du message depuis les donnÃ©es
     * 
     * Cet utilisateur sera le destinataire de la notification
     * (celui qui a Ã©crit le message rÃ©agi)
     * 
     * @param array $data DonnÃ©es de la notification (contient post_author)
     * @return int L'ID de l'auteur du message
     */
    public static function get_item_author_id($data) 
    {
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
    }

    // =========================================================================
    // MÃ‰THODES DE GÃ‰NÃ‰RATION D'URL
    // ========================================================================= 

    /**
     * GÃ©nÃ¨re l'URL vers le message rÃ©agi (mÃ©thode d'instance)
     * 
     * Cette URL sera utilisÃ©e dans la notification pour que
     * l'utilisateur puisse cliquer et accÃ©der directement au message.
     * 
     * Format : viewtopic.php?p=123#p123
     * 
     * @return string L'URL complÃ¨te vers le message
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
     * GÃ©nÃ¨re l'URL vers le message rÃ©agi (mÃ©thode statique)
     * 
     * Version statique de get_url(), utilisÃ©e par phpBB dans certains contextes
     * 
     * @param array $data DonnÃ©es de la notification
     * @return string L'URL complÃ¨te vers le message
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
    // MÃ‰THODES DE LANGUE ET AFFICHAGE
    // =========================================================================

    /**
     * Retourne la clÃ© de langue pour le titre de la notification
     * 
     * CORRECTION IMPORTANTE : Cette clâ”œÂ® DOIT correspondre exactement
     * Ã  celle dÃ©finie dans notification/notification.type.reaction.php
     * 
     * Format du message : "%s a rÃ©agi Ã  votre message avec %s"
     * - %s = nom de l'utilisateur qui a râ”œÂ®agi
     * - %s = emoji utilisÃ©
     * 
     * @return string La clÃ© de langue (SANS le prÃ©fixe L_)
     */
    public function get_title()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    /**
     * SpÃ©cifie le fichier de langue Ã  charger pour ce type
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
    // MÃ‰THODES POUR L'UCP (CENTRE DE CONTRÃ”LE UTILISATEUR)
    // =========================================================================
    // Ces mÃ©thodes dÃ©finissent comment le type apparaÃ®t dans l'UCP

    /**
     * Retourne le nom du type affichÃ© dans l'UCP
     * 
     * Doit correspondre Ã  une clÃ© dans le fichier de langue de la notification.
     * 
     * @return string La clÃ© de langue pour le nom
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_TYPE_REACTION_TITLE';
    }

    /**
     * Retourne la description du type affichÃ©e dans l'UCP
     * 
     * Doit correspondre Ã  une clÃ© dans le fichier de langue de la notification.
     * 
     * @return string La clâ”œÂ® de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_TYPE_REACTION_DESC';
    }

    // =========================================================================
    // Dâ”œÃ«TERMINATION DES DESTINATAIRES
    // =========================================================================

    /**
     * Trouve les utilisateurs qui doivent recevoir cette notification
     * 
     * LOGIQUE :
     * - On notifie l'auteur du message (poster_id)
     * - SAUF si c'est lui-mÃªme qui a rÃ©agi (pas d'auto-notification)
     * 
     * @param array $type_data DonnÃ©es de la rÃ©action
     * @param array $options   Options supplâ”œÂ®mentaires (non utilisâ”œÂ® ici)
     * @return array Liste des IDs utilisateur Ã  notifier
     */
    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? ($type_data['poster_id'] ?? 0));
        $reacter = (int) ($type_data['reacter'] ?? ($type_data['reacter_id'] ?? 0));

        // Notifier l'auteur UNIQUEMENT s'il n'est pas le rÃ©acteur
        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    /**
     * Retourne les utilisateurs dont il faut charger les donnâ”œÂ®es
     * 
     * Dans notre cas, on n'a pas besoin de charger de donnâ”œÂ®es
     * utilisateur supplÃ©mentaires (le nom est dÃ©jÃ  dans les donnÃ©es de la notification)
     * 
     * @return array Liste vide (pas de chargement nâ”œÂ®cessaire)
     */
    public function users_to_query()
    {
        return [];
    }

    // =========================================================================
    // GESTION DES E-MAILS
    // ========================================================================= 
    // Les e-mails sont dÃ©sactivÃ©s ici car ils sont gÃ©rÃ©s par le CRON

    /**
     * DÃ©sactive l'envoi immÃ©diat d'e-mails
     * 
     * Les e-mails sont gÃ©rÃ©s par la tÃ¢che CRON notification_task
     * qui regroupe les rÃ©actions sur une pÃ©riode (anti-spam)
     * 
     * @return bool|string False = pas d'e-mail immÃ©diat
     */
    public function get_email_template()
    {
        return false;
    }

    /**
     * Variables du template d'e-mail (non utilisÃ© car e-mails dÃ©sactivÃ©s)
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
    // AFFICHAGE PERSONNALISÃ‰ DE LA NOTIFICATION
    // =========================================================================

    /**
     * GÃ©nÃ¨re le titre de la notification pour un utilisateur spÃ©cifique
     * 
     * Retourne un tableau contenant :
     * - [0] : La clÃ© de langue
     * - [1] : Les paramÃ¨tres Ã  insÃ©rer dans la traduction
     * 
     * @param int    $user_id ID de l'utilisateur destinataire (non utilisÃ© ici)
     * @param object $lang    Objet de langue (non utilisÃ©, on utilise get_title())
     * @return array [clÃ©_langue, [paramÃ¨tres]]
     */
    public function get_title_for_user($user_id, $lang)
    {
        return [
            $this->get_title(), // ClÃ© : NOTIFICATION_TYPE_REACTION
            [
                $this->notification_data['reacter_username'] ?? '', // Param 1 : Nom du rÃ©acteur
                $this->notification_data['emoji'] ?? '',             // Param 2 : Emoji
            ],
        ];
    }

    /**
     * DonnÃ©es de rendu pour l'affichage personnalisÃ©
     * 
     * Ces donnÃ©es peuvent Ãªtre utilisÃ©es dans un template personnalisÃ©
     * si on veut un affichage plus riche qu'un simple texte
     * 
     * @param int $user_id ID de l'utilisateur destinataire
     * @return array DonnÃ©es pour le rendu
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
    // PERSISTANCE EN BASE DE DONNÃ‰ES
    // =========================================================================

    /**
     * DÃ©finit les colonnes supplÃ©mentaires pour cette notification
     * 
     * phpBB va AUTOMATIQUEMENT crÃ©er une colonne "reaction_emoji"
     * dans la table phpbb_notifications si elle n'existe pas
     * 
     * @return array DÃ©finition des colonnes personnalisÃ©es
     */
    public function get_insert_sql() 
    {
        return [
            'reaction_emoji' => ['VCHAR_UNI', 191], // VARCHAR UNICODE, 191 pour supporter les emojis composÃ©s
        ];
    }

    /**
     * PrÃ©pare les donnÃ©es Ã  insÃ©rer en base pour cette notification
     * 
     * Fusionne les donnÃ©es standards (crÃ©Ã©es par parent::create_insert_array)
     * avec nos donnÃ©es personnalisÃ©es (l'emoji)
     * 
     * @param array $type_data      DonnÃ©es de la rÃ©action
     * @param array $pre_create_data DonnÃ©es prÃ©-calculÃ©es (optionnel)
     * @return array Tableau complet Ã  insÃ©rer en BDD
     */
    public function create_insert_array($type_data, $pre_create_data = array())
    {
        // CrÃ©er le tableau de base avec les champs standards
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        
        // Ajouter notre champ personnalisÃ© : l'emoji
        $insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
        
        return $insert_array;
    }
    
}
