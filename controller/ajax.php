<?php
/**
 * Contrôleur AJAX pour l'extension Reactions
 * 
 * Ce contrôleur gère toutes les requêtes AJAX liées aux réactions :
 * - Ajouter/supprimer des réactions
 * - Récupérer les réactions d'un message
 * - Récupérer les utilisateurs ayant réagi avec un emoji spécifique
 * - Déclencher les notifications immédiates (cloche)
 * 
 * Actions supportées :
 * - 'add' : Ajouter une réaction
 * - 'remove' : Supprimer une réaction
 * - 'get' : Récupérer toutes les réactions d'un message
 * - 'get_users' : Récupérer les utilisateurs ayant réagi avec un emoji
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Définir la constante ANONYMOUS si elle n'est pas définie
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}

/**
 * Contrôleur AJAX pour les réactions
 * 
 * Gère les interactions AJAX avec les réactions aux messages.
 * Inclut la validation, les autorisations, les limites et les notifications.
 */
class ajax
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données */
    protected $db;
    
    /** @var \phpbb\user Utilisateur actuel */
    protected $user;
    
    /** @var \phpbb\request\request Gestionnaire de requêtes HTTP */
    protected $request;
    
    /** @var \phpbb\auth\auth Gestionnaire d'autorisations */
    protected $auth;
    
    /** @var \phpbb\language\language Gestionnaire de langues */
    protected $language;
    
    /** @var string Nom de la table des réactions */
    protected $post_reactions_table;
    
    /** @var string Nom de la table des messages */
    protected $posts_table;
    
    /** @var string Nom de la table des sujets */
    protected $topics_table; 
    
    /** @var string Nom de la table des forums */
    protected $forums_table;
    
    /** @var string Chemin racine du forum */
    protected $root_path; 
    
    /** @var string Extension des fichiers PHP */
    protected $php_ext;
    
    /** @var \phpbb\config\config Configuration du forum */
    protected $config;
    
    /** @var \phpbb\notification\manager Gestionnaire de notifications */
    protected $notification_manager;

    /**
     * Liste des 10 emojis courantes utilisées par défaut
     * 
     * Ces emojis sont affichés en priorité dans l'interface utilisateur.
     * Ils doivent être synchronisés avec reactions.js et listener.php.
     * 
     * @var array Liste des emojis courantes
     */
    protected $common_emojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur du contrôleur AJAX
     * 
     * Initialise tous les services nécessaires pour gérer les réactions.
     * Configure la connexion base de données en UTF8MB4 pour supporter les emojis.
     * 
     * @param \phpbb\db\driver\driver_interface $db Connexion base de données
     * @param \phpbb\user $user Utilisateur actuel
     * @param \phpbb\request\request $request Gestionnaire de requêtes
     * @param \phpbb\auth\auth $auth Gestionnaire d'autorisations
     * @param \phpbb\language\language $language Gestionnaire de langues
     * @param string $post_reactions_table Nom de la table des réactions
     * @param string $posts_table Nom de la table des messages
     * @param string $topics_table Nom de la table des sujets
     * @param string $forums_table Nom de la table des forums
     * @param string $root_path Chemin racine du forum
     * @param string $php_ext Extension des fichiers PHP
     * @param \phpbb\config\config $config Configuration du forum
     * @param \phpbb\notification\manager $notification_manager Gestionnaire de notifications
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\auth\auth $auth,
        \phpbb\language\language $language,
        $post_reactions_table,
        $posts_table,
        $topics_table,
        $forums_table,
        $root_path,
        $php_ext,
        \phpbb\config\config $config,
        \phpbb\notification\manager $notification_manager
    ) {
        // Initialisation des propriétés
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->auth = $auth;
        $this->language = $language;
        $this->post_reactions_table = $post_reactions_table;
        $this->posts_table = $posts_table;
        $this->topics_table = $topics_table;
        $this->forums_table = $forums_table;
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
        $this->config = $config;
        $this->notification_manager = $notification_manager;
        
        // Charger les fichiers de langue de l'extension
        $this->language->add_lang('common', 'bastien59960/reactions');
        
        // Forcer la connexion en utf8mb4 pour supporter les emojis
        $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
    }

    // =============================================================================
    // MÉTHODE PRINCIPALE
    // =============================================================================
    
    /**
     * Point d'entrée principal pour toutes les requêtes AJAX
     * 
     * Cette méthode traite toutes les requêtes AJAX liées aux réactions.
     * Elle effectue les validations nécessaires et délègue l'action appropriée.
     * 
     * Actions supportées :
     * - 'add' : Ajouter une réaction à un message
     * - 'remove' : Supprimer une réaction d'un message
     * - 'get' : Récupérer toutes les réactions d'un message
     * - 'get_users' : Récupérer les utilisateurs ayant réagi avec un emoji
     * 
     * @return JsonResponse Réponse JSON avec le résultat de l'opération
     */
    public function handle()
    {
        // Génération d'un identifiant unique pour le debug et le chronométrage
        $rid = bin2hex(random_bytes(8));
        $t0 = microtime(true);

        try {
            // =====================================================================
            // 1. VÉRIFICATIONS PRÉLIMINAIRES
            // =====================================================================
            
            // Vérifier que l'utilisateur est connecté
            if ($this->user->data['user_id'] == ANONYMOUS) {
                throw new HttpException(403, 'User not logged in.');
            }

            // =====================================================================
            // 2. PARSING DE LA REQUÊTE JSON
            // =====================================================================
            
            // Lire les données JSON de la requête
            $raw = file_get_contents('php://input');

            try {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $jsonEx) {
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'json_decode',
                    'error'   => $jsonEx->getMessage(),
                    'rid'     => $rid,
                ], 400);
            }

            // =====================================================================
            // 3. EXTRACTION ET VALIDATION DES PARAMÈTRES
            // =====================================================================
            
            // Extraire les paramètres de la requête
            $sid     = $data['sid'] ?? '';           // Jeton de session CSRF
            $post_id = (int) ($data['post_id'] ?? 0); // ID du message
            $emoji   = $data['emoji'] ?? '';         // Emoji de la réaction
            $action  = $data['action'] ?? '';        // Action à effectuer

            // Vérifier le jeton CSRF pour la sécurité
            if ($sid !== $this->user->data['session_id']) {
                throw new HttpException(403, 'Jeton CSRF invalide.');
            }

            // Vérifier que l'action est valide
            if (!in_array($action, ['add', 'remove', 'get', 'get_users'], true)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Invalid action',
                    'rid'     => $rid,
                ], 400);
            }

            // =====================================================================
            // 4. VALIDATION DES DONNÉES
            // =====================================================================
            
            // Vérifier que le message existe et est valide
            if (!$post_id || !$this->is_valid_post($post_id)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => $this->language->lang('REACTION_INVALID_POST'),
                    'rid'     => $rid,
                ], 400);
            }

            // Vérifier l'emoji (sauf pour l'action 'get' qui n'en a pas besoin)
            if ($action !== 'get' && (!$emoji || !$this->is_valid_emoji($emoji))) {
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'validation',
                    'error'   => $this->language->lang('REACTION_INVALID_EMOJI'),
                    'rid'     => $rid,
                ], 400);
            }

            // =====================================================================
            // 5. VÉRIFICATION DES AUTORISATIONS
            // =====================================================================
            
            // Vérifier que l'utilisateur peut réagir à ce message
            if (!$this->can_react_to_post($post_id)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => $this->language->lang('REACTION_NOT_AUTHORIZED'),
                    'rid'     => $rid,
                ], 403);
            }

            // =====================================================================
            // 6. VÉRIFICATION DES LIMITES (pour l'action 'add')
            // =====================================================================
            
            $user_id = (int)$this->user->data['user_id'];

            if ($action === 'add') {
                // Récupérer les limites configurées
                $max_per_post = (int) ($this->config['bastien59960_reactions_max_per_post'] ?? 20);
                $max_per_user = (int) ($this->config['bastien59960_reactions_max_per_user'] ?? 10);
                
                // Compter les types de réactions actuels sur ce message
                $sql = 'SELECT COUNT(DISTINCT reaction_emoji) as count FROM ' . $this->post_reactions_table . ' WHERE post_id = ' . $post_id;
                $result = $this->db->sql_query($sql);
                $current_types = (int) $this->db->sql_fetchfield('count');
                $this->db->sql_freeresult($result);
                
                // Compter les réactions de l'utilisateur sur ce message
                $sql = 'SELECT COUNT(*) as count FROM ' . $this->post_reactions_table . ' WHERE post_id = ' . $post_id . ' AND user_id = ' . $user_id;
                $result = $this->db->sql_query($sql);
                $user_reactions = (int) $this->db->sql_fetchfield('count');
                $this->db->sql_freeresult($result);
                
                // Vérifier les limites
                if ($current_types >= $max_per_post) {
                    return new JsonResponse(['success' => false, 'error' => 'REACTIONS_LIMIT_POST', 'rid' => $rid], 400);
                }
                if ($user_reactions >= $max_per_user) {
                    return new JsonResponse(['success' => false, 'error' => 'REACTIONS_LIMIT_USER', 'rid' => $rid], 400);
                }
            }

            // =====================================================================
            // 7. EXÉCUTION DE L'ACTION
            // =====================================================================
            
            switch ($action) {
                case 'add':
                    $resp = $this->add_reaction($post_id, $emoji);
                    break;
            
                case 'remove':
                    $resp = $this->remove_reaction($post_id, $emoji);
                    break;
            
                case 'get':
                    $resp = $this->get_reactions($post_id);
                    break;
            
                case 'get_users':
                    $resp = $this->get_users_for_emoji($post_id, $emoji);
                    break;
                    
                default:
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Invalid action',
                        'rid' => $rid
                    ], 400);
            }

            // =====================================================================
            // 8. FINALISATION DE LA RÉPONSE
            // =====================================================================
            
            // Ajouter l'ID de requête dans la réponse pour le debug
            if ($resp instanceof \Symfony\Component\HttpFoundation\JsonResponse) {
                $payload = json_decode($resp->getContent(), true);
                if (is_array($payload)) {
                    $payload['rid'] = $rid;
                    $resp->setData($payload);
                }
            }

            return $resp;

        } catch (\phpbb\exception\http_exception $httpEx) {
            // Erreurs HTTP contrôlées (403, 400, etc.)
            error_log("[Reactions RID=$rid] HttpException: " . $httpEx->getMessage());
            return new JsonResponse([
                'success' => false,
                'error'   => $httpEx->getMessage(),
                'rid'     => $rid,
            ], $httpEx->get_status_code());

        } catch (\Throwable $e) {
            // Erreurs fatales non contrôlées
            error_log("[Reactions RID=$rid] Exception attrapée: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return new JsonResponse([
                'success' => false,
                'error'   => 'Erreur serveur',
                'rid'     => $rid,
            ], 500);

        } finally {
            // Chronométrage de la requête pour le debug
            $elapsed = round((microtime(true) - $t0) * 1000);
            error_log("[Reactions RID=$rid] handle() terminé en {$elapsed}ms");
        }
    }

    // =============================================================================
    // MÉTHODES D'ACTIONS
    // =============================================================================
    
    /**
     * Ajouter une réaction à un message
     * 
     * Cette méthode ajoute une nouvelle réaction à un message spécifique.
     * Elle vérifie les doublons, récupère les informations nécessaires,
     * insère la réaction en base et déclenche les notifications.
     * 
     * @param int $post_id ID du message auquel ajouter la réaction
     * @param string $emoji Emoji de la réaction à ajouter
     * @return JsonResponse Réponse JSON avec le résultat de l'opération
     */
    private function add_reaction($post_id, $emoji)
    {
        $rid = bin2hex(random_bytes(8));
        $t0 = microtime(true);
        error_log("[Reactions RID=$rid] add_reaction enter post_id=$post_id emoji=$emoji user_id=" . (int)$this->user->data['user_id']);

        try {
            $post_id = (int) $post_id;
            $user_id = (int) $this->user->data['user_id'];

            // Topic lookup
            $sql = 'SELECT topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . $post_id;
            error_log("[Reactions RID=$rid] topic lookup SQL: $sql");
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            error_log("[Reactions RID=$rid] topic lookup fetched=" . json_encode($row));

            if (!$row || !isset($row['topic_id'])) {
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'topic_lookup',
                    'error'   => $this->language->lang('REACTION_INVALID_POST'),
                    'rid'     => $rid,
                ], 400);
            }
            $topic_id = (int) $row['topic_id'];

            // Duplicate check
            $dupSql = 'SELECT reaction_id FROM ' . $this->post_reactions_table . '
                            WHERE post_id = ' . $post_id . '
                              AND user_id = ' . $user_id . "
                              AND reaction_emoji = '" . $this->db->sql_escape($emoji) . "'";
            error_log("[Reactions RID=$rid] duplicate SQL: $dupSql");
            $result = $this->db->sql_query($dupSql);
            $already = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            error_log("[Reactions RID=$rid] duplicate found=" . json_encode((bool)$already));

            if ($already) {
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'duplicate',
                    'error'   => $this->language->lang('REACTION_ALREADY_ADDED'),
                    'rid'     => $rid,
                ], 400);
            }

            // =========================================================================
            // CORRECTION : Suppression de la vérification de longueur redondante et trop stricte.
            // La validation est déjà faite correctement par is_valid_emoji() dans handle().
            // =========================================================================

            // Build insert
            $sql_ary = [
                'post_id'        => $post_id,
                'topic_id'       => $topic_id,
                'user_id'        => $user_id,
                'reaction_emoji' => $emoji,
                'reaction_time'  => time(),
            ];
            error_log("[Reactions RID=$rid] sql_ary=" . json_encode($sql_ary, JSON_UNESCAPED_UNICODE));

            // Vérification du charset de la connexion avant l'INSERT
            $res = $this->db->sql_query("SHOW VARIABLES LIKE 'character_set_connection'");
            $row = $this->db->sql_fetchrow($res);
            $this->db->sql_freeresult($res);
            if ($row) {
                error_log("[Reactions RID=$rid] Connexion charset=" . $row['Value']);
            }

            try {
                $insertSql = 'INSERT INTO ' . $this->post_reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
                error_log("[Reactions RID=$rid] insert SQL: $insertSql");
            } catch (\Throwable $buildEx) {
                error_log("[Reactions RID=$rid] sql_build_array error: " . $buildEx->getMessage());
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'sql_build',
                    'error'   => $buildEx->getMessage(),
                    'rid'     => $rid,
                ], 500);
            }

            try {
                $this->db->sql_query($insertSql);
            } catch (\Throwable $dbEx) {
                $err = $this->db->sql_error();
                error_log("[Reactions RID=$rid] sql_query error: " . $dbEx->getMessage() . " db=" . json_encode($err));
                return new JsonResponse([
                    'success'  => false,
                    'stage'    => 'sql_insert',
                    'error'    => $dbEx->getMessage(),
                    'db_error' => $err,
                    'rid'      => $rid,
                ], 500);
            }

            $elapsed = round((microtime(true) - $t0) * 1000);
            error_log("[Reactions RID=$rid] add_reaction OK in {$elapsed}ms");
            
            // Récupère les réactions mises à jour
            $reactions = $this->get_reactions_array($post_id);
            $count = isset($reactions[$emoji]) ? $reactions[$emoji] : 1;

            // Déclencher immédiatement la notification par cloche
            $this->trigger_immediate_notification($post_id, $user_id, $emoji);

            // Retourne une réponse JSON valide
            return new JsonResponse([
                'success'      => true,
                'post_id'      => $post_id,
                'emoji'        => $emoji,
                'user_id'      => $user_id,
                'count'        => $count,
                'user_reacted' => true,
                'reactions'    => $reactions,
                'rid'          => $rid,
            ]);

        } catch (\Throwable $e) {
            error_log("[Reactions RID=$rid] add_reaction fatal: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'stage'   => 'fatal',
                'error'   => $e->getMessage(),
                'rid'     => $rid,
            ], 500);
        }
    }

    /**
     * Remove a reaction - Version corrigée
     */
    private function remove_reaction($post_id, $emoji)
    {
        $rid = bin2hex(random_bytes(8));
        error_log("[Reactions RID=$rid] remove_reaction enter post_id=$post_id emoji=$emoji");

        $sql = 'DELETE FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                  AND user_id = ' . (int) $this->user->data['user_id'] . "
                  AND reaction_emoji = '" . $this->db->sql_escape($emoji) . "'";
        
        error_log("[Reactions RID=$rid] delete SQL: $sql");
        $this->db->sql_query($sql);

        // Récupérer les réactions mises à jour
        $reactions = $this->get_reactions_array($post_id);
        $count = isset($reactions[$emoji]) ? $reactions[$emoji] : 0;

        return new JsonResponse([
            'success'      => true,
            'post_id'      => $post_id,
            'emoji'        => $emoji,
            'user_id'      => (int) $this->user->data['user_id'],
            'count'        => $count,
            'user_reacted' => false,
            'reactions'    => $reactions,
            'rid'          => $rid,
        ]);
    }

    /**
     * Get all reactions for a post
     */
    private function get_reactions($post_id)
    {
        $reactions = $this->get_reactions_array($post_id);
        
        return new JsonResponse([
            'success'   => true,
            'post_id'   => $post_id,
            'reactions' => $reactions,
        ]);
    }

    /**
    * Get users who reacted with a specific emoji
    */
    private function get_users_for_emoji($post_id, $emoji)
    {
        $sql = 'SELECT DISTINCT u.user_id, u.username, u.username_clean
                FROM ' . $this->post_reactions_table . ' r
                LEFT JOIN ' . USERS_TABLE . ' u ON r.user_id = u.user_id
                WHERE r.post_id = ' . (int) $post_id . "
                  AND r.reaction_emoji = '" . $this->db->sql_escape($emoji) . "'
                ORDER BY r.reaction_time ASC";
        
        $result = $this->db->sql_query($sql);
        
        $users = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $users[] = [
                'user_id' => (int) $row['user_id'],
                'username' => $row['username'],
                'username_clean' => $row['username_clean']
            ];
        }
        $this->db->sql_freeresult($result);
        
        return new JsonResponse([
            'success' => true,
            'post_id' => $post_id,
            'emoji' => $emoji,
            'users' => $users
        ]);
    }

    /**
     * Récupère les réactions sous forme d'array
     */
    private function get_reactions_array($post_id)
    {
        $sql = 'SELECT reaction_emoji, COUNT(*) AS count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                GROUP BY reaction_emoji';
        $result = $this->db->sql_query($sql);

        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[$row['reaction_emoji']] = (int) $row['count'];
        }
        $this->db->sql_freeresult($result);

        return $reactions;
    }

    // =============================================================================
    // MÉTHODES DE VALIDATION
    // =============================================================================
    
    /**
     * Vérifier si un message existe et est valide
     * 
     * Cette méthode vérifie qu'un message existe dans la base de données.
     * Elle est utilisée pour valider les requêtes avant de traiter les réactions.
     * 
     * @param int $post_id ID du message à vérifier
     * @return bool True si le message existe, False sinon
     */
    private function is_valid_post($post_id)
    {
        $sql = 'SELECT post_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (bool) $exists;
    }

    /**
     * Valider un emoji
     * 
     * Cette méthode valide qu'un emoji est valide et sécurisé.
     * Elle supporte les emojis courantes, les emojis composés avec ZWJ
     * et effectue des vérifications de sécurité.
     * 
     * @param string $emoji Emoji à valider
     * @return bool True si l'emoji est valide, False sinon
     */
    private function is_valid_emoji($emoji)
    {
        // Vérifier d'abord les emojis courantes (plus rapide)
        if (in_array($emoji, $this->common_emojis, true)) {
            return true;
        }
        
        // Vérifier que l'emoji n'est pas vide
        if (empty($emoji)) {
            return false;
        }
        
        // Limite de longueur pour les emojis composés (ZWJ)
        // Les emojis avec ZWJ peuvent faire jusqu'à 40-50 octets
        if (strlen($emoji) > 50) {
            return false;
        }
        
        // Vérifier la longueur Unicode (1 à 15 caractères Unicode)
        $mb_length = mb_strlen($emoji, 'UTF-8');
        if ($mb_length === 0 || $mb_length > 15) {
            return false;
        }
        
        // Vérifier qu'il n'y a pas de caractères de contrôle dangereux
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $emoji)) {
            return false;
        }
        
        return true;
    }

    /**
     * Vérifier si l'utilisateur peut réagir à un message
     * 
     * Cette méthode vérifie les autorisations pour réagir à un message.
     * Elle vérifie que le sujet et le forum ne sont pas verrouillés.
     * 
     * @param int $post_id ID du message à vérifier
     * @return bool True si l'utilisateur peut réagir, False sinon
     */
    private function can_react_to_post($post_id)
    {
        // Vérifier que l'utilisateur est connecté
        if ($this->user->data['user_id'] == ANONYMOUS) {
            return false;
        }

        // Récupérer les informations du message, sujet et forum
        $sql = 'SELECT p.post_id, p.forum_id, p.poster_id, t.topic_status, f.forum_status
                FROM ' . $this->posts_table . ' p
                JOIN ' . $this->topics_table . ' t ON p.topic_id = t.topic_id
                JOIN ' . $this->forums_table . ' f ON p.forum_id = f.forum_id
                WHERE p.post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $post_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        // Vérifier que le message existe
        if (!$post_data) {
            return false;
        }
        
        // Vérifier que le sujet et le forum ne sont pas verrouillés
        if ($post_data['topic_status'] == ITEM_LOCKED || $post_data['forum_status'] == ITEM_LOCKED) {
            return false;
        }
        
        return true;
    }

    // =============================================================================
    // MÉTHODES UTILITAIRES
    // =============================================================================
    
    /**
     * Récupérer la liste des emojis courantes
     * 
     * Cette méthode retourne la liste des emojis courantes utilisées
     * par défaut dans l'interface utilisateur.
     * 
     * @return array Liste des emojis courantes
     */
    public function get_common_emojis()
    {
        return $this->common_emojis;
    }

    /**
     * Déclencher immédiatement une notification par cloche
     * 
     * Cette méthode déclenche une notification immédiate (sans délai anti-spam)
     * pour l'auteur du message quand quelqu'un réagit.
     * Les notifications par email sont gérées séparément par le cron.
     * 
     * @param int $post_id ID du message
     * @param int $reacter_id ID de l'utilisateur qui a réagi
     * @param string $emoji Emoji de la réaction
     * @return void
     */
    private function trigger_immediate_notification($post_id, $reacter_id, $emoji)
    {
        try {
            // Récupérer l'auteur du post
            $sql = 'SELECT poster_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
            $result = $this->db->sql_query($sql);
            $post_data = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$post_data || !$post_data['poster_id']) {
                return;
            }

            $post_author_id = (int) $post_data['poster_id'];

            // Ne pas notifier l'utilisateur qui a réagi
            if ($post_author_id === $reacter_id) {
                return;
            }

            // Récupérer les données des réactions pour ce post
            $sql = 'SELECT user_id FROM ' . $this->post_reactions_table . ' 
                    WHERE post_id = ' . (int) $post_id . ' 
                    ORDER BY reaction_time ASC';
            $result = $this->db->sql_query($sql);
            
            $reacter_ids = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $reacter_ids[] = (int) $row['user_id'];
            }
            $this->db->sql_freeresult($result);

            // Compter le nombre total de réactions pour ce post
            $sql = 'SELECT COUNT(*) as total FROM ' . $this->post_reactions_table . ' WHERE post_id = ' . (int) $post_id;
            $result = $this->db->sql_query($sql);
            $reaction_count = (int) $this->db->sql_fetchfield('total');
            $this->db->sql_freeresult($result);

            // Préparer les données de notification
            $notification_data = [
                'post_id' => $post_id,
                'post_author' => $post_author_id,
                'reacter_ids' => $reacter_ids,
                'reaction_count' => $reaction_count,
                'emoji' => $emoji,
            ];

            // Déclencher la notification immédiatement (cloche)
            $this->notification_manager->add_notifications('bastien59960.reactions.notification', $notification_data);

        } catch (\Exception $e) {
            error_log('[Reactions] Erreur lors de la notification immédiate: ' . $e->getMessage());
        }
    }

}