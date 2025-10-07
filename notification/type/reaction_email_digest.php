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

/**
 * Classe de notification pour les résumés email de réactions
 * 
 * Ce type spécial ne crée PAS de notifications individuelles.
 * Il est utilisé uniquement pour :
 * 1. Afficher l'option dans les préférences UCP
 * 2. Permettre à l'utilisateur d'activer/désactiver les emails groupés
 * 3. Fournir le template d'email au cron (cron/notification_task.php)
 */
class reaction_email_digest extends base
{
    /**
     * Spécifie le fichier de langue à charger pour ce type
     * 
     * phpBB chargera automatiquement :
     * - language/fr/notification/notification.type.reaction_email_digest.php
     * - language/en/notification/notification.type.reaction_email_digest.php
     * 
     * @return string Le chemin relatif du fichier de langue
     */
    public function get_language_file()
    {
        return 'notification/notification.type.reaction_email_digest';
    }

    /**
     * Retourne le nom du template d'email utilisé par le cron
     * 
     * Le cron (cron/notification_task.php) utilise ce template pour
     * composer les emails de résumé. Le template se trouve dans :
     * - language/fr/email/reaction.txt (français)
     * - language/en/email/reaction.txt (anglais)
     * 
     * IMPORTANT : On retourne 'reaction' (sans .txt), phpBB ajoute
     * automatiquement l'extension et cherche dans le bon dossier language/
     * 
     * @return string Le nom du template (sans extension)
     */
    public function get_email_template()
    {
        return 'reaction';
    }

    /**
     * Variables du template d'email
     * 
     * Ces variables sont remplies par le cron et injectées dans le template.
     * Pour ce type, le cron gère lui-même les variables, donc on retourne
     * un tableau vide ici.
     * 
     * Les variables réelles utilisées dans le template sont :
     * - {USERNAME} : Nom de l'utilisateur destinataire
     * - {REACTOR_NAMES} : Liste des utilisateurs ayant réagi
     * - {POST_TITLE} : Titre du sujet
     * - {U_POST_LINK} : URL vers le message
     * - {TITLE} : Titre de l'email
     * - {EMAIL_SIG} : Signature de l'email
     * 
     * @return array Variables pour le template (géré par le cron)
     */
    public function get_email_template_variables()
    {
        return array();
    }

    /**
     * Méthodes requises par l'interface type_interface
     * 
     * Ces méthodes doivent être implémentées car elles font partie
     * de l'interface, mais pour ce type spécial elles retournent
     * des valeurs vides car on ne crée pas de notifications individuelles.
     */

    /**
     * ID de l'élément (non utilisé pour ce type)
     * 
     * @param array $data Données de notification
     * @return int Toujours 0
     */
    public static function get_item_id($data)
    {
        return 0;
    }

    /**
     * ID du parent (non utilisé pour ce type)
     * 
     * @param array $data Données de notification
     * @return int Toujours 0
     */
    public static function get_item_parent_id($data)
    {
        return 0;
    }

    /**
     * Utilisateurs à charger (aucun)
     * 
     * @return array Toujours vide
     */
    public function users_to_query()
    {
        return array();
    }

    /**
     * URL de la notification (aucune)
     * 
     * Ce type n'a pas d'URL car il n'apparaît pas dans la cloche.
     * Les URLs sont dans les emails envoyés par le cron.
     * 
     * @return string Toujours vide
     */
    public function get_url()
    {
        return '';
    }
}

     * Constructeur simplifié
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
     * Ce constructeur ne fait que :
     * 1. Appeler le constructeur parent
     * 2. Logger l'initialisation (si DEBUG activé)
     * 
     * @param driver_interface $db                  Base de données
     * @param language         $language            Gestionnaire de langues
     * @param user             $user                Utilisateur courant
     * @param auth             $auth                Autorisations
     * @param string           $phpbb_root_path     Chemin racine phpBB
     * @param string           $php_ext             Extension PHP
     * @param string           $notifications_table Table des notifications
     */
    public function __construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table)
    {
        parent::__construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table);
        
        // Log de débogage uniquement
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Email Digest] Constructeur initialisé - Type: ' . $this->get_type());
        }
        
        // ⚠️  CORRECTION : PLUS D'INSERTION SQL ICI
        // Le type de notification est créé par la migration (migrations/release_1_0_0.php)
        // et activé/désactivé par ext.php lors de l'activation/désactivation de l'extension
    }

    /**
     * Retourne le nom unique du type de notification
     * 
     * IMPORTANT : Ce nom DOIT être "notification.type.reaction_email_digest"
     * C'est le nom stocké en base de données et utilisé par phpBB
     * pour identifier ce type de notification.
     * 
     * Ce nom est utilisé dans :
     * - phpbb_notification_types (colonne notification_type_name)
     * - ext.php (méthodes enable_notifications/disable_notifications)
     * - migrations/release_1_0_0.php (création du type)
     * - cron/notification_task.php (référence au type pour les emails)
     * 
     * @return string Le nom canonique du type
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Indique qu'on propose uniquement la méthode email pour ce type
     * 
     * Ce type de notification n'apparaît PAS dans la cloche (notification board).
     * Il est uniquement disponible en tant que notification par email.
     * 
     * @return array Liste des méthodes de notification disponibles
     */
    public function get_notification_methods()
    {
        return array('email');
    }

    /**
     * Clé de langue affichée dans l'UCP (titre)
     * 
     * Cette clé est définie dans :
     * - language/fr/notification/notification.type.reaction_email_digest.php
     * - language/en/notification/notification.type.reaction_email_digest.php
     * 
     * @return string La clé de langue pour le titre
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Description dans l'UCP
     * 
     * Cette clé est définie dans :
     * - language/fr/notification/notification.type.reaction_email_digest.php
     * - language/en/notification/notification.type.reaction_email_digest.php
     * 
     * @return string La clé de langue pour la description
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Ce type ne crée pas de notifications individuelles
     * 
     * Le cron (cron/notification_task.php) s'occupe de :
     * 1. Agréger les réactions sur une période
     * 2. Créer UN email groupé avec toutes les réactions
     * 3. Envoyer cet email aux utilisateurs concernés
     * 
     * Donc find_users_for_notification retourne toujours un tableau vide.
     * 
     * @param array $data    Données de notification
     * @param array $options Options supplémentaires
     * @return array Toujours vide (pas de création individuelle)
     */
    public function find_users_for_notification($data, $options = array())
    {
        return array();
    }

    /**
     * Ce type ne crée pas d'entrées dans phpbb_notifications
     * 
     * Les notifications sont gérées directement par le cron,
     * pas par le système standard de notifications phpBB.
     * 
     * @param array $data           Données de notification
     * @param array $pre_create_data Données pré-calculées
     * @return array Toujours vide (pas d'insertion)
     */
    public function create_insert_array($data, $pre_create_data = array())
    {
        return array();
    }

    /**
     * Pas de titre (pas d'affichage dans la cloche)
     * 
     * @return string Chaîne vide
     */
    public function get_title()
    {
        return '';
    }

    /**
