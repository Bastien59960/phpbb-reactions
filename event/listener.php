<?php
/**
 * Listener d'événements pour l'extension Reactions
 * 
 * Ce listener gère l'affichage des réactions sur les pages du forum.
 * Il écoute les événements phpBB pour :
 * - Ajouter les CSS/JS nécessaires aux pages
 * - Charger les réactions existantes pour chaque message
 * - Configurer les données pour les templates
 * - Gérer l'affichage des réactions avec les utilisateurs
 * 
 * Événements écoutés :
 * - core.page_header : Ajoute les assets CSS/JS
 * - core.viewtopic_cache_user_data : Charge les données utilisateur
 * - core.viewtopic_post_row_after : Affiche les réactions pour chaque message
 * - core.viewforum_modify_topicrow : Ajoute les données du forum
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\db\driver\driver_interface;

/**
 * Listener d'événements pour les réactions
 * 
 * Gère l'affichage et la configuration des réactions sur les pages du forum.
 */
class listener implements EventSubscriberInterface
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var driver_interface Connexion à la base de données */
    protected $db;

    /** @var \phpbb\user Utilisateur actuel */
    protected $user;

    /** @var string Nom de la table des réactions */
    protected $post_reactions_table;

    /** @var string Nom de la table des messages */
    protected $posts_table;

    /** @var \phpbb\template\template Moteur de templates */
    protected $template;

    /** @var \phpbb\language\language Gestionnaire de langues */
    protected $language;

    /** @var \phpbb\controller\helper Helper pour les URLs */
    protected $helper;

    /** @var \phpbb\config\config Configuration du forum */
    protected $config;

    /**
     * Liste des 10 emojis courantes utilisées par défaut
     * 
     * Ces emojis sont affichés en priorité dans l'interface utilisateur.
     * Ils doivent être synchronisés avec reactions.js et ajax.php.
     * 
     * @var array Liste des emojis courantes
     */
    protected $common_emojis = [
        '👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'
    ];

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur du listener d'événements
     * 
     * Initialise tous les services nécessaires pour gérer l'affichage des réactions.
     * Configure la connexion base de données en UTF8MB4 pour supporter les emojis.
     * 
     * @param driver_interface $db Connexion base de données
     * @param \phpbb\user $user Utilisateur actuel
     * @param string $post_reactions_table Nom de la table des réactions
     * @param string $posts_table Nom de la table des messages
     * @param \phpbb\template\template $template Moteur de templates
     * @param \phpbb\language\language $language Gestionnaire de langues
     * @param \phpbb\controller\helper $helper Helper pour les URLs
     * @param \phpbb\config\config $config Configuration du forum
     */
    public function __construct(
        driver_interface $db,
        \phpbb\user $user,
        $post_reactions_table,
        $posts_table,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        \phpbb\controller\helper $helper,
        \phpbb\config\config $config  
    ) {
        // Initialisation des propriétés
        $this->db = $db;
        $this->user = $user;
        $this->post_reactions_table = $post_reactions_table;
        $this->posts_table = $posts_table;
        $this->template = $template;
        $this->language = $language;
        $this->helper = $helper;
        $this->config = $config;
        
        // Configurer la connexion en utf8mb4 pour supporter les emojis
        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
        } catch (\Exception $e) {
            error_log('[phpBB Reactions] Could not set names: ' . $e->getMessage());
        }
    }

    // =============================================================================
    // CONFIGURATION DES ÉVÉNEMENTS
    // =============================================================================
    
    /**
     * Définir les événements écoutés par ce listener
     * 
     * Cette méthode indique à phpBB quels événements ce listener doit écouter
     * et quelle méthode appeler pour chaque événement.
     * 
     * @return array Tableau associatif événement => méthode
     */
    public static function getSubscribedEvents()
    {
        return [
            'core.page_header'               => 'add_assets_to_page',      // Ajouter CSS/JS
            'core.viewtopic_cache_user_data' => 'load_language_and_data',  // Charger données utilisateur
            'core.viewtopic_post_row_after'  => 'display_reactions',       // Afficher réactions
            'core.viewforum_modify_topicrow' => 'add_forum_data',          // Ajouter données forum
        ];
    }

    // =============================================================================
    // MÉTHODES D'ÉVÉNEMENTS
    // =============================================================================
    
    /**
     * Ajouter les assets CSS/JS aux pages
     * 
     * Cette méthode est appelée sur l'événement 'core.page_header'.
     * Elle ajoute les fichiers CSS et JavaScript nécessaires pour les réactions,
     * ainsi que les variables JavaScript pour l'AJAX.
     * 
     * @param array $event Données de l'événement
     * @return void
     */
    public function add_assets_to_page($event)
    {
        // Charger les fichiers de langue de l'extension
        $this->language->add_lang('common', 'bastien59960/reactions');

        // Définir les chemins vers les assets
        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        // Générer l'URL AJAX pour les réactions
        try {
            $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);
        } catch (\Exception $e) {
            $ajax_url = append_sid('app.php/reactions/ajax');
        }

        // Assigner les variables au template
        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,                    // Indique que les réactions sont activées
            'REACTIONS_CSS_PATH'  => $css_path,               // Chemin vers le CSS
            'REACTIONS_JS_PATH'   => $js_path,                // Chemin vers le JS
            'U_REACTIONS_AJAX'    => $ajax_url,               // URL AJAX
            'S_SESSION_ID'        => isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '',
        ]);

        // Assigner les variables JavaScript pour l'AJAX
        $this->template->assign_var(
            'REACTIONS_AJAX_URL_JS',
            'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '"; window.REACTIONS_SID = "' . addslashes(isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '') . '";'
        );
    }

    /**
     * Charger les données de langue et utilisateur
     * 
     * Cette méthode est appelée sur l'événement 'core.viewtopic_cache_user_data'.
     * Actuellement utilisée comme placeholder pour de futures fonctionnalités.
     * 
     * @param array $event Données de l'événement
     * @return void
     */
    public function load_language_and_data($event)
    {
        // Placeholder pour de futures fonctionnalités
    }

    /**
     * Afficher les réactions pour un message
     * 
     * Cette méthode est appelée sur l'événement 'core.viewtopic_post_row_after'.
     * Elle récupère les réactions existantes pour un message et les affiche
     * avec les données des utilisateurs pour les tooltips.
     * 
     * @param array $event Données de l'événement contenant les informations du message
     * @return void
     */
    public function display_reactions($event)
    {
        $post_row = isset($event['post_row']) ? $event['post_row'] : [];
        $row      = isset($event['row']) ? $event['row'] : [];
        $post_id  = isset($row['post_id']) ? (int) $row['post_id'] : 0;

        if ($post_id <= 0) {
            $event['post_row'] = $post_row;
            return;
        }

        if (!$this->is_valid_post($post_id)) {
            error_log('[phpBB Reactions] display_reactions: post_id ' . $post_id . ' not found');
            $event['post_row'] = $post_row;
            return;
        }

        // Récupération des réactions depuis la DB
        $reactions_by_db = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, (int) $this->user->data['user_id']);
        $list_users_reaction = $this->get_list_users_reactions($post_id);

        // CORRECTION : Ne retourner que les réactions avec count > 0
        $visible_reactions = [];
        foreach ($reactions_by_db as $emoji => $count) {
            if ((int) $count > 0) {
                // Récupérer les utilisateurs qui ont réagi avec cet emoji
                $users_for_emoji = $this->get_users_by_reaction($post_id, $emoji);
                
                $visible_reactions[] = [
                    'EMOJI'        => $emoji,
                    'COUNT'        => (int) $count,
                    'USER_REACTED' => in_array($emoji, $user_reactions, true),
                    'USERS'        => $users_for_emoji,
                ];
            }
        }
		
		$visible_users = [];
		foreach ($list_users_reaction as $emoji => $nom)
		{
			$visible_users[] = [
				'EMOJI'		=> $emoji,
				'NOM'		=> $nom,
			];
		}
		
        // CORRECTION : Plus de fallback avec des emojis à count=0
        // Selon cahier des charges : "les émojis n'apparaissent que s'il y a des réactions"
		$post_row['S_REACTIONS_ENABLED'] = true;
		$post_row['post_reactions'] = [];
		$post_row['list_reactions'] = [];
		
		// Assigner les réactions via le système de blocs de template
		foreach ($visible_reactions as $reaction) {
		    $this->template->assign_block_vars('postrow.post_reactions', $reaction);
		}
		
		//~ error_log('[phpBB Reactions] post_reactions assignees pour post ' . $post_id . ': ' . count($visible_reactions) . ' reactions, structure: ' . print_r($post_row['post_reactions'], true));
		        //~ $event['post_row'] = $post_row;
		
		foreach ($visible_users as $users_name) {
		    $this->template->assign_block_vars('postrow.list_reactions', $users_name);
		}
		//~ error_log('[phpBB Reactions] list_reactions assignees pour post ' . $post_id . ': ' . count($visible_users) . ' reactions, structure: ' . print_r($post_row['list_reactions'], true));

        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'post_reactions'      => $visible_reactions, // Seules les vraies réactions
        ]);

        $event['post_row'] = $post_row;
    }

    public function add_forum_data($event)
    {
        // Placeholder
    }

    /**
     * CORRECTION : Amélioration du debug pour get_user_reactions
     */
    private function get_user_reactions($post_id, $user_id)
    {
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;

        if ($user_id === ANONYMOUS || $post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                  AND user_id = ' . $user_id;

        $result = $this->db->sql_query($sql);

        $user_reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['reaction_emoji'])) {
                $user_reactions[] = $row['reaction_emoji'];
            }
        }
        $this->db->sql_freeresult($result);

        $unique = array_values(array_unique($user_reactions));
        return $unique;
    }

    private function is_valid_post($post_id)
    {
        $sql = 'SELECT post_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return (bool) $exists;
    }

