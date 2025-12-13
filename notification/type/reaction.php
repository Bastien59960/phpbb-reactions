<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /notification/type/reaction.php
 * Rôle :
 * Définit le type de notification "Réaction à un message". Cette classe est
 * responsable de la création des notifications instantanées (dans la cloche)
 * lorsqu'un utilisateur réagit au message d'un autre.
 * Elle implémente les méthodes requises par phpBB pour trouver les destinataires,
 * générer le texte et le lien de la notification, et la stocker en base de données.
 */
namespace bastien59960\reactions\notification\type;

// Import des classes phpBB nécessaires
use phpbb\notification\type\base;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\notification\type\type_interface;
use phpbb\language\language;


/**
 * Classe de notification pour les réactions aux messages
 * 
 * Cette classe hérite de la classe de base des notifications phpBB
 * et implémente toutes les méthodes nécessaires pour gérer les
 * notifications de réactions.
 */
class reaction extends base implements type_interface
{
    // =========================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =========================================================================
    
    /** @var string Nom de la table des notifications */
    protected $notifications_table;

    /**
     * Constructeur de la classe de notification
     * 
     * IMPORTANT : L'ORDRE DES ARGUMENTS DOIT CORRESPONDRE À services.yml
     * 
     */
    public function __construct(
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table
    ) {
        // Appeler le constructeur de la classe parente
        // L'ordre attendu par \phpbb\notification\type\base est : db, language, user, auth, root_path, php_ext, notifications_table
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

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
     * IMPORTANT : Ce nom doit correspondre EXACTEMENT au nom de service défini
     * dans services.yml (bastien59960.reactions.notification.type.reaction).
     *
     * @return string Le nom complet du service
     */
    public function get_type()
    {
        return 'bastien59960.reactions.notification.type.reaction';
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
    /**
     * Extrait l'ID du message depuis les données de notification
     * Cette méthode est statique car phpBB peut avoir besoin de cet ID sans instancier l'objet.
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
     * Utile pour regrouper ou filtrer les notifications par sujet.
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
     * Cet utilisateur sera le destinataire principal de la notification.
     * 
     * @param array $data Données de la notification (contient post_author)
     * @return int L'ID de l'auteur du message
     */
    public static function get_item_author_id($data) 
    {
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
    }

    /**
     * Spécifie le type d'élément auquel cette notification est liée.
     *
     * CRUCIAL : En retournant 'post', on dit à phpBB que cette notification
     * concerne un message. phpBB utilisera alors le système de permissions
     * des messages (f_read) pour déterminer si l'utilisateur peut voir la notification.
     * Sans cela, la notification est créée mais jamais affichée.
     */
    public static function get_item_type()
    {
        return 'post';
    }

    // =========================================================================
    // MÉTHODES DE GÉNÉRATION D'URL
    // ========================================================================= 

    /**
     * Génère l'URL vers le message réagi (méthode d'instance)
     * 
     * Cette URL sera utilisée dans la notification pour que
     * l'utilisateur puisse cliquer et accéder directement au message.
     * Le format est `viewtopic.php?p=123#p123`.
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
     * Version statique de get_url(), utilisée par phpBB dans des contextes où l'objet n'est pas instancié.
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
     * à celle définie dans le fichier de langue de la notification.
     * Le message attendu est du type : "%1$s a réagi à votre message avec %2$s".
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
     * CORRECTION : Retourner `false` pour indiquer à phpBB de NE PAS charger
     * de fichier de langue spécifique. Le chargement est déjà géré de manière
     * centralisée par le listener d'événements (`event/listener.php`), qui charge
     * `common.php` où toutes nos clés sont maintenant.
     * 
     * @return bool|string
     */
    public function get_language_file()
    {
        return false;
    }

    // =========================================================================
    // MÉTHODES POUR L'UCP (CENTRE DE CONTRÔLE UTILISATEUR)
    // =========================================================================
    // Ces méthodes définissent comment le type apparaît dans l'UCP

    /**
     * Retourne le nom du type affiché dans l'UCP
     * 
     * Doit correspondre à une clé dans le fichier de langue de la notification (ex: 'Réactions à vos messages').
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
     * Doit correspondre à une clé dans le fichier de langue de la notification.
     * 
     * @return string La clé de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_TYPE_REACTION_DESC';
	}

	/**
	 * Définit les méthodes de notification par défaut pour les nouveaux utilisateurs.
	 * 
	 * @return array Méthodes par défaut (ex: 'notification.method.board')
	 */
	public function get_default_methods()
	{
		// Activer par défaut les notifications sur le forum (cloche)
		return array('notification.method.board');
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
     * Retourne les utilisateurs dont il faut charger les données
     * 
     * Dans notre cas, on n'a pas besoin de charger de données
     * utilisateur supplémentaires car le nom est déjà dans les données de la notification.
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
     * Les e-mails sont gérés par la tâche CRON `notification_task`
     * qui regroupe les réactions sur une période pour éviter le spam.
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
        // CORRECTION : Le notification_manager de phpBB désérialise automatiquement les données.
        // On doit donc TOUJOURS s'attendre à recevoir un tableau ici.
        $data = $this->notification_data;

        return [
            $this->get_title(), // Clé : NOTIFICATION_TYPE_REACTION
            [
                $data['reacter_name'] ?? 'Quelqu\'un', // %1$s : Nom du réacteur
                !empty($data['reaction_emoji']) ? $data['reaction_emoji'] : 'XXX', // %2$s : Emoji (placeholder)
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

}
