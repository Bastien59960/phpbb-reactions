<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * Type de notification "Résumé e-mail des réactions" pour l'extension Reactions.
 *
 * Ce fichier définit la classe de notification utilisée pour envoyer un résumé périodique par e-mail des réactions reçues sur les messages d'un utilisateur (notification par cron).
 *
 * Points clés de la logique métier :
 *   - Ce type n'est pas stocké individuellement dans la table des notifications (pas de cloche)
 *   - Utilisé uniquement pour l'affichage dans l'UCP et la gestion des préférences
 *   - Génère les clés de langue pour le titre et la description dans l'UCP
 *   - Fournit le nom du template d'e-mail utilisé par le cron
 *
 * Ce type de notification permet à l'utilisateur d'activer ou non le résumé e-mail dans ses préférences UCP.
 *
 * IMPORTANT - Architecture des notifications :
 * 
 * 📧 NOM DU TYPE : 'notification.type.reaction_email_digest'
 *    - Défini dans get_type()
 *    - Stocké dans phpbb_notification_types
 *    - Créé par la migration (migrations/release_1_0_0.php)
 *    - Activé/désactivé par ext.php
 * 
 * 📋 DIFFÉRENCE AVEC reaction.php :
 *    - reaction.php : Notification cloche instantanée (dans le forum)
 *    - reaction_email_digest.php : Notification email groupée (envoyée par cron)
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

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;

class reaction_email_digest extends base
{
    /**
     * Spécifie le fichier de langue à charger pour ce type
     */
    public function get_language_file()
    {
        return 'notification/notification.type.reaction_email_digest';
    }

    /**
     * Retourne le nom du template d'email utilisé par le cron
     */
    public function get_email_template()
    {
        return 'reaction_digest';  // Correspond au fichier reaction_digest.txt
    }

    /**
     * Variables du template d'email (gérées par le cron)
     */
    public function get_email_template_variables()
    {
        return array();
    }

    /**
     * ID de l'élément (non utilisé)
     */
    public static function get_item_id($data)
    {
        return 0;
    }

    /**
     * ID du parent (non utilisé)
     */
    public static function get_item_parent_id($data)
    {
        return 0;
    }

    /**
     * Utilisateurs à charger (aucun)
     */
    public function users_to_query()
    {
        return array();
    }

    /**
     * URL de la notification (aucune)
     */
    public function get_url()
    {
        return '';
    }

    /**
     * Constructeur simplifié
     * 
     * Ce constructeur NE FAIT PLUS d'insertion SQL dans phpbb_notification_types.
     * L'insertion du type est gérée par la migration (migrations/release_1_0_0.php).
     */
    public function __construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table)
    {
        parent::__construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table);
        
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Email Digest] Constructeur initialisé - Type: ' . $this->get_type());
        }

        // ⚠️  Aucune insertion SQL ici (migration gère la création du type)
    }

    /**
     * Nom unique du type de notification
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Méthodes de notification disponibles
     */
    public function get_notification_methods()
    {
        return array('email');
    }

    /**
     * Clé de langue du titre (UCP)
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Clé de langue de la description (UCP)
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Ce type ne crée pas de notifications individuelles
     */
    public function find_users_for_notification($data, $options = array())
    {
        return array();
    }

    /**
     * Ce type ne crée pas d'entrées dans phpbb_notifications
     */
    public function create_insert_array($data, $pre_create_data = array())
    {
        return array();
    }

    /**
     * Pas de titre (pas d'affichage dans la cloche)
     */
    public function get_title()
    {
        return '';
    }
}
