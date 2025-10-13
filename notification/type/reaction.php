<?php
/**
 * Fichier : notification/type/reaction.php — bastien59960/reactions/notification/type/reaction.php
 *
 * Type de notification pour les réactions aux messages (cloche de notification)
 *
 * Ce fichier définit le comportement des notifications en temps réel (cloche)
 * lorsqu'un utilisateur ajoute une réaction à un message d'un autre utilisateur.
 *
 * ARCHITECTURE PHPBB DES NOTIFICATIONS :
 * =====================================
 * phpBB utilise un système de notifications modulaire basé sur des "types".
 * Chaque type hérite de \phpbb\notification\type\base et définit :
 * 
 * 1. QUI doit être notifié (find_users_for_notification)
 * 2. COMMENT afficher la notification (get_title, get_url, get_avatar)
 * 3. QUOI stocker en base de données (create_insert_array)
 * 4. QUAND envoyer des emails (get_email_template)
 *
 * CYCLE DE VIE D'UNE NOTIFICATION :
 * =================================
 * 1. Le contrôleur AJAX appelle $notification_manager->add_notifications()
 * 2. Le manager appelle find_users_for_notification() pour savoir qui notifier
 * 3. Pour chaque utilisateur, create_insert_array() prépare les données à insérer
 * 4. Les données sont insérées dans la table phpbb_notifications
 * 5. L'utilisateur voit la notification dans sa cloche (header du forum)
 * 6. Au clic, get_url() détermine où rediriger l'utilisateur
 *
 * POINTS CRITIQUES DE SÉCURITÉ :
 * ==============================
 * - TOUJOURS valider les IDs utilisateurs (éviter ANONYMOUS)
 * - TOUJOURS retourner des arrays (jamais false/null/int) dans find_users_for_notification
 * - TOUJOURS échapper les données utilisateur dans les templates
 * - NE JAMAIS notifier l'auteur de l'action (éviter auto-notification)
 *
 * INTÉGRATION AVEC L'EXTENSION REACTIONS :
 * ========================================
 * Cette notification est déclenchée immédiatement après l'ajout d'une réaction
 * via la méthode trigger_immediate_notification() dans controller/ajax.php.
 * Elle complète le système de notifications par email digest (cron).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

/**
 * Classe de notification pour les réactions (type cloche)
 * 
 * Hérite de \phpbb\notification\type\base qui fournit la structure de base
 * pour tous les types de notifications phpBB.
 * 
 * RESPONSABILITÉS :
 * - Déterminer qui doit recevoir la notification (auteur du post)
 * - Fournir les informations d'affichage (titre, lien, avatar)
 * - Gérer le stockage et la récupération des données
 * - Exclure les utilisateurs non pertinents (celui qui a réagi)
 */
