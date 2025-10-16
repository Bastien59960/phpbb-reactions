<?php
/**
 * Fichier : ajax.php
 * Chemin : bastien59960/reactions/controller/ajax.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est le cœur de l'interactivité de l'extension. Il reçoit et traite
 * toutes les requêtes AJAX envoyées par le client (reactions.js) pour ajouter ou
 * supprimer une réaction, et obtenir la liste des utilisateurs ayant réagi.
 * Il renvoie systématiquement une réponse au format JSON.
 *
 * Informations reçues (Payload JSON) :
 * - `post_id` : ID du message concerné
 * - `emoji` : L'emoji de la réaction (Unicode)
 * - `action` : L'action à effectuer ('add', 'remove', 'get_users')
 * - `sid` : Le jeton de session pour la protection CSRF
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

    /** @var \bastien59960\reactions\controller\helper Service d'aide pour générer le HTML */
    protected $reactions_helper;

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
     * @param \bastien59960\reactions\controller\helper $reactions_helper Helper pour le HTML
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
        \phpbb\notification\manager $notification_manager,
        \bastien59960\reactions\controller\helper $reactions_helper // Ajout du paramètre manquant
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
        $this->reactions_helper = $reactions_helper; // Ligne à restaurer
        
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse Réponse JSON.
     */
    public function handle()
    {
        // =========================================================================
        // CORRECTION CRITIQUE : Nettoyer TOUTE sortie parasite AVANT le JSON
        // =========================================================================
        
        // 1. Supprimer tous les buffers de sortie existants
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // 2. Démarrer un nouveau buffer propre
        ob_start();
        
        // 3. Forcer les headers JSON immédiatement
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-cache, must-revalidate');
        }
        
        // Génération d'un identifiant unique pour le debug et le chronométrage
        $rid = bin2hex(random_bytes(8));
        $t0 = microtime(true);

        try {
            // =====================================================================
            // 1. VÉRIFICATIONS PRÉLIMINAIRES
            // =====================================================================
            
            // Vérifier que l'utilisateur est connecté
            if ($this->user->data['user_id'] == ANONYMOUS) { // ANONYMOUS est une constante de phpBB
                throw new HttpException(403, 'User not logged in.');
            }

            // =====================================================================
            // 2. PARSING DE LA REQUÊTE JSON
            // =====================================================================
            
            // Récupérer le corps brut de la requête POST.
            $raw = file_get_contents('php://input');
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] raw payload (".strlen($raw)." bytes): " . $raw);
            }

            // Tentative de décodage JSON avec gestion d'erreur robuste
            try {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $jsonEx) {
                error_log("[Reactions RID=$rid] json_decode first attempt failed: " . $jsonEx->getMessage());

                // Tentative de correction UTF-8
                try {
                    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    } else {
                        $fixed = mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
                        $data = json_decode($fixed, true, 512, JSON_THROW_ON_ERROR);
                    }
                } catch (\Throwable $jsonEx2) {
                    error_log("[Reactions RID=$rid] json_decode second attempt failed: " . $jsonEx2->getMessage());

                    // Dernier recours : form-encoded
                    parse_str($raw, $parsed);
                    if (!empty($parsed)) {
                        $data = $parsed;
                        error_log("[Reactions RID=$rid] parsed form-encoded payload as fallback.");
                    } else {
                        // Nettoyer le buffer et retourner l'erreur JSON
                        ob_end_clean();
                        return new JsonResponse([
                            'success' => false,
                            'stage'   => 'json_decode',
                            'error'   => $jsonEx2->getMessage(),
                            'raw_len' => strlen($raw),
                            'rid'     => $rid,
                        ], 400);
                    }
                }
            }

            // =====================================================================
            // 3. EXTRACTION ET VALIDATION DES PARAMÈTRES
            // =====================================================================
            
            $sid      = $data['sid'] ?? '';
            $post_id  = (int) ($data['post_id'] ?? 0);
            $emoji    = $data['emoji'] ?? '';
            $action   = $data['action'] ?? '';
            $post_ids = [];

            // Vérification du jeton de session pour la protection CSRF.
            if ($sid !== $this->user->data['session_id']) {
                throw new HttpException(403, 'Jeton CSRF invalide.');
            }

            // Valider que l'action demandée est l'une des actions autorisées.
            if (!in_array($action, ['add', 'remove', 'get', 'get_users', 'sync'], true)) {
                ob_end_clean();
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Invalid action',
                    'rid'     => $rid,
                ], 400);
            }

            // =====================================================================
            // 4. VALIDATION DES DONNÉES
            // =====================================================================
            
            if ($action === 'sync') {
                $post_ids_raw = $data['post_ids'] ?? [];
                if (!is_array($post_ids_raw)) {
                    ob_end_clean();
                    return new JsonResponse([
                        'success' => false,
                        'error'   => 'Invalid payload: post_ids must be an array',
                        'rid'     => $rid,
                    ], 400);
                }

                $post_ids = array_values(array_unique(array_filter(
                    array_map('intval', $post_ids_raw),
                    static function ($id) {
                        return $id > 0;
                    }
                )));

                if (empty($post_ids)) {
                    ob_end_clean();
                    return new JsonResponse([
                        'success' => false,
                        'error'   => 'No valid post_ids provided',
                        'rid'     => $rid,
                    ], 400);
                }
            }

            // Vérifier que le post_id est valide et que le message existe.
            if ($action !== 'sync' && (!$post_id || !$this->is_valid_post($post_id))) {
                ob_end_clean();
                return new JsonResponse([
                    'success' => false,
                    'error'   => $this->language->lang('REACTION_INVALID_POST'),
                    'rid'     => $rid,
                ], 400);
            }

            // Vérifier que l'emoji est valide (sauf pour les actions qui n'en ont pas besoin).
            if (!in_array($action, ['get', 'sync'], true) && (!$emoji || !$this->is_valid_emoji($emoji))) {
                ob_end_clean();
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
            
            // Vérifier si l'utilisateur a le droit de réagir à ce message (forum non verrouillé, etc.).
            if (!$this->can_react_to_post($post_id)) {
                ob_end_clean();
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
                // Lire les limites depuis la configuration de l'ACP.
                $max_per_post = (int) ($this->config['bastien59960_reactions_max_per_post'] ?? 20);
                $max_per_user = (int) ($this->config['bastien59960_reactions_max_per_user'] ?? 10);
                
                // Compter le nombre de types de réactions uniques sur le message.
                $sql = 'SELECT COUNT(DISTINCT reaction_emoji) as count FROM ' . $this->post_reactions_table . ' WHERE post_id = ' . $post_id;
                $result = $this->db->sql_query($sql);
                $current_types = (int) $this->db->sql_fetchfield('count');
                $this->db->sql_freeresult($result);
                
                // Compter le nombre de réactions que l'utilisateur a déjà mises sur ce message.
                $sql = 'SELECT COUNT(reaction_id) as count FROM ' . $this->post_reactions_table . ' WHERE post_id = ' . $post_id . ' AND user_id = ' . $user_id;
                $result = $this->db->sql_query($sql);
                $user_reactions = (int) $this->db->sql_fetchfield('count');
                $this->db->sql_freeresult($result);
                
                if ($current_types >= $max_per_post) {
                    throw new HttpException(400, sprintf($this->language->lang('REACTIONS_LIMIT_POST'), $max_per_post));
                }
                if ($user_reactions >= $max_per_user) {
                    throw new HttpException(400, sprintf($this->language->lang('REACTIONS_LIMIT_USER'), $max_per_user));
                }

                error_log("[Reactions RID=$rid] CONFIG max_per_post={$max_per_post} max_per_user={$max_per_user}");
                error_log("[Reactions RID=$rid] current_types = {$current_types}, user_reactions = {$user_reactions}");
            }

            // =====================================================================
            // 7. EXÉCUTION DE L'ACTION
            // =====================================================================
            
            // Appeler la méthode correspondante en fonction de l'action demandée.
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

                case 'sync':
                    $resp = $this->sync_reactions($post_ids);
                    break;
                    
                default:
                    ob_end_clean();
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Invalid action',
                        'rid' => $rid
                    ], 400);
            }

            // =====================================================================
            // 8. FINALISATION DE LA RÉPONSE
            // =====================================================================
            
            // Ajouter l'identifiant de requête (RID) à la réponse pour le débogage.
            if ($resp instanceof \Symfony\Component\HttpFoundation\JsonResponse) {
                $payload = json_decode($resp->getContent(), true);
                if (is_array($payload)) {
                    $payload['rid'] = $rid;
                    $resp->setData($payload);
                }
            }

            // CRITIQUE : Nettoyer le buffer avant d'envoyer la réponse
            ob_end_clean();
            
            return $resp;

        } catch (\phpbb\exception\http_exception $httpEx) {
            // Gérer les exceptions HTTP (ex: 403 Forbidden).
            error_log("[Reactions RID=$rid] HttpException: " . $httpEx->getMessage());
            ob_end_clean();
            return new JsonResponse([
                'success' => false,
                'error'   => $httpEx->getMessage(),
                'rid'     => $rid,
            ], $httpEx->get_status_code());

        } catch (\Throwable $e) {
            // Gérer toutes les autres erreurs serveur (500).
            error_log("[Reactions RID=$rid] Exception attrapée: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            ob_end_clean();
            return new JsonResponse([
                'success' => false,
                'error'   => 'Erreur serveur',
                'rid'     => $rid,
            ], 500);

        } finally {
            // Enregistrer le temps d'exécution de la requête.
            $elapsed = round((microtime(true) - $t0) * 1000);
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] handle() terminé en {$elapsed}ms");
            }
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
        // Normalisation de l'emoji côté serveur
        $emoji = (string) $emoji;
        $emoji = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $emoji);

        if (extension_loaded('intl') && class_exists('\Normalizer')) {
            $emoji = \Normalizer::normalize($emoji, \Normalizer::FORM_C);
        }

        $rid = bin2hex(random_bytes(8));
        $t0 = microtime(true);
        if (defined('DEBUG') && DEBUG) {
            error_log("[Reactions RID=$rid] add_reaction enter post_id=$post_id emoji=$emoji user_id=" . (int)$this->user->data['user_id']);
        }

        try {
            $post_id = (int) $post_id;
            $user_id = (int) $this->user->data['user_id'];

            // Topic lookup
            $sql = 'SELECT topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . $post_id;
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] topic lookup SQL: $sql");
            }
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] topic lookup fetched=" . json_encode($row));
            }

            if (!$row || !isset($row['topic_id'])) {
                ob_end_clean();
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
              AND reaction_emoji COLLATE utf8mb4_bin = '" . $this->db->sql_escape($emoji) . "'";

            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] duplicate SQL: $dupSql");
            }
            $result = $this->db->sql_query($dupSql);
            $already = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] duplicate found=" . json_encode((bool)$already));
            }

            if ($already) {
                ob_end_clean();
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'duplicate',
                    'error'   => $this->language->lang('REACTION_ALREADY_ADDED'),
                    'rid'     => $rid,
                ], 400);
            }

            // Build insert
            $sql_ary = [
                'post_id'           => $post_id,
                'topic_id'          => $topic_id,
                'user_id'           => $user_id,
                'reaction_emoji'    => $emoji,
                'reaction_time'     => time(),
                'reaction_notified' => 0,
            ];
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] sql_ary=" . json_encode($sql_ary, JSON_UNESCAPED_UNICODE));
            }

            // Vérification du charset de la connexion
            $res = $this->db->sql_query("SHOW VARIABLES LIKE 'character_set_connection'");
            $row = $this->db->sql_fetchrow($res);
            $this->db->sql_freeresult($res);
            if ($row) {
                error_log("[Reactions RID=$rid] Connexion charset=" . $row['Value']);
            }

            try {
                $insertSql = 'INSERT INTO ' . $this->post_reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
                if (defined('DEBUG') && DEBUG) {
                    error_log("[Reactions RID=$rid] insert SQL: $insertSql");
                }
            } catch (\Throwable $buildEx) {
                error_log("[Reactions RID=$rid] sql_build_array error: " . $buildEx->getMessage());
                ob_end_clean();
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
                ob_end_clean();
                return new JsonResponse([
                    'success'  => false,
                    'stage'    => 'sql_insert',
                    'error'    => $dbEx->getMessage(),
                    'db_error' => $err,
                    'rid'      => $rid,
                ], 500);
            }

            $elapsed = round((microtime(true) - $t0) * 1000);
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] add_reaction OK in {$elapsed}ms");
            }
            
            // Récupère les réactions mises à jour
            $reactions = $this->get_reactions_array($post_id);
            $count = isset($reactions[$emoji]) ? $reactions[$emoji] : 1;

            // Déclencher immédiatement la notification par cloche
            $this->trigger_immediate_notification($post_id, $user_id, $emoji);

            // Génération du HTML mis à jour
            $new_reactions_html = $this->reactions_helper->get_reactions_html_for_post($post_id);

            // Retourne une réponse JSON valide
            return new JsonResponse([
                'success'      => true,
                'action'       => 'add',
                'post_id'      => $post_id,
                'emoji'        => $emoji,
                'user_id'      => $user_id,
                'count'        => $count,
                'user_reacted' => true,
                'reactions'    => $reactions,
                'html'         => $new_reactions_html,
                'rid'          => $rid,
            ]);

        } catch (\Throwable $e) {
            error_log("[Reactions RID=$rid] add_reaction fatal: " . $e->getMessage());
            ob_end_clean();
            return new JsonResponse([
                'success' => false,
                'stage'   => 'fatal',
                'error'   => $e->getMessage(),
                'rid'     => $rid,
            ], 500);
        }
    }

    /**
     * Supprimer une réaction d'un message
     */
    private function remove_reaction($post_id, $emoji)
    {
        $rid = bin2hex(random_bytes(8));
        if (defined('DEBUG') && DEBUG) {
            error_log("[Reactions RID=$rid] remove_reaction enter post_id=$post_id emoji=$emoji");
        }

        $sql = 'DELETE FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                  AND user_id = ' . (int) $this->user->data['user_id'] . "
                  AND reaction_emoji COLLATE utf8mb4_bin = '" . $this->db->sql_escape($emoji) . "'";
        
        if (defined('DEBUG') && DEBUG) {
            error_log("[Reactions RID=$rid] delete SQL: $sql");
        }
        $this->db->sql_query($sql);

        // Récupérer les réactions mises à jour
        $reactions = $this->get_reactions_array($post_id);
        $count = isset($reactions[$emoji]) ? $reactions[$emoji] : 0;

        // Génération du HTML mis à jour
        $new_reactions_html = $this->reactions_helper->get_reactions_html_for_post($post_id);

        return new JsonResponse([
            'success'      => true,
            'action'       => 'remove',
            'post_id'      => $post_id,
            'emoji'        => $emoji,
            'user_id'      => (int) $this->user->data['user_id'],
            'count'        => $count,
            'user_reacted' => false,
            'reactions'    => $reactions,
            'html'         => $new_reactions_html,
            'rid'          => $rid,
        ]);
    }

    /**
     * Récupérer toutes les réactions pour un message
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
     * Récupérer les utilisateurs ayant réagi avec un emoji spécifique
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

    private function sync_reactions(array $post_ids)
    {
        $payload = [];

        foreach ($post_ids as $pid) {
            $payload[$pid] = [
                'reactions' => $this->get_reactions_array($pid),
                'html'      => $this->reactions_helper->get_reactions_html_for_post($pid),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'posts'   => $payload,
        ]);
    }

    // =============================================================================
    // MÉTHODES DE VALIDATION
    // =============================================================================
    
    /**
     * Vérifier si un message existe et est valide
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
        // Les emojis peuvent être composés : autoriser jusqu'à 191 octets (sécurité côté app)
        if (strlen($emoji) > 191 * 4) { // 4 octets max / point Unicode en UTF-8
            return false;
        }

        // Vérifier la longueur Unicode (par point de code) : tolérer plus de points de code si nécessaire
        $mb_length = mb_strlen($emoji, 'UTF-8');
        if ($mb_length === 0 || $mb_length > 64) { // 64 points code est large pour une séquence emoji
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

        // Vérifier si l'utilisateur a le droit de répondre dans ce forum
        if (!$this->auth->acl_get('f_reply', $post_data['forum_id'])) {
            return false;
        }
        
        // OPTIONNEL : Interdire de réagir à ses propres messages (à décommenter si besoin)
        // if ($post_data['poster_id'] == $this->user->data['user_id']) {
        //     return false;
        // }

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
 * @param int $post_id ID du message
 * @param int $reacter_id ID de l'utilisateur qui a réagi
 * @param string $emoji Emoji de la réaction
 * @return void
 */
    private function trigger_immediate_notification($post_id, $reacter_id, $emoji)
    {
        try {
            // Récupérer l'auteur du post pour le notifier
            $sql = 'SELECT poster_id, topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
            $result = $this->db->sql_query($sql);
            $post_data = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$post_data || !$post_data['poster_id']) {
                error_log('[Reactions AJAX] Post introuvable pour notification, post_id=' . $post_id);
                return;
            }

            $post_author_id = (int) $post_data['poster_id'];
            $topic_id = (int) $post_data['topic_id'];

            // On ne s'envoie pas de notification à soi-même
            if ($post_author_id === $reacter_id) {
                return;
            }

            // Respecter la préférence de notification instantanée de l'auteur
            $author_sql = 'SELECT user_reactions_notify, username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $post_author_id;
            $author_result = $this->db->sql_query($author_sql);
            $author_row = $this->db->sql_fetchrow($author_result);
            $this->db->sql_freeresult($author_result);

            $notify_pref = ($author_row !== false && array_key_exists('user_reactions_notify', $author_row))
                ? (int) $author_row['user_reactions_notify']
                : 1;

            if (!$author_row || $notify_pref !== 1) {
                return;
            }

            $notification_data = [
                'post_id'          => (int) $post_id,
                'topic_id'         => $topic_id,
                'post_author'      => $post_author_id,
                'poster_id'        => $post_author_id,
                'reacter'          => $reacter_id,
                'reacter_username' => $this->user->data['username'] ?? '',
                'emoji'            => $emoji,
            ];

            $notification_ids = $this->notification_manager->add_notifications(
                'notification.type.reaction',
                $notification_data
            );

            if (!empty($notification_ids)) {
                $log_suffix = is_array($notification_ids) ? implode(',', $notification_ids) : $notification_ids;
            } else {
                $log_suffix = 'none';
            }

            error_log('[Reactions AJAX] Notification envoyée OK pour post_id=' . $post_id . ', emoji=' . $emoji . ', auteur=' . $post_author_id . ', ids=' . $log_suffix);
        } catch (\Exception $e) {
            error_log('[Reactions] Erreur lors de l\'envoi de la notification : ' . $e->getMessage());
            error_log('[Reactions] Stack trace: ' . $e->getTraceAsString());
        }
    }
}
