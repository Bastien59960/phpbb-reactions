<?php
/**
 * Type de notification pour les réactions
 * 
 * Ce fichier définit le type de notification utilisé pour notifier les utilisateurs
 * quand quelqu'un réagit à leurs messages. Il gère :
 * - Le format des notifications par cloche
 * - Le format des emails
 * - La détermination des utilisateurs à notifier
 * - Les données nécessaires pour les notifications
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
namespace bastien59960\reactions\notification\type;

/**
 * Type de notification pour les réactions
 * 
 * Gère les notifications envoyées aux auteurs de messages quand quelqu'un réagit.
 * Supporte les notifications par cloche et par email.
 */
class reaction extends \phpbb\notification\type\base
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var \phpbb\user_loader Chargeur d'utilisateurs */
    protected $user_loader;
    
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données */
    protected $db;
    
    /** @var string Chemin racine du forum */
    protected $phpbb_root_path;
    
    /** @var \phpbb\user Utilisateur actuel */
    protected $user;

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur du type de notification
     * 
     * Initialise les services nécessaires pour gérer les notifications de réactions.
     * 
     * @param \phpbb\user_loader $user_loader Chargeur d'utilisateurs
     * @param \phpbb\db\driver\driver_interface $db Connexion base de données
     * @param string $phpbb_root_path Chemin racine du forum
     * @param \phpbb\user $user Utilisateur actuel
     */
    public function __construct(\phpbb\user_loader $user_loader, \phpbb\db\driver\driver_interface $db, $phpbb_root_path, \phpbb\user $user)
    {
        $this->user_loader = $user_loader;
        $this->db = $db;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->user = $user;
    }
    
    // =============================================================================
    // CONFIGURATION DU TYPE DE NOTIFICATION
    // =============================================================================
    
    /**
     * Nom du type de notification
     * 
     * Ce nom doit correspondre au service défini dans services.yml
     * et être utilisé dans les appels au notification_manager.
     * 
     * @var string Nom du type de notification
     */
    public static $notification_type = 'bastien59960.reactions.notification';

    // =============================================================================
    // MÉTHODES REQUISES PAR LE SYSTÈME DE NOTIFICATIONS
    // =============================================================================
    
    /**
     * Récupérer le type de notification
     * 
     * Cette méthode est requise par le système de notifications phpBB.
     * Elle retourne le nom du type de notification.
     * 
     * @return string Nom du type de notification
     */
    public function get_type()
    {
        return self::$notification_type;
    }

    /**
     * Déterminer les utilisateurs à notifier
     * 
     * Cette méthode est appelée par le système de notifications pour déterminer
     * quels utilisateurs doivent recevoir la notification.
     * Pour les réactions, on notifie uniquement l'auteur du message.
     * 
     * @param array $data Données de la notification
     * @param array $options Options supplémentaires
     * @return array Liste des IDs des utilisateurs à notifier
     */
    public function find_users_for_notification($data, $options = [])
    {
        // On notifie uniquement l'auteur du message
        return [$data['post_author']];
    }
    
    // =============================================================================
    // MÉTHODES DE FORMATAGE DES NOTIFICATIONS
    // =============================================================================
    
    /**
     * Récupérer les variables pour le template d'email
     * 
     * Cette méthode est appelée pour générer les variables utilisées
     * dans le template d'email des notifications.
     * 
     * @return array Variables pour le template d'email
     */
    public function get_email_template_variables()
    {
        $reacter_names = $this->get_reacter_names($this->notification_data['reacter_ids']);
        $post_title = $this->get_post_title($this->notification_data['post_id']);

        return [
            'TITLE' => $this->user->lang('REACTIONS_NOTIFICATION_EMAIL_SUBJECT', $reacter_names, $post_title),
            'REACTOR_NAMES' => $reacter_names,
            'POST_TITLE' => $post_title,
            'U_POST_LINK' => $this->get_url(),
        ];
    }

    /**
     * Récupérer le titre de la notification
     * 
     * Cette méthode génère le titre affiché dans les notifications par cloche.
     * Elle utilise le système de pluriels de phpBB pour gérer les cas singulier/pluriel.
     * 
     * @return string Titre de la notification
     */
    public function get_title()
    {
        $count = (int) $this->notification_data['reaction_count'];
        $reacter_names = $this->get_reacter_names($this->notification_data['reacter_ids']);
        
        // Utilise le système de pluriels de phpBB
        return $this->user->lang('REACTIONS_NOTIFICATION_TITLE', $reacter_names, $count);
    }

    /**
     * Récupérer l'URL de la notification
     * 
     * Cette méthode génère l'URL vers le message concerné par la notification.
     * L'URL inclut un ancrage vers le message spécifique.
     * 
     * @return string URL vers le message
     */
    public function get_url()
    {
        return append_sid($this->phpbb_root_path . 'viewtopic.php', 'p=' . $this->notification_data['post_id'] . '#p' . $this->notification_data['post_id']);
    }

    /**
     * Fonction pour créer le message.
     */
    public static function get_item_id($data)
    {
        return (int) $data['post_id'];
    }

    /**
     * Récupérer l'ID de l'élément parent (statique)
     * 
     * Cette méthode statique est requise par le système de notifications phpBB.
     * Elle retourne l'ID de l'auteur du message à partir des données fournies.
     * 
     * @param array $data Données de la notification
     * @return int ID de l'auteur du message
     */
    public static function get_item_parent_id($data)
    {
        return (int) $data['post_author'];
    }

    /**
     * Récupérer l'ID du forum
     * 
     * Cette méthode retourne l'ID du forum contenant le message concerné.
     * Elle est utilisée pour les autorisations et le filtrage des notifications.
     * 
     * @return int ID du forum
     */
    public function get_forum_id()
    {
        $sql = 'SELECT forum_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $this->notification_data['post_id'];
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? (int) $row['forum_id'] : 0;
    }

    /**
     * Récupérer l'ID du sujet
     * 
     * Cette méthode retourne l'ID du sujet contenant le message concerné.
     * Elle est utilisée pour les autorisations et le filtrage des notifications.
     * 
     * @return int ID du sujet
     */
    public function get_topic_id()
    {
        $sql = 'SELECT topic_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $this->notification_data['post_id'];
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? (int) $row['topic_id'] : 0;
    }
    
    // =============================================================================
    // MÉTHODES UTILITAIRES
    // =============================================================================
    
    /**
     * Récupérer le titre d'un message
     * 
     * Cette méthode récupère le titre (sujet) d'un message depuis la base de données.
     * Elle est utilisée pour afficher le titre du message dans les notifications.
     * 
     * @param int $post_id ID du message
     * @return string Titre du message ou chaîne vide si non trouvé
     */
    private function get_post_title($post_id)
    {
        $sql = 'SELECT post_subject FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? $row['post_subject'] : '';
    }
    
    /**
     * Récupérer les noms des utilisateurs ayant réagi
     * 
     * Cette méthode génère une chaîne de caractères avec les noms des utilisateurs
     * qui ont réagi au message. Elle limite l'affichage à 3 noms maximum
     * et ajoute "et X autres" si nécessaire.
     * 
     * @param array $user_ids Liste des IDs des utilisateurs ayant réagi
     * @return string Chaîne formatée avec les noms des utilisateurs
     */
    private function get_reacter_names($user_ids)
    {
        // Limiter à 3 noms pour ne pas surcharger la notification
        $user_ids = array_slice($user_ids, 0, 3);
        $this->user_loader->load_users($user_ids);
        
        $names = [];
        foreach ($user_ids as $user_id) {
            $names[] = $this->user_loader->get_username($user_id, 'username');
        }
        
        // Calculer le nombre d'utilisateurs supplémentaires
        $others = count($this->notification_data['reacter_ids']) - count($names);
        if ($others > 0) {
            return $this->user->lang('REACTIONS_NOTIFICATION_AND_OTHERS', implode(', ', $names), $others);
        }
        
        return implode(', ', $names);
    }
}