class reaction extends \phpbb\notification\type\base
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /**
     * @var \phpbb\config\config Configuration globale du forum
     * Contient tous les paramètres configurables (limites, options, etc.)
     */
    protected $config;
    
    /**
     * @var \phpbb\user_loader Service de chargement des données utilisateur
     * Permet de récupérer les informations des utilisateurs (avatar, nom, etc.)
     * Sans avoir à faire des requêtes SQL manuelles
     */
    protected $user_loader;
    
    /**
     * @var \phpbb\controller\helper Helper de contrôleur
     * Fournit des méthodes utilitaires pour les routes, URLs, etc.
     */
    protected $helper;
    
    /**
     * @var \phpbb\request\request Gestionnaire de requêtes HTTP
     * Permet d'accéder aux données POST, GET, COOKIE de manière sécurisée
     */
    protected $request;
    
    /**
     * @var \phpbb\template\template Moteur de template phpBB
     * Permet de générer du HTML pour les notifications complexes si nécessaire
     */
    protected $template;

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur de la classe de notification
     * 
     * IMPORTANT : L'ordre et le type des paramètres doivent correspondre EXACTEMENT
     * à la configuration dans config/services.yml. Toute différence causera une erreur
     * fatale au démarrage de l'extension.
     * 
     * PARAMÈTRES DU PARENT (7 premiers) :
     * Ces paramètres sont automatiquement injectés par le conteneur de services
     * phpBB grâce à la directive "parent: notification.type.base" dans services.yml
     * 
     * 1. user_loader (injecté par le parent)
     * 2. db (injecté par le parent)
     * 3. cache (injecté par le parent)
     * 4. user (injecté par le parent)
     * 5. auth (injecté par le parent)
     * 6. config (injecté par le parent)
     * 
     * PARAMÈTRES PERSONNALISÉS (suivants) :
     * Ces paramètres sont définis dans la section "arguments:" de services.yml
     * Ils sont spécifiques à notre extension Reactions
     * Constructeur de la classe de notification - VERSION CORRIGÉE
     * 
     * ⚠️ PROBLÈME IDENTIFIÉ :
     * L'ordre des paramètres NE CORRESPONDAIT PAS à ce que phpBB injecte via services.yml
     * 
     * RÈGLE D'OR phpBB :
     * ==================
     * Quand on utilise "parent: notification.type.base", phpBB injecte automatiquement
     * les 6 PREMIERS paramètres du parent dans CET ORDRE EXACT :
     * 
     * 1. \phpbb\user_loader $user_loader
     * 2. \phpbb\db\driver\driver_interface $db  
     * 3. \phpbb\cache\driver\driver_interface $cache
     * 4. \phpbb\language\language $language        ⬅️ ATTENTION: language en 4e position!
     * 5. \phpbb\user $user
     * 6. \phpbb\auth\auth $auth
     * 
     * PUIS viennent nos paramètres personnalisés définis dans services.yml
     * 
     * ERREUR DANS L'ANCIEN CODE :
     * On avait mis $config en 6e position, mais phpBB injecte $auth en 6e !
     * 
     * @param \phpbb\user_loader $user_loader       [1] Service user_loader (du parent)
     * @param \phpbb\db\driver\driver_interface $db [2] Connexion DB (du parent)
     * @param \phpbb\cache\driver\driver_interface $cache [3] Cache (du parent)
     * @param \phpbb\language\language $language    [4] Language (du parent)
     * @param \phpbb\user $user                     [5] User actuel (du parent)
     * @param \phpbb\auth\auth $auth                [6] Auth (du parent)
     * 
     * Les autres dépendances sont injectées via les méthodes setter définies dans services.yml
     */
    public function __construct(
<<<<<<< HEAD
        driver_interface $db,
        language $language,
        user $user,
        auth $auth,
        $phpbb_root_path,
        $php_ext,
        $notifications_table,
        config $config,
        user_loader $user_loader,
        helper $helper,
        request_interface $request,
        template $template
    ) {
        // Appeler le constructeur de la classe parente avec ses 7 arguments requis
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        // Stocker toutes les dépendances dans les propriétés de la classe
        $this->db = $db;
        $this->language = $language;
        $this->user = $user;
        $this->auth = $auth;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->notifications_table = $notifications_table;
        $this->config = $config;
        $this->user_loader = $user_loader;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;

        // Log de débogage (visible uniquement si DEBUG est activé dans config.php)
        if (defined('DEBUG') && DEBUG) {
            error_log('[Reactions Notification] Constructeur initialisé - DB: ' . get_class($db));
        }

        // =====================================================================
        // INSERTION AUTOMATIQUE DU TYPE DE NOTIFICATION EN BASE DE DONNÉES
        // =====================================================================
        // Cette section s'assure que le type "notification.type.reaction"
        // existe dans la table phpbb_notification_types
=======
        \phpbb\db\driver\driver_interface $db,
        \phpbb\language\language $language,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        \phpbb\user_loader $user_loader,
        \phpbb\cache\driver\driver_interface $cache
    ) {
        // ✅ Appel du constructeur parent - phpBB gère l'ordre automatiquement
        parent::__construct($user_loader, $db, $cache, $language, $user, $auth, 'phpbb_notifications');
>>>>>>> 61e982ff6ed4e15d84e828d8d31dd816e0ff8e15
        
        // ✅ Stockage du user_loader du parent
        $this->user_loader = $user_loader;
    }
    
    /**
     * Méthodes setter pour l'injection des dépendances via calls
     */
    public function set_config(\phpbb\config\config $config)
    {
        $this->config = $config;
    }
    
    public function set_helper($helper)
    {
        $this->helper = $helper;
    }
    
    public function set_request(\phpbb\request\request $request)
    {
        $this->request = $request;
    }
    
    public function set_template(\phpbb\template\template $template)
    {
        $this->template = $template;
    }

    // =============================================================================
    // MÉTHODES D'IDENTIFICATION DU TYPE
    // =============================================================================
    
    /**
     * Retourne l'identifiant unique du type de notification
     * 
     * IMPORTANCE CRITIQUE :
     * Cet identifiant doit correspondre EXACTEMENT au nom du service défini
     * dans config/services.yml. C'est ainsi que phpBB fait le lien entre
     * le service et ce code.
     * 
     * FORMAT REQUIS :
     * vendor.extension.notification.type.nom_du_type
     * 
     * UTILISATION :
     * - Enregistrement du type dans le système de notifications
     * - Appel de add_notifications() avec ce type
     * - Stockage en base de données (colonne notification_type_id)
     * - Filtrage des notifications dans l'interface utilisateur
     * 
     * @return string Identifiant unique du type de notification
     */
    public function get_type()
    {
        return 'bastien59960.reactions.notification.type.reaction';
    }

    /**
     * Vérifie si ce type de notification est disponible
     * 
     * Cette méthode permet de désactiver temporairement un type de notification
     * sans avoir à le supprimer du code ou de la configuration.
     * 
     * CAS D'USAGE :
     * - Désactiver les notifications pendant une maintenance
     * - Désactiver selon des permissions utilisateur spécifiques
     * - Désactiver selon la configuration du forum
     * 
     * EXEMPLE D'UTILISATION AVANCÉE :
     * return $this->config['enable_reaction_notifications'] == 1;
     * 
     * VALEUR ACTUELLE :
     * true = Les notifications de réactions sont toujours actives
     * 
     * @return bool True si le type est disponible, False sinon
     */
    public function is_available()
    {
        return true;
    }

    // =============================================================================
    // MÉTHODES D'IDENTIFICATION DES ITEMS
    // =============================================================================
    
    /**
     * Retourne l'ID de l'item notifié (ici : le post_id)
     * 
     * RÔLE :
     * Cette méthode permet à phpBB d'identifier de manière unique chaque notification.
     * Elle est utilisée pour :
     * - Regrouper les notifications similaires
     * - Éviter les doublons de notifications
     * - Supprimer les anciennes notifications lors du remplacement
     * 
     * DANS NOTRE CAS :
     * L'item est le MESSAGE (post) qui a reçu la réaction.
     * On retourne donc son post_id.
     * 
     * IMPORTANT :
     * Cette méthode est STATIC car phpBB l'appelle avant même d'instancier
     * la classe de notification. Elle doit donc travailler uniquement avec
     * les données passées en paramètre $data.
     * 
     * @param array $data Tableau de données de la notification
     *                    Format : ['post_id' => 123, 'emoji' => '👍', ...]
     * @return int ID du message (post_id)
     */
    public static function get_item_id($data)
    {
        return (int) $data['post_id'];
    }

    /**
     * Retourne l'ID du parent de l'item (ici : le topic_id)
     * 
     * RÔLE :
     * Permet de regrouper les notifications par sujet de discussion.
     * Utilisé pour :
     * - Afficher "3 nouvelles réactions dans le sujet X"
     * - Regrouper les notifications dans l'interface
     * - Filtrer les notifications par sujet
     * 
     * DANS NOTRE CAS :
     * Le parent d'un message est le SUJET (topic) auquel il appartient.
     * On retourne donc son topic_id.
     * 
     * POURQUOI ISSET() ?
     * Dans les anciennes versions de l'extension ou lors de migrations,
     * topic_id pourrait ne pas être présent dans $data.
     * On retourne 0 par défaut pour éviter une erreur fatale.
     * 
     * @param array $data Tableau de données de la notification
     * @return int ID du sujet (topic_id) ou 0 si non défini
     */
    public static function get_item_parent_id($data)
    {
        return isset($data['topic_id']) ? (int) $data['topic_id'] : 0;
    }

    // =============================================================================
    // MÉTHODE CRITIQUE : DÉTERMINATION DES DESTINATAIRES
    // =============================================================================
    
    /**
     * ✅ CORRECTION CRITIQUE : Trouve les utilisateurs à notifier
     * 
     * RÔLE FONDAMENTAL :
     * C'est LA méthode la plus importante de toute la classe de notification.
     * phpBB l'appelle pour déterminer QUI doit recevoir la notification.
     * 
     * POURQUOI CETTE MÉTHODE CAUSAIT L'ERREUR ?
     * ==========================================
     * 
     * Le manager de notifications fait ceci dans son code (ligne 412) :
     * 
     * ```php
     * $users_to_notify = $notification_type->find_users_for_notification($data);
     * foreach ($users_to_notify as $user_id => $user_data) {
     *     // Créer la notification pour cet utilisateur
     * }
     * ```
     * 
     * Si cette méthode retourne un INT (comme 60) au lieu d'un ARRAY,
     * le foreach() échoue avec l'erreur :
     * "foreach() argument must be of type array|object, int given"
     * 
     * FORMAT DE RETOUR OBLIGATOIRE :
     * ==============================
     * 
     * MAUVAIS (causait l'erreur) :
     * ```php
     * return 60;                           // ❌ INT
     * return [60];                         // ❌ Array simple
     * return ['user_id' => 60];            // ❌ Array sans clé user_id
     * ```
     * 
     * BON (format correct) :
     * ```php
     * return [
     *     60 => [                          // ✅ Clé = user_id
     *         'user_id' => 60              // ✅ Valeur = array avec user_id
     *     ]
     * ];
     * ```
     * 
     * POURQUOI CE FORMAT BIZARRE ?
     * ============================
     * phpBB doit pouvoir :
     * 1. Itérer sur les utilisateurs (foreach)
     * 2. Accéder rapidement à un utilisateur par son ID (array key)
     * 3. Avoir les données utilisateur dans un array (pour flexibilité)
     * 
     * LOGIQUE MÉTIER :
     * ================
     * Dans notre cas, on veut notifier l'AUTEUR DU MESSAGE qui a reçu une réaction.
     * 
     * Exemple concret :
     * - Bob écrit un message (Bob = poster_id = 2)
     * - Alice ajoute une réaction 👍 au message de Bob (Alice = reacter_id = 3)
     * - On doit notifier Bob (poster_id = 2)
     * - On ne doit PAS notifier Alice (elle a fait l'action)
     * 
     * GESTION DES CAS D'ERREUR :
     * ==========================
     * Si poster_id est manquant, invalide, ou = ANONYMOUS, on retourne []
     * Un array vide est sûr : phpBB fera foreach([]) qui ne fait rien (0 itération)
     * 
     * @param array $data Données de la notification
     *                    Format attendu : ['poster_id' => 2, 'reacter_id' => 3, ...]
     * @param array $options Options supplémentaires (rarement utilisé)
     * @return array Tableau des utilisateurs à notifier
     *               Format : [user_id => ['user_id' => user_id, ...], ...]
     */
    public function find_users_for_notification($data, $options = [])
    {
        // =====================================================================
        // ÉTAPE 1 : VALIDATION DES DONNÉES ENTRANTES
        // =====================================================================
        
        // Vérifier que poster_id existe dans les données
        // Si absent, on ne peut pas déterminer qui notifier
        if (!isset($data['poster_id'])) {
            // Log pour debugging (visible dans les logs PHP du serveur)
            error_log('[Reactions Notification] poster_id manquant dans find_users_for_notification');
            
            // ✅ RETOUR SÉCURISÉ : Array vide (pas false, null ou int)
            // phpBB fera foreach([]) qui ne plante pas
            return [];
        }

        // Conversion en entier pour sécurité
        // Même si poster_id est "2" (string), on aura 2 (int)
        $poster_id = (int) $data['poster_id'];
        
        // =====================================================================
        // ÉTAPE 2 : VALIDATION DE LA VALEUR DE POSTER_ID
        // =====================================================================
        
        // Ne pas notifier si poster_id invalide ou utilisateur non connecté
        // 
        // ANONYMOUS = constante phpBB pour les invités (valeur = 1)
        // Un poster_id <= 0 ou = ANONYMOUS est invalide
        // 
        // CAS PRATIQUES :
        // - poster_id = 0 : Erreur de données (ne devrait jamais arriver)
        // - poster_id = 1 (ANONYMOUS) : Message d'un invité (pas de notification possible)
        // - poster_id < 0 : Corruption de données
        if ($poster_id <= 0 || $poster_id == ANONYMOUS) {
            // Log pour traçabilité
            error_log('[Reactions Notification] poster_id invalide : ' . $poster_id);
            
            // ✅ RETOUR SÉCURISÉ : Array vide
            return [];
        }

        // =====================================================================
        // ÉTAPE 3 : CONSTRUCTION DU TABLEAU DE RETOUR
        // =====================================================================
        
        // ✅ FORMAT OBLIGATOIRE pour phpBB :
        // [
        //     user_id (clé) => [
        //         'user_id' => user_id (valeur)
        //     ]
        // ]
        // 
        // POURQUOI user_id en DOUBLE (clé et valeur) ?
        // - Clé : Permet à phpBB d'accéder directement à $users[60]
        // - Valeur : Permet de stocker des données supplémentaires si besoin
        // 
        // EXEMPLE CONCRET :
        // Si Bob (user_id = 2) a écrit le message, on retourne :
        // [
        //     2 => ['user_id' => 2]
        // ]
        // 
        // phpBB fera ensuite :
        // foreach ([2 => ['user_id' => 2]] as $uid => $user_data) {
        //     // $uid = 2
        //     // $user_data = ['user_id' => 2]
        //     // Créer notification pour user_id = 2
        // }
        return [
            $poster_id => [
                'user_id' => $poster_id
            ]
        ];
        
        // NOTE AVANCÉE :
        // On pourrait enrichir le array avec plus de données :
        // return [
        //     $poster_id => [
        //         'user_id' => $poster_id,
        //         'username' => $data['poster_username'],  // Si disponible
        //         'email' => $data['poster_email'],        // Pour emails
        //     ]
        // ];
        // 
        // Mais dans notre cas, phpBB chargera ces données automatiquement
        // via le user_loader, donc on garde le format minimal.
    }

    // =============================================================================
    // MÉTHODES D'EXCLUSION
    // =============================================================================
    
    /**
     * Retourne la liste des utilisateurs à EXCLURE de la notification
     * 
     * RÔLE :
     * Même si find_users_for_notification() retourne un utilisateur,
     * phpBB vérifiera cette méthode pour l'exclure si nécessaire.
     * 
     * POURQUOI EXCLURE ?
     * On ne veut PAS notifier l'utilisateur qui a effectué l'action.
     * 
     * EXEMPLE CONCRET :
     * - Bob écrit un message
     * - Alice ajoute une réaction 👍
     * - find_users_for_notification() retourne [Bob]
     * - get_excluded_users() retourne [Alice]
     * - Résultat final : Bob est notifié, Alice n'est pas notifiée
     * 
     * CAS PARTICULIER :
     * Si Bob réagit à son propre message, on a :
     * - find_users_for_notification() retourne [Bob]
     * - get_excluded_users() retourne [Bob]
     * - Résultat final : Personne n'est notifié (normal)
     * 
     * SÉCURITÉ :
     * On retourne toujours un array (même vide) pour éviter les erreurs
     * 
     * @return array Tableau des IDs utilisateurs à exclure
     *               Format : [user_id1, user_id2, ...]
     */
    public function get_excluded_users()
    {
        // Récupération de l'ID de l'utilisateur qui a réagi
        // get_data() accède aux données stockées dans $this->data (propriété du parent)
        // Ces données ont été définies dans create_insert_array()
        $reacter_id = isset($this->get_data()['reacter_id']) ? (int) $this->get_data()['reacter_id'] : 0;
        
        // Si reacter_id valide, on l'exclut
        // Sinon, on retourne un array vide (personne à exclure)
        return $reacter_id > 0 ? [$reacter_id] : [];
    }

    // =============================================================================
    // MÉTHODES D'AFFICHAGE
    // =============================================================================
    
    /**
     * Retourne l'URL du lien de la notification
     * 
     * RÔLE :
     * Définit où l'utilisateur sera redirigé quand il clique sur la notification
     * dans sa cloche (header du forum).
     * 
     * COMPORTEMENT SOUHAITÉ :
     * Rediriger vers le MESSAGE EXACT qui a reçu la réaction, avec scroll automatique.
     * 
     * FORMAT DE L'URL :
     * viewtopic.php?p=123#p123
     * 
     * EXPLICATION DES PARAMÈTRES :
     * - viewtopic.php : Page d'affichage d'un sujet
     * - p=123 : Paramètre GET pour identifier le message (post_id = 123)
     * - #p123 : Ancre HTML pour scroller automatiquement au message
     * 
     * POURQUOI CETTE SYNTAXE ?
     * phpBB charge automatiquement la bonne page du sujet (si pagination)
     * et scroll jusqu'au message grâce à l'ancre #p123.
     * 
     * EXEMPLE CONCRET :
     * Si Alice a réagi au message #456 de Bob, quand Bob clique sur la notification,
     * il arrive directement sur viewtopic.php?p=456#p456 (page 12 du sujet si besoin)
     * 
     * SÉCURITÉ :
     * append_sid() ajoute automatiquement le SID (session ID) si nécessaire
     * et échappe les caractères spéciaux pour éviter les injections XSS.
     * 
     * @return string URL complète vers le message notifié
     */
    public function get_url()
    {
        // Récupération du post_id depuis les données stockées
        $post_id = $this->get_data('post_id');
        
        // Construction de l'URL avec append_sid (fonction phpBB)
        // append_sid() garantit que l'URL est sécurisée et inclut le SID si nécessaire
        return append_sid('viewtopic.php', 'p=' . $post_id . '#p' . $post_id);
    }

    /**
     * Retourne le titre court de la notification (ligne unique)
     * 
     * RÔLE :
     * Affiché dans la liste déroulante des notifications (cloche en haut à droite).
     * Doit être COURT et INFORMATIF.
     * 
     * FORMAT ACTUEL :
     * "Nouvelle réaction 👍"
     * 
     * AMÉLIORATIONS POSSIBLES :
     * - "Alice a réagi 👍 à votre message"
     * - "2 nouvelles réactions sur votre message"
     * - "Alice et Bob ont réagi à votre message"
     * 
     * POUR IMPLÉMENTER CES AMÉLIORATIONS :
     * Il faudrait charger le username via $this->user_loader
     * et compter les réactions groupées (si plusieurs notifications)
     * 
     * EXEMPLE D'IMPLÉMENTATION AVANCÉE :
     * ```php
     * $reacter_id = $this->get_data('reacter_id');
     * $this->user_loader->load_users([$reacter_id]);
     * $username = $this->user_loader->get_username($reacter_id);
     * $emoji = $this->get_data('emoji');
     * return $username . ' a réagi ' . $emoji . ' à votre message';
     * ```
     * 
     * @return string Titre court de la notification
     */
    public function get_title()
    {
        // Récupération de l'emoji depuis les données stockées
        $emoji = $this->get_data('emoji');
        
        // Construction du titre simple
        // TODO : Améliorer avec le nom de l'utilisateur pour plus de contexte
        return 'Nouvelle réaction ' . $emoji;
    }

    /**
     * Retourne le template d'email pour cette notification
     * 
     * RÔLE :
     * Si on veut envoyer un EMAIL immédiat lors d'une réaction,
     * cette méthode doit retourner le nom du template d'email.
     * 
     * TEMPLATE D'EMAIL :
     * Un fichier .txt dans language/fr/email/ qui contient le corps de l'email
     * avec des placeholders pour les variables (username, emoji, lien, etc.)
     * 
     * CHOIX ACTUEL :
     * false = Pas d'email immédiat pour ce type de notification
     * 
     * POURQUOI false ?
     * - Les réactions sont des actions légères et fréquentes
     * - Un email immédiat pour chaque réaction serait spam
     * - On préfère regrouper dans un digest quotidien (voir notification_task.php)
     * 
     * POUR ACTIVER LES EMAILS IMMÉDIATS :
     * 1. Créer language/fr/email/reaction_notification.txt
     * 2. Retourner '@bastien59960_reactions/reaction_notification'
     * 3. Implémenter get_email_template_variables()
     * 
     * @return string|false Nom du template d'email ou false
     */
    public function get_email_template()
    {
        return false; // Pas d'email immédiat, uniquement cloche
    }

    /**
     * Retourne les variables pour le template d'email
     * 
     * RÔLE :
     * Si get_email_template() retourne un nom de template,
     * cette méthode fournit les variables à injecter dans le template.
     * 
     * FORMAT DE RETOUR :
     * Array associatif avec les clés correspondant aux placeholders du template
     * 
     * EXEMPLE :
     * ```php
     * return [
     *     'USERNAME' => 'Alice',
     *     'EMOJI' => '👍',
     *     'POST_LINK' => $this->get_url(),
     *     'SITENAME' => $this->config['sitename']
     * ];
     * ```
     * 
     * Dans le template email, on utiliserait :
     * "Hello, {USERNAME} a réagi {EMOJI} à votre message sur {SITENAME}."
     * 
     * CHOIX ACTUEL :
     * Array vide car on n'envoie pas d'emails immédiats (voir get_email_template)
     * 
     * @return array Variables pour le template d'email
     */
    public function get_email_template_variables()
    {
        return []; // Pas d'email, donc pas de variables
    }

    /**
     * Retourne l'avatar de l'utilisateur qui a déclenché la notification
     * 
     * RÔLE :
     * Affiché à côté de la notification dans la liste déroulante (cloche).
     * Permet d'identifier visuellement qui a effectué l'action.
     * 
     * COMPORTEMENT :
     * - Si Alice réagit au message de Bob, Bob voit l'avatar d'Alice
     * - L'avatar est récupéré via le user_loader (cache automatique)
     * 
     * PARAMÈTRES DE get_avatar() :
     * - $user_id : ID de l'utilisateur dont on veut l'avatar
     * - $alt_text : Texte alternatif (false = utiliser le username par défaut)
     * - $ignore_config : Ignorer la config "allow_avatar" (true = toujours afficher)
     * 
     * SÉCURITÉ :
     * Si reacter_id est invalide ou 0, on retourne une string vide (pas d'avatar)
     * 
     * @return string HTML de l'avatar ou string vide si non disponible
     */
    public function get_avatar()
    {
        // Récupération de l'ID de l'utilisateur qui a réagi
        $reacter_id = isset($this->get_data()['reacter_id']) ? (int) $this->get_data()['reacter_id'] : 0;
        
        // Si reacter_id valide, récupérer l'avatar
        if ($reacter_id > 0) {
            // user_loader->get_avatar() retourne le HTML complet de l'avatar
            // Paramètres :
            // - $reacter_id : User ID dont on veut l'avatar
            // - false : Pas de texte alternatif personnalisé (utilise le username)
            // - true : Ignorer la config allow_avatar (toujours afficher)
            return $this->user_loader->get_avatar($reacter_id, false, true);
        }
        
        // Si reacter_id invalide, retourner une string vide
        // phpBB affichera alors un avatar par défaut ou aucun avatar
        return '';
    }

    // =============================================================================
    // MÉTHODES DE GESTION DES DONNÉES
    // =============================================================================
    
    /**
     * ✅ Prépare les données à insérer en base de données
     * 
     * RÔLE FONDAMENTAL :
     * Cette méthode est appelée par phpBB juste avant d'insérer une notification
     * dans la table phpbb_notifications. Elle détermine QUELLES DONNÉES seront
     * stockées et donc disponibles plus tard via get_data().
     * 
     * CYCLE DE VIE :
     * ============
     * 1. Le contrôleur AJAX appelle add_notifications() avec $data
     * 2. phpBB appelle find_users_for_notification() pour savoir qui notifier
     * 3. Pour chaque utilisateur, phpBB appelle create_insert_array()
     * 4. Les données sont insérées dans phpbb_notifications
     * 5. Plus tard, get_title(), get_url(), etc. lisent ces données via get_data()
     * 
     * MÉTHODE set_data() :
     * ===================
     * Héritée du parent (\phpbb\notification\type\base)
     * Stocke les données dans $this->data (tableau interne)
     * Format : $this->data['post_id'] = 123
     * 
     * DONNÉES OBLIGATOIRES :
     * =====================
     * - post_id : ID du message qui a reçu la réaction (CRITIQUE)
     * - topic_id : ID du sujet (pour regroupement et URL)
     * - poster_id : ID de l'auteur du message (qui sera notifié)
     * - reacter_id : ID de l'utilisateur qui a réagi (pour exclusion et avatar)
     * - emoji : L'emoji utilisé (pour affichage dans le titre)
     * 
     * POURQUOI topic_id EST CRITIQUE :
     * ================================
     * Sans topic_id, phpBB ne peut pas :
     * - Regrouper les notifications par sujet
     * - Vérifier les permissions (accès au sujet)
     * - Générer l'URL correcte (si on veut lier au sujet)
     * - Supprimer les notifications si le sujet est supprimé
     * 
     * DONNÉES SUPPLÉMENTAIRES POSSIBLES :
     * ===================================
     * On pourrait stocker plus d'infos pour enrichir l'affichage :
     * - 'post_subject' : Titre du message (pour contexte)
     * - 'topic_title' : Titre du sujet (pour affichage)
     * - 'reaction_time' : Timestamp de la réaction (pour tri chronologique)
     * - 'forum_id' : ID du forum (pour permissions)
     * 
     * GESTION DES DONNÉES MANQUANTES :
     * ================================
     * On utilise isset() et valeurs par défaut pour éviter les erreurs fatales
     * si une donnée est absente. Exemple : topic_id par défaut = 0
     * 
     * APPEL DU PARENT :
     * =================
     * parent::create_insert_array() DOIT être appelé à la fin.
     * Il finalise la préparation des données et retourne le tableau SQL complet.
     * 
     * @param array $data Données brutes passées à add_notifications()
     *                    Format : ['post_id' => 123, 'emoji' => '👍', ...]
     * @param array $pre_create_data Données pré-calculées (rarement utilisé)
     * @return array Tableau SQL prêt pour l'insertion en base
     */
    public function create_insert_array($data, $pre_create_data = [])
    {
        // =====================================================================
        // STOCKAGE DES DONNÉES CRITIQUES
        // =====================================================================
        
        // Stockage du post_id (OBLIGATOIRE)
        // C'est l'identifiant principal de notre notification
        // Utilisé par : get_item_id(), get_url(), toutes les requêtes SQL
        $this->set_data('post_id', $data['post_id']);
        
        // Stockage du topic_id avec valeur par défaut
        // Si topic_id est absent dans $data (cas d'erreur), on met 0
        // Utilisé par : get_item_parent_id(), regroupement, permissions
        $this->set_data('topic_id', isset($data['topic_id']) ? $data['topic_id'] : 0);
        
        // Stockage du poster_id (auteur du message)
        // C'est lui qui recevra la notification
        // Utilisé par : find_users_for_notification(), affichage destinataire
        $this->set_data('poster_id', $data['poster_id']);
        
        // Stockage du reacter_id (celui qui a réagi)
        // Utilisé par : get_excluded_users(), get_avatar(), affichage "qui a réagi"
        $this->set_data('reacter_id', $data['reacter_id']);
        
        // Stockage de l'emoji
        // Utilisé par : get_title(), affichage dans la notification
        // IMPORTANT : L'emoji est stocké en UTF-8 brut (pas d'échappement ici)
        $this->set_data('emoji', $data['emoji']);
        
        // =====================================================================
        // DONNÉES SUPPLÉMENTAIRES OPTIONNELLES (COMMENTÉES)
        // =====================================================================
        
        // Si on veut enrichir l'affichage plus tard, décommenter et adapter :
        
        // Titre du message (pour contexte dans la notification)
        // $this->set_data('post_subject', isset($data['post_subject']) ? $data['post_subject'] : '');
        
        // Titre du sujet (pour affichage complet)
        // $this->set_data('topic_title', isset($data['topic_title']) ? $data['topic_title'] : '');
        
        // Timestamp de la réaction (pour tri chronologique précis)
        // $this->set_data('reaction_time', isset($data['reaction_time']) ? $data['reaction_time'] : time());
        
        // ID du forum (pour vérifications de permissions avancées)
        // $this->set_data('forum_id', isset($data['forum_id']) ? $data['forum_id'] : 0);
        
        // Username du reacter (pour affichage sans requête supplémentaire)
        // $this->set_data('reacter_username', isset($data['reacter_username']) ? $data['reacter_username'] : '');
        
        // =====================================================================
        // FINALISATION ET RETOUR
        // =====================================================================
        
        // Appel OBLIGATOIRE du parent pour finaliser
        // Le parent ajoute des données système :
        // - notification_time : timestamp de création
        // - user_id : destinataire (calculé par find_users_for_notification)
        // - notification_read : statut lu/non-lu (0 par défaut)
        // - notification_type_id : ID du type (calculé depuis get_type())
        // 
        // Retourne le tableau SQL complet prêt pour INSERT INTO
        return parent::create_insert_array($data, $pre_create_data);
    }

    // =============================================================================
    // MÉTHODES DE GROUPEMENT ET RÉFÉRENCE
    // =============================================================================
    
    /**
     * Retourne la référence pour le regroupement des notifications
     * 
     * RÔLE :
     * Permet à phpBB de regrouper des notifications similaires en une seule.
     * 
     * EXEMPLE D'UTILISATION :
     * Si 3 personnes réagissent avec 👍 au même message, au lieu d'afficher
     * 3 notifications distinctes, phpBB peut afficher :
     * "Alice, Bob et Charlie ont réagi 👍 à votre message"
     * 
     * COMPORTEMENT ACTUEL :
     * On retourne l'emoji, donc :
     * - Toutes les réactions 👍 sur un même post peuvent être regroupées
     * - Les réactions 👍 et ❤️ restent séparées (emojis différents)
     * 
     * ALTERNATIVES POSSIBLES :
     * - Retourner 'reaction' : Groupe TOUTES les réactions (peu importe l'emoji)
     * - Retourner $post_id : Groupe par message (mais ça fait doublon avec get_item_id)
     * - Retourner $emoji . '_' . $post_id : Groupe par emoji ET par message
     * 
     * POUR DÉSACTIVER LE REGROUPEMENT :
     * Retourner une valeur unique à chaque fois :
     * return uniqid(); // Chaque notification reste séparée
     * 
     * NOTE TECHNIQUE :
     * phpBB compare les valeurs de get_reference() pour décider du regroupement.
     * Si 2 notifications ont la même référence (et même item_id), elles sont groupées.
     * 
     * @return string Valeur de référence pour le regroupement
     */
    public function get_reference()
    {
        // On regroupe par emoji : toutes les réactions avec le même emoji
        // sur le même message seront regroupées ensemble
        return $this->get_data('emoji');
    }

    /**
     * Retourne la raison de la notification (texte explicatif)
     * 
     * RÔLE :
     * Fournit un texte expliquant pourquoi l'utilisateur a reçu cette notification.
     * 
     * UTILISATION :
     * Affiché dans certains contextes (emails, paramètres de notifications)
     * pour expliquer la logique de notification.
     * 
     * EXEMPLES POSSIBLES :
     * - "Vous avez reçu une réaction sur votre message"
     * - "Quelqu'un a réagi à votre message"
     * - "Vous êtes l'auteur du message"
     * 
     * CHOIX ACTUEL :
     * String vide = Pas de raison spécifique affichée
     * 
     * POURQUOI STRING VIDE ?
     * La raison est évidente : l'utilisateur a écrit le message, donc il est normal
     * qu'il soit notifié des réactions. Pas besoin d'explication supplémentaire.
     * 
     * POUR AJOUTER UNE RAISON :
     * return 'Vous êtes l\'auteur du message';
     * 
     * @return string Texte expliquant la raison de la notification
     */
    public function get_reason()
    {
        return ''; // Pas de raison explicite nécessaire
    }

    // =============================================================================
    // MÉTHODE OBLIGATOIRE : USERS_TO_QUERY
    // =============================================================================
    
    /**
     * ✅ Retourne les IDs utilisateurs à charger pour cette notification
     * 
     * RÔLE CRITIQUE :
     * Cette méthode est OBLIGATOIRE (définie dans type_interface).
     * Elle indique à phpBB quels utilisateurs charger depuis la base de données
     * pour afficher correctement la notification.
     * 
     * POURQUOI CETTE MÉTHODE EST NÉCESSAIRE ?
     * =======================================
     * Quand phpBB affiche une notification, il a besoin de charger les données
     * utilisateur (username, avatar, permissions) de tous les utilisateurs
     * mentionnés dans la notification.
     * 
     * DANS NOTRE CAS :
     * ================
     * Notre notification mentionne 2 utilisateurs :
     * 1. Le POSTER (poster_id) : L'auteur du message (destinataire)
     * 2. Le REACTER (reacter_id) : Celui qui a réagi (affiché dans la notification)
     * 
     * phpBB va :
     * 1. Appeler users_to_query() pour savoir qui charger
     * 2. Faire un SELECT sur phpbb_users avec ces IDs
     * 3. Mettre en cache les données utilisateur
     * 4. Les rendre disponibles via $this->user_loader
     * 
     * FORMAT DE RETOUR :
     * ==================
     * Array d'IDs utilisateurs à charger
     * Format : [user_id1, user_id2, ...]
     * 
     * EXEMPLE CONCRET :
     * =================
     * Bob (user_id=2) écrit un message
     * Alice (user_id=3) réagit avec 👍
     * 
     * Cette méthode retourne : [2, 3]
     * phpBB charge les données de Bob ET Alice
     * 
     * DANS get_title() on pourrait alors faire :
     * "Alice a réagi 👍 à votre message"
     * (Alice = reacter, chargé grâce à users_to_query)
     * 
     * GESTION DES CAS D'ERREUR :
     * ==========================
     * Si poster_id ou reacter_id sont invalides (0 ou absent),
     * on ne les inclut pas dans le tableau de retour.
     * 
     * OPTIMISATION :
     * ==============
     * On utilise array_filter() pour retirer automatiquement les valeurs
     * nulles, false, 0, etc. Ne reste que les IDs valides > 0.
     * 
     * @return array Tableau d'IDs utilisateurs à charger
     *               Format : [user_id1, user_id2, ...]
     */
    public function users_to_query()
    {
        // Récupération des IDs depuis les données stockées
        // get_data() retourne la valeur ou null si la clé n'existe pas
        $poster_id = $this->get_data('poster_id');
        $reacter_id = $this->get_data('reacter_id');
        
        // Construction du tableau avec les 2 utilisateurs
        // array_filter() retire automatiquement les valeurs "vides" :
        // - null (si get_data retourne null)
        // - 0 (si l'ID est invalide)
        // - false (si jamais stocké comme false)
        // 
        // Ne restent que les IDs valides > 0
        $users = array_filter([
            $poster_id,   // Auteur du message
            $reacter_id   // Celui qui a réagi
        ]);
        
        // Retour du tableau nettoyé
        // Exemple de résultat : [2, 3] ou [2] ou [] selon les données
        return $users;
        
        // NOTE AVANCÉE :
        // ==============
        // Si on avait plusieurs reacters dans une notification groupée,
        // on pourrait faire :
        // 
        // $reacter_ids = $this->get_data('reacter_ids'); // Array de plusieurs IDs
        // return array_filter(array_merge(
        //     [$poster_id],
        //     $reacter_ids
        // ));
        // 
        // Cela chargerait l'auteur + tous les utilisateurs qui ont réagi
    }

    // =============================================================================
    // MÉTHODES HÉRITÉES DU PARENT (NON SURCHARGÉES)
    // =============================================================================
    
    /*
     * Les méthodes suivantes sont héritées de \phpbb\notification\type\base
     * et fonctionnent correctement avec leur implémentation par défaut.
     * On ne les surcharge pas pour garder le code simple.
     * 
     * MÉTHODES HÉRITÉES IMPORTANTES :
     * ================================
     * 
     * get_data($key = null)
     * ---------------------
     * Récupère les données stockées par set_data()
     * Utilisation : $this->get_data('post_id')
     * 
     * set_data($key, $value)
     * ----------------------
     * Stocke une donnée dans $this->data
     * Utilisation : $this->set_data('emoji', '👍')
     * 
     * is_enabled()
     * ------------
     * Vérifie si l'utilisateur a activé ce type dans ses préférences
     * Par défaut : vérifie config_name dans phpbb_user_notifications
     * 
     * get_insert_array()
     * ------------------
     * Finalise les données après create_insert_array()
     * Appelée automatiquement par phpBB
     * 
     * mark_read()
     * -----------
     * Marque la notification comme lue
     * Appelée quand l'utilisateur clique sur "Tout marquer comme lu"
     * 
     * delete()
     * --------
     * Supprime la notification de la base de données
     * Appelée lors de la purge des anciennes notifications
     * 
     * POURQUOI NE PAS SURCHARGER ?
     * ============================
     * L'implémentation par défaut du parent est suffisante et robuste.
     * Surcharger sans raison augmente la complexité et les risques de bugs.
     * 
     * QUAND SURCHARGER ?
     * ==================
     * - Si on a besoin de logique personnalisée (ex: permissions spéciales)
     * - Si on veut modifier le comportement par défaut (ex: ne jamais marquer comme lu)
     * - Si on doit faire des requêtes SQL supplémentaires
     */

    // =============================================================================
    // NOTES DE DÉBOGAGE ET MAINTENANCE
    // =============================================================================
    
    /*
     * PROBLÈMES COURANTS ET SOLUTIONS
     * ================================
     * 
     * 1. ERREUR "foreach() argument must be of type array|object, int given"
     *    SOLUTION : Vérifier que find_users_for_notification() retourne TOUJOURS
     *               un array au format [user_id => ['user_id' => user_id]]
     * 
     * 2. NOTIFICATION NON AFFICHÉE
     *    CAUSES POSSIBLES :
     *    - L'utilisateur a désactivé ce type dans ses préférences
     *    - get_excluded_users() exclut le destinataire
     *    - find_users_for_notification() retourne un array vide
     *    - Le service n'est pas correctement enregistré dans services.yml
     *    DIAGNOSTIC :
     *    - Vérifier les logs PHP pour erreurs
     *    - Vérifier la table phpbb_notifications (INSERT réussi ?)
     *    - Vérifier phpbb_user_notifications (préférences utilisateur)
     * 
     * 3. AVATAR NON AFFICHÉ
     *    CAUSES :
     *    - reacter_id invalide ou = 0
     *    - Avatars désactivés globalement dans la config forum
     *    - user_loader non injecté correctement
     *    SOLUTION :
     *    - Vérifier que create_insert_array() stocke bien reacter_id
     *    - Vérifier les logs : error_log dans get_avatar()
     * 
     * 4. URL INCORRECTE
     *    CAUSE : post_id manquant ou = 0
     *    SOLUTION : Vérifier create_insert_array() et le contrôleur AJAX
     * 
     * 5. TITRE VIDE OU BIZARRE
     *    CAUSE : emoji manquant ou mal encodé
     *    SOLUTION : Vérifier l'encodage UTF-8 dans ajax.php (safeEmoji)
     * 
     * LOGS UTILES POUR LE DÉBOGAGE
     * ============================
     * 
     * Dans le constructeur :
     * error_log('[Reactions Notification] Constructeur appelé');
     * 
     * Dans find_users_for_notification :
     * error_log('[Reactions Notification] find_users called with poster_id=' . $poster_id);
     * error_log('[Reactions Notification] Returning: ' . json_encode($result));
     * 
     * Dans create_insert_array :
     * error_log('[Reactions Notification] create_insert_array called with data: ' . json_encode($data));
     * error_log('[Reactions Notification] Stored emoji: ' . $this->get_data('emoji'));
     * 
     * VÉRIFICATIONS SQL MANUELLES
     * ===========================
     * 
     * Voir toutes les notifications de réactions :
     * SELECT * FROM phpbb_notifications 
     * WHERE notification_type_id = (
     *     SELECT notification_type_id FROM phpbb_notification_types 
     *     WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'
     * );
     * 
     * Voir les préférences utilisateur :
     * SELECT * FROM phpbb_user_notifications 
     * WHERE method = 'notification.method.board';
     * 
     * OPTIMISATIONS FUTURES
     * =====================
     * 
     * 1. CACHE DES USERNAMES
     *    Stocker reacter_username dans create_insert_array()
     *    Évite un chargement user_loader dans get_title()
     * 
     * 2. REGROUPEMENT INTELLIGENT
     *    Dans get_title(), détecter si c'est une notification groupée
     *    Afficher "3 personnes ont réagi 👍" au lieu de "Nouvelle réaction 👍"
     * 
     * 3. PRÉVISUALISATION DU MESSAGE
     *    Stocker post_text (tronqué) dans create_insert_array()
     *    Afficher dans une tooltip ou description étendue
     * 
     * 4. MULTI-ÉMOJIS
     *    Si plusieurs réactions groupées avec emojis différents
     *    Afficher "Alice a réagi 👍❤️😂 à votre message"
     * 
     * TESTS UNITAIRES SUGGÉRÉS
     * =========================
     * 
     * 1. Test find_users_for_notification() :
     *    - Avec poster_id valide : doit retourner [poster_id => [...]]
     *    - Avec poster_id = 0 : doit retourner []
     *    - Avec poster_id = ANONYMOUS : doit retourner []
     *    - Sans poster_id dans $data : doit retourner []
     * 
     * 2. Test get_excluded_users() :
     *    - Avec reacter_id valide : doit retourner [reacter_id]
     *    - Avec reacter_id = 0 : doit retourner []
     * 
     * 3. Test create_insert_array() :
     *    - Vérifier que toutes les données sont stockées
     *    - Vérifier le retour de parent::create_insert_array()
     * 
     * 4. Test get_url() :
     *    - Doit retourner viewtopic.php?p=XXX#pXXX
     *    - Avec différents post_id
     * 
     * COMPATIBILITÉ PHPBB
     * ===================
     * 
     * Testé avec : phpBB 3.3.15
     * Compatible : phpBB 3.3.x (toutes versions)
     * Non compatible : phpBB 3.2.x et antérieures (API différente)
     * 
     * DÉPENDANCES SYSTÈME
     * ===================
     * 
     * Services requis (injectés via constructeur) :
     * - user_loader : Pour avatars et usernames
     * - db : Pour requêtes SQL (hérité du parent)
     * - cache : Pour optimisations (hérité du parent)
     * - user : Utilisateur actuel (hérité du parent)
     * - auth : Permissions (hérité du parent)
     * - config : Configuration forum (hérité du parent)
     * 
     * Tables utilisées :
     * - phpbb_notifications : Stockage des notifications
     * - phpbb_notification_types : Types de notifications
     * - phpbb_user_notifications : Préférences utilisateur
     * - phpbb_users : Données utilisateur (via user_loader)
     * 
     * SÉCURITÉ ET PERMISSIONS
     * =======================
     * 
     * Vérifications automatiques par phpBB :
     * - L'utilisateur doit avoir accès au forum/sujet
     * - L'utilisateur ne peut pas voir les notifications de sujets privés
     * - Les notifications sont filtrées selon les permissions ACL
     * 
     * Vérifications dans notre code :
     * - Ne pas notifier ANONYMOUS (find_users_for_notification)
     * - Ne pas notifier l'auteur de l'action (get_excluded_users)
     * - Valider que poster_id > 0 (find_users_for_notification)
     * 
     * RESSOURCES OFFICIELLES
     * ======================
     * 
     * Documentation phpBB :
     * https://area51.phpbb.com/docs/dev/3.3.x/extensions/tutorial_notifications.html
     * 
     * API Notification Manager :
     * https://area51.phpbb.com/docs/code/3.3.x/phpbb/notification/manager.html
     * 
     * Classe parente base :
     * https://area51.phpbb.com/docs/code/3.3.x/phpbb/notification/type/base.html
     * 
     * Exemples officiels :
     * phpBB core : phpbb/notification/type/post.php
     * phpBB core : phpbb/notification/type/quote.php
     */
}