<?php
/**
 * Fichier : ajax.php
 * Chemin : bastien59960/reactions/controller/ajax.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 * @version 1.0.3
 *
 * Rôle :
 * Ce contrôleur est le point d'entrée unique pour toutes les requêtes AJAX
 * de l'extension. Il gère l'ensemble des interactions utilisateur en temps réel,
 * comme l'ajout, la suppression ou la consultation des réactions. Il assure la
 * validation des données, la vérification des permissions, la gestion des erreurs
 * et le déclenchement des notifications instantanées.
 *
 * @input \Symfony\Component\HttpFoundation\Request Requête POST avec un corps JSON.
 *        Le JSON doit contenir :
 *        - `action` (string) : 'add', 'remove', 'get_users', 'sync'.
 *        - `post_id` (int) : ID du message concerné.
 *        - `emoji` (string) : L'emoji de la réaction.
 *        - `sid` (string) : Jeton de session pour la protection CSRF.
 *
 * @output \Symfony\Component\HttpFoundation\JsonResponse Réponse JSON standardisée
 *         contenant un statut de succès et les données pertinentes (comptes, HTML, etc.).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

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

    /** @var \phpbb\event\dispatcher_interface Dispatcher d'événements phpBB */
    protected $dispatcher;

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
        \bastien59960\reactions\controller\helper $reactions_helper,
        \phpbb\event\dispatcher_interface $dispatcher
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
        $this->reactions_helper = $reactions_helper;
        $this->dispatcher = $dispatcher;

        // Charger les fichiers de langue de l'extension
        $this->language->add_lang('common', 'bastien59960/reactions');
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
        // Génération d'un identifiant unique pour le debug et le chronométrage
        $rid = bin2hex(random_bytes(8));
        $t0 = microtime(true);

        try {
            // =====================================================================
            // 1. VÉRIFICATIONS PRÉLIMINAIRES
            // =====================================================================

            // Démarrer un nouveau buffer pour capturer toute sortie inattendue
            ob_start();
        
            // Forcer les headers JSON immédiatement
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-Content-Type-Options: nosniff');
                header('Cache-Control: no-cache, must-revalidate');
            }
            // =====================================================================
            // =====================================================================
            
            // Seuls les utilisateurs connectés peuvent interagir.
            // Retour direct pour éviter de polluer les logs avec une exception attendue.
            if ((int) $this->user->data['user_id'] === ANONYMOUS) { // ANONYMOUS est une constante de phpBB
                if (ob_get_level()) {
                    ob_end_clean();
                }

                return new JsonResponse([
                    'success' => false,
                    'error' => 'User not logged in.',
                    'auth_required' => true,
                    'rid' => $rid,
                ], 403);
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

                // Tentative de correction UTF-8, un problème courant avec les emojis.
                try {
                    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    } else {
                        $fixed = mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
                        $data = json_decode($fixed, true, 512, JSON_THROW_ON_ERROR);
                    }
                } catch (\Throwable $jsonEx2) {
                    error_log("[Reactions RID=$rid] json_decode second attempt failed: " . $jsonEx2->getMessage());

                    // Dernier recours : essayer de parser comme une requête form-encoded.
                    parse_str($raw, $parsed);
                    if (!empty($parsed)) {
                        $data = $parsed;
                        error_log("[Reactions RID=$rid] parsed form-encoded payload as fallback.");
                    } else {
                        return new JsonResponse([
                            'success' => false,
                            'stage'   => 'json_decode',
                            'error'   => $jsonEx2->getMessage(),
                            'raw_len' => strlen($raw),
                            'note'    => 'Raw payload was not valid JSON or form-encoded.',
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
                throw new \phpbb\exception\http_exception(403, 'Jeton CSRF invalide.');
            }

            // Valider que l'action demandée est l'une des actions autorisées.
            if (!in_array($action, ['add', 'remove', 'get', 'get_users', 'sync'], true)) {
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
                    return new JsonResponse([
                        'success' => false,
                        'error'   => 'No valid post_ids provided',
                        'rid'     => $rid,
                    ], 400);
                }
            }

            // Pour les actions non-sync, un post_id valide est requis.
            if ($action !== 'sync' && (!$post_id || !$this->is_valid_post($post_id))) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => $this->language->lang('REACTION_INVALID_POST'),
                    'rid'     => $rid,
                ], 400);
            }

            // Un emoji valide est requis pour ajouter ou supprimer une réaction.
            if (!in_array($action, ['get', 'sync'], true) && (!$emoji || !$this->is_valid_emoji($emoji))) {
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
            
            // La vérification des permissions ne s'applique pas à 'sync' qui gère plusieurs posts.
            // Pour les autres actions, on vérifie si l'utilisateur peut interagir avec le post.
            if ($action !== 'sync')
            {
                if (!$this->can_react_to_post($post_id)) {
                    return new JsonResponse([
                        'success' => false,
                        'error'   => $this->language->lang('REACTION_NOT_AUTHORIZED'),
                        'rid'     => $rid,
                    ], 403);
                }
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
                    $errorMessage = vsprintf($this->language->lang('REACTIONS_LIMIT_POST'), [$max_per_post]);
                    throw new \phpbb\exception\http_exception(429, $errorMessage);
                }
                if ($user_reactions >= $max_per_user) {
                    $errorMessage = vsprintf($this->language->lang('REACTIONS_LIMIT_USER'), [$max_per_user]);
                    throw new \phpbb\exception\http_exception(429, $errorMessage);
                }

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

            // CRITIQUE : Nettoyer le buffer de sortie avant d'envoyer la réponse JSON finale.
            ob_end_clean();
            
            return $resp;

        } catch (\phpbb\exception\http_exception $httpEx) {
            // Gérer les exceptions HTTP (ex: 403 Forbidden).
            // Nettoyer le buffer avant de retourner une erreur JSON
            if (ob_get_level()) {
                ob_end_clean();
            }
            error_log("[Reactions RID=$rid] HttpException: " . $httpEx->getMessage());
            return new JsonResponse([
                'success' => false,
                // Le message est déjà traduit et formaté au moment où l'exception est lancée.
                'error'   => $httpEx->getMessage(),
                'rid'     => $rid,
            ], $httpEx->getStatusCode());

        } catch (\Throwable $e) {
            // Gérer toutes les autres erreurs serveur (500) pour éviter une réponse non-JSON.
            // Nettoyer le buffer avant de retourner une erreur JSON
            if (ob_get_level()) {
                ob_end_clean();
            }
            error_log("[Reactions RID=$rid] Exception attrapée: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return new JsonResponse([
                'success' => false,
                // CORRECTION : Afficher le message d'erreur en mode DEBUG
                'error'   => (defined('DEBUG') && DEBUG) ? $e->getMessage() : $this->language->lang('REACTIONS_SERVER_ERROR'),
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
        // Normalisation et nettoyage de l'emoji côté serveur
        $emoji = (string) $emoji;
        // Supprimer les caractères de contrôle invisibles (sauf tabulation, nouvelle ligne, etc.)
        $emoji = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $emoji);

        if (extension_loaded('intl') && class_exists('\Normalizer')) {
            // Normaliser en Forme C (Composition) pour standardiser les caractères composites.
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

            // Récupérer le topic_id est nécessaire pour l'insertion en base.
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
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'topic_lookup',
                    'error'   => $this->language->lang('REACTION_INVALID_POST'),
                    'rid'     => $rid,
                ], 400);
            }
            $topic_id = (int) $row['topic_id'];

            // Vérifier si l'utilisateur a déjà ajouté cet emoji spécifique à ce message.
            $dupSql = 'SELECT reaction_id FROM ' . $this->post_reactions_table .
                ' WHERE post_id = ' . (int)$post_id .
                ' AND user_id = ' . (int)$user_id .
                " AND reaction_emoji COLLATE utf8mb4_bin = '" . $this->db->sql_escape($emoji) . "'";

            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] duplicate SQL: $dupSql");
            }
            $result = $this->db->sql_query($dupSql);
            $already = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] duplicate found=" . json_encode($already));
            }

            if ($already) {
                return new JsonResponse([
                    'success' => false,
                    'stage'   => 'duplicate',
                    'error'   => $this->language->lang('REACTION_ALREADY_ADDED'),
                    'rid'     => $rid,
                ], 400);
            }

            // Préparer le tableau de données pour l'insertion SQL.
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
            if (defined('DEBUG') && DEBUG) {
                error_log("[Reactions RID=$rid] add_reaction OK in {$elapsed}ms");
            }
            
            // Récupère les réactions mises à jour
            $reactions = $this->get_reactions_array($post_id);
            $count = isset($reactions[$emoji]) ? $reactions[$emoji] : 1;

            // Déclencher immédiatement la notification "cloche" pour l'auteur du message.
            $this->trigger_immediate_notification($post_id, $user_id, $emoji);

            // Notifier les bridges (ex. phpbbwaha) qu'une réaction a été ajoutée.
            $vars = ['post_id', 'user_id', 'emoji', 'action'];
            $action = 'add';
            extract($this->dispatcher->trigger_event('bastien59960.reactions.reaction_toggled', compact($vars)));

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

        // Notifier les bridges qu'une réaction a été supprimée (emoji vide = suppression WA).
        $vars = ['post_id', 'user_id', 'emoji', 'action'];
        $user_id = (int) $this->user->data['user_id'];
        $action  = 'remove';
        $emoji_relay = ''; // Emoji vide = suppression côté WA
        extract($this->dispatcher->trigger_event('bastien59960.reactions.reaction_toggled', [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'emoji'   => $emoji_relay,
            'action'  => $action,
        ]));

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
        $sql = 'SELECT u.user_id, u.username, u.username_clean, r.reaction_time
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
                'username_clean' => $row['username_clean'],
                'reaction_time' => (int) $row['reaction_time']
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
        if (!is_string($emoji) || $emoji === '') {
            return false;
        }
    
        // CORRECTION 4 : Validation renforcée pour les emojis et les sélecteurs de variation.
        // Un emoji valide, même avec des modificateurs (couleur, variation), doit contenir au moins
        // un caractère de base qui est un symbole, une lettre ou un nombre.
        // Les sélecteurs de variation (catégorie \p{M}) ne sont pas suffisants à eux seuls.
        // \p{L} -> Lettres
        // \p{N} -> Nombres
        // \p{P} -> Ponctuation
        // \p{S} -> Symboles (catégorie principale des emojis)
        if (!preg_match('/[\p{L}\p{N}\p{P}\p{S}]/u', $emoji)) {
            return false;
        }

        // Ensuite, on s'assure que la chaîne ne contient QUE des caractères autorisés pour un emoji.
        // Cela inclut les caractères de base, les marques de combinaison (tons de peau, sélecteurs de variation)
        // et les caractères de format (comme le Zero-Width Joiner pour les emojis composites).
        //    \p{Z} -> Séparateurs (espaces)
        //    \p{M} -> Marques (accents, modificateurs de couleur d'emoji)
        //    \p{Cf} -> Format (Zero-Width Joiner U+200D)
        if (!preg_match('/^[\p{L}\p{N}\p{P}\p{S}\p{Z}\p{M}\p{Cf}]+$/u', $emoji)) {
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

        // Récupérer les statuts du message, du sujet et du forum en une seule requête.
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

        // Vérifier si l'utilisateur a le droit de répondre dans ce forum.
        // Règle métier : on ne peut réagir que si on peut participer activement.
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
            // Étape 1 : Récupérer TOUTES les informations nécessaires du message
            $sql = 'SELECT p.poster_id, p.topic_id, p.forum_id, u.username as author_username
                    FROM ' . $this->posts_table . ' p
                    LEFT JOIN ' . USERS_TABLE . ' u ON p.poster_id = u.user_id
                    WHERE post_id = ' . (int) $post_id . '
                    LIMIT 1';

            $result = $this->db->sql_query($sql);
            $post_data = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$post_data || !$post_data['poster_id']) {
                error_log('[Reactions] Notification: Post introuvable pour post_id=' . $post_id);
                return;
            }

            $post_author_id = (int) $post_data['poster_id'];
            $topic_id = (int) $post_data['topic_id'];
            $forum_id = (int) $post_data['forum_id'];

            // On ne s'envoie pas de notification à soi-même
            if ($post_author_id === $reacter_id) {
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions] Notification: Auto-réaction ignorée (post_id=' . $post_id . ')');
                }
                return;
            }

            // Vérifier la préférence de notification de l'auteur
            $sql_pref = 'SELECT user_reactions_notify FROM ' . USERS_TABLE . ' WHERE user_id = ' . $post_author_id;
            $result_pref = $this->db->sql_query($sql_pref);
            $pref_row = $this->db->sql_fetchrow($result_pref);
            $this->db->sql_freeresult($result_pref);

            $notify_pref = isset($pref_row['user_reactions_notify']) ? (int) $pref_row['user_reactions_notify'] : 1;

            if ($notify_pref !== 1) {
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions] Notification: Auteur ' . $post_author_id . ' a désactivé les notifications.');
                }
                return;
            }
            
            // SÉCURITÉ SUPPLÉMENTAIRE : Refuser les emojis vides ou invalides
            if (empty($emoji) || !$this->is_valid_emoji($emoji))
            {
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions] Notification: Emoji vide ou invalide ignoré (post_id=' . $post_id . ')');
                }
                return;
            }

            // Récupérer le nom d'utilisateur du réacteur
            $reacter_username = $this->user->data['username'] ?? 'Utilisateur';

            // Préparer les données COMPLÈTES pour le gestionnaire de notifications
            $notification_data = [
                'topic_id'         => $topic_id,
                'forum_id'         => $forum_id,
                'poster_id'        => $post_author_id,
                'reacter_id'       => $reacter_id,
                'reacter_name'     => $reacter_username,
                'reaction_emoji'   => $emoji,
            ];

            if (defined('DEBUG') && DEBUG) {
                error_log('[Reactions] Notification DATA: ' . json_encode($notification_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            // phpBB se charge de trouver les destinataires, de construire la notification et de la sérialiser.
            // Le tableau de données doit contenir toutes les informations nécessaires pour que les méthodes
            // statiques de la classe de notification (get_item_id, get_item_parent_id, etc.) puissent fonctionner.
            $notification_data['post_id'] = $post_id; // Ajout crucial pour get_item_id

            // CORRECTION CRITIQUE : Utiliser le nom COMPLET du service de notification.
            // Ce nom doit correspondre exactement au nom retourné par get_type() et défini dans services.yml.
            $this->notification_manager->add_notifications('bastien59960.reactions.notification.type.reaction', $notification_data);

            if (defined('DEBUG') && DEBUG) {
                error_log('[Reactions] Notification envoyée pour post_id=' . $post_id . ', auteur=' . $post_author_id);
            }
        } catch (\Exception $e) {
            error_log('[Reactions] Erreur lors de l\'envoi de la notification : ' . $e->getMessage());
            error_log('[Reactions] Stack trace: ' . $e->getTraceAsString());
        }
    }
}