/**	ajout liste users **/
    private function get_list_users_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }
        
        $sql = 'SELECT r.reaction_emoji as emo, u.username_clean as nom FROM `phpbb_users` u INNER JOIN `phpbb_post_reactions` r ON u.user_id=r.user_id WHERE r.post_id=' . $post_id ;
        error_log("[Reactions Debug] get_list_users_reactions SQL: $sql");
        $result = $this->db->sql_query($sql);

        $reactions_users = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['emo'])) {
                $reactions_users[$row['emo']] = $row['nom'];
            }
        }
        $this->db->sql_freeresult($result);

        error_log('[Reactions Debug] Users trouves pour post_id=' . $post_id . ': ' . json_encode($reactions_users, JSON_UNESCAPED_UNICODE));
        return $reactions_users;
	}
	
    /**
     * CORRECTION : Amélioration du debug pour get_post_reactions
     */
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji, COUNT(*) AS reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                HAVING COUNT(*) > 0
                ORDER BY COUNT(*) DESC';

        $result = $this->db->sql_query($sql);

        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['reaction_emoji'])) {
                $reactions[$row['reaction_emoji']] = (int) $row['reaction_count'];
            }
        }
        $this->db->sql_freeresult($result);

        return $reactions;
    }

    /**
     * Comptage du nombre total de réactions pour un post (distinct emojis)
     */
    private function count_post_reactions($post_id)
    {
        $sql = 'SELECT COUNT(DISTINCT reaction_emoji) AS total_reactions
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? (int) $row['total_reactions'] : 0;
    }

    /**
     * Comptage des réactions d'un utilisateur pour un post
     */
    private function count_user_reactions($post_id, $user_id)
    {
        if ($user_id === ANONYMOUS) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS user_reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                  AND user_id = ' . (int) $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? (int) $row['user_reaction_count'] : 0;
    }

    /**
     * Récupère la liste des utilisateurs ayant réagi avec un emoji spécifique
     */
    private function get_users_by_reaction($post_id, $emoji)
    {
        $sql = 'SELECT u.user_id, u.username
                FROM ' . $this->post_reactions_table . ' pr
                JOIN ' . USERS_TABLE . ' u ON pr.user_id = u.user_id
                WHERE pr.post_id = ' . (int) $post_id . "
                  AND pr.reaction_emoji = '" . $this->db->sql_escape($emoji) . "'
                ORDER BY pr.reaction_time ASC";
        
        $result = $this->db->sql_query($sql);
        $users = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $users[] = [
                'user_id' => (int) $row['user_id'],
                'username' => $row['username']
            ];
        }
        $this->db->sql_freeresult($result);

        return $users;
    }

    /**
     * Vérifie les limites selon le cahier des charges
     */
    public function check_reaction_limits($post_id, $user_id)
    {
        // B.1 - Limite de types par post (défaut: 20)
        $max_types = 20; // À rendre configurable via ACP
        $current_types = $this->count_post_reactions($post_id);
        
        // B.2 - Limite par utilisateur/post (défaut: 10)  
        $max_user_reactions = 10; // À rendre configurable via ACP
        $current_user_reactions = $this->count_user_reactions($post_id, $user_id);
        
        return [
            'can_add_new_type' => $current_types < $max_types,
            'can_add_reaction' => $current_user_reactions < $max_user_reactions,
            'current_types' => $current_types,
            'max_types' => $max_types,
            'current_user_reactions' => $current_user_reactions,
            'max_user_reactions' => $max_user_reactions
        ];
    }

    public function is_valid_emoji($emoji)
    {
        if (!is_string($emoji) || $emoji === '') {
            return false;
        }

        if (in_array($emoji, $this->common_emojis, true)) {
            return true;
        }

        return (mb_strlen(trim($emoji)) > 0);
    }
}
