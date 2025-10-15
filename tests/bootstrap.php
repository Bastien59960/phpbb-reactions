<?php
/**
 * Fichier : tests/bootstrap.php — bastien59960/reactions/tests/bootstrap.php
 *
 * Bootstrap pour les tests de l'extension Reactions.
 *
 * Ce fichier initialise l'environnement de test pour l'extension Reactions.
 * Il configure les autoloaders, les variables d'environnement, et les
 * services nécessaires pour l'exécution des tests.
 *
 * Points clés de la logique métier :
 *   - Configuration de l'environnement de test phpBB
 *   - Initialisation des services et dépendances
 *   - Configuration des autoloaders pour les tests
 *   - Gestion des variables d'environnement de test
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// =============================================================================
// CONFIGURATION DE BASE
// =============================================================================

// Définir l'environnement de test
define('PHPBB_TESTING', true);
define('PHPBB_INSTALLED', true);

// Configuration des erreurs pour les tests
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// =============================================================================
// AUTOLOADER
// =============================================================================

// Charger l'autoloader Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    throw new RuntimeException('Composer autoloader not found. Run composer install.');
}

// =============================================================================
// CONFIGURATION DE LA BASE DE DONNÉES DE TEST
// =============================================================================

// Configuration de la base de données en mémoire pour les tests
$db_config = [
    'dbms' => 'sqlite',
    'dbhost' => ':memory:',
    'dbname' => 'test_db',
    'dbuser' => '',
    'dbpasswd' => '',
    'dbport' => '',
    'table_prefix' => 'phpbb_',
    'charset' => 'utf8mb4',
];

// =============================================================================
// CONFIGURATION DES SERVICES DE TEST
// =============================================================================

// Configuration des services pour les tests
$services_config = [
    'dbal.conn' => 'phpbb\db\driver\sqlite',
    'user' => 'phpbb\user',
    'request' => 'phpbb\request\request',
    'template' => 'phpbb\template\template',
    'language' => 'phpbb\language\language',
    'auth' => 'phpbb\auth\auth',
    'config' => 'phpbb\config\config',
    'notification_manager' => 'phpbb\notification\manager',
    'user_loader' => 'phpbb\user_loader',
];

// =============================================================================
// FONCTIONS UTILITAIRES POUR LES TESTS
// =============================================================================

/**
 * Créer une instance de test de la base de données
 * 
 * @return \phpbb\db\driver\driver_interface
 */
function create_test_database()
{
    global $db_config;
    
    $db = new \phpbb\db\driver\sqlite();
    $db->sql_connect($db_config['dbhost'], $db_config['dbuser'], $db_config['dbpasswd'], $db_config['dbname'], $db_config['dbport']);
    
    return $db;
}

/**
 * Créer une instance de test de l'utilisateur
 * 
 * @return \phpbb\user
 */
function create_test_user()
{
    $user = new \phpbb\user();
    $user->data = [
        'user_id' => 1,
        'username' => 'test_user',
        'user_email' => 'test@example.com',
        'session_id' => 'test_session_id',
        'user_lang' => 'fr',
        'user_timezone' => 'Europe/Paris',
    ];
    
    return $user;
}

/**
 * Créer une instance de test de la configuration
 * 
 * @return \phpbb\config\config
 */
function create_test_config()
{
    $config = new \phpbb\config\config([
        'bastien59960_reactions_enabled' => 1,
        'bastien59960_reactions_max_per_post' => 20,
        'bastien59960_reactions_max_per_user' => 10,
        'bastien59960_reactions_spam_time' => 2700,
        'server_name' => 'localhost',
        'server_port' => 80,
        'script_path' => '/',
        'cookie_name' => 'phpbb_test',
        'cookie_domain' => 'localhost',
        'cookie_path' => '/',
        'cookie_secure' => 0,
        'cookie_httponly' => 1,
        'session_length' => 3600,
        'form_token_lifetime' => 3600,
        'form_token_sid_guests' => 1,
        'form_token_mintime' => 0,
        'form_token_max_time' => 86400,
        'form_token_max_time_guests' => 3600,
        'form_token_max_time_bots' => 3600,
        'form_token_max_time_guests_register' => 3600,
        'form_token_max_time_guests_contact' => 3600,
        'form_token_max_time_guests_forgot' => 3600,
        'form_token_max_time_guests_resend' => 3600,
        'form_token_max_time_guests_activate' => 3600,
        'form_token_max_time_guests_login' => 3600,
        'form_token_max_time_guests_logout' => 3600,
        'form_token_max_time_guests_confirm' => 3600,
        'form_token_max_time_guests_confirm_admin' => 3600,
        'form_token_max_time_guests_confirm_user' => 3600,
        'form_token_max_time_guests_confirm_email' => 3600,
        'form_token_max_time_guests_confirm_password' => 3600,
        'form_token_max_time_guests_confirm_username' => 3600,
        'form_token_max_time_guests_confirm_avatar' => 3600,
        'form_token_max_time_guests_confirm_signature' => 3600,
        'form_token_max_time_guests_confirm_website' => 3600,
        'form_token_max_time_guests_confirm_location' => 3600,
        'form_token_max_time_guests_confirm_occupation' => 3600,
        'form_token_max_time_guests_confirm_interests' => 3600,
        'form_token_max_time_guests_confirm_aim' => 3600,
        'form_token_max_time_guests_confirm_yahoo' => 3600,
        'form_token_max_time_guests_confirm_icq' => 3600,
        'form_token_max_time_guests_confirm_msn' => 3600,
        'form_token_max_time_guests_confirm_jabber' => 3600,
        'form_token_max_time_guests_confirm_skype' => 3600,
        'form_token_max_time_guests_confirm_facebook' => 3600,
        'form_token_max_time_guests_confirm_twitter' => 3600,
        'form_token_max_time_guests_confirm_google' => 3600,
        'form_token_max_time_guests_confirm_linkedin' => 3600,
        'form_token_max_time_guests_confirm_instagram' => 3600,
        'form_token_max_time_guests_confirm_youtube' => 3600,
        'form_token_max_time_guests_confirm_twitch' => 3600,
        'form_token_max_time_guests_confirm_discord' => 3600,
        'form_token_max_time_guests_confirm_steam' => 3600,
        'form_token_max_time_guests_confirm_reddit' => 3600,
        'form_token_max_time_guests_confirm_github' => 3600,
        'form_token_max_time_guests_confirm_gitlab' => 3600,
        'form_token_max_time_guests_confirm_bitbucket' => 3600,
        'form_token_max_time_guests_confirm_stackoverflow' => 3600,
        'form_token_max_time_guests_confirm_codepen' => 3600,
        'form_token_max_time_guests_confirm_jsfiddle' => 3600,
        'form_token_max_time_guests_confirm_jsbin' => 3600,
        'form_token_max_time_guests_confirm_plunker' => 3600,
        'form_token_max_time_guests_confirm_jsdoit' => 3600,
        'form_token_max_time_guests_confirm_jsitor' => 3600,
        'form_token_max_time_guests_confirm_jsrun' => 3600,
        'form_token_max_time_guests_confirm_jsfiddle' => 3600,
        'form_token_max_time_guests_confirm_jsbin' => 3600,
        'form_token_max_time_guests_confirm_plunker' => 3600,
        'form_token_max_time_guests_confirm_jsdoit' => 3600,
        'form_token_max_time_guests_confirm_jsitor' => 3600,
        'form_token_max_time_guests_confirm_jsrun' => 3600,
    ]);
    
    return $config;
}

/**
 * Créer une instance de test de la requête
 * 
 * @return \phpbb\request\request
 */
function create_test_request()
{
    $request = new \phpbb\request\request();
    
    return $request;
}

/**
 * Créer une instance de test du template
 * 
 * @return \phpbb\template\template
 */
function create_test_template()
{
    $template = new \phpbb\template\template();
    
    return $template;
}

/**
 * Créer une instance de test de la langue
 * 
 * @return \phpbb\language\language
 */
function create_test_language()
{
    $language = new \phpbb\language\language();
    
    return $language;
}

/**
 * Créer une instance de test de l'authentification
 * 
 * @return \phpbb\auth\auth
 */
function create_test_auth()
{
    $auth = new \phpbb\auth\auth();
    
    return $auth;
}

/**
 * Créer une instance de test du gestionnaire de notifications
 * 
 * @return \phpbb\notification\manager
 */
function create_test_notification_manager()
{
    $notification_manager = new \phpbb\notification\manager();
    
    return $notification_manager;
}

/**
 * Créer une instance de test du chargeur d'utilisateurs
 * 
 * @return \phpbb\user_loader
 */
function create_test_user_loader()
{
    $user_loader = new \phpbb\user_loader();
    
    return $user_loader;
}

// =============================================================================
// CONFIGURATION DES RÉPERTOIRES DE TEST
// =============================================================================

// Créer les répertoires de test s'ils n'existent pas
$test_dirs = [
    __DIR__ . '/logs',
    __DIR__ . '/cache',
    __DIR__ . '/temp',
];

foreach ($test_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// =============================================================================
// CONFIGURATION DES CONSTANTES DE TEST
// =============================================================================

// Définir les constantes nécessaires pour les tests
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}

if (!defined('ITEM_LOCKED')) {
    define('ITEM_LOCKED', 1);
}

if (!defined('ITEM_UNLOCKED')) {
    define('ITEM_UNLOCKED', 0);
}

if (!defined('POSTS_TABLE')) {
    define('POSTS_TABLE', 'phpbb_posts');
}

if (!defined('TOPICS_TABLE')) {
    define('TOPICS_TABLE', 'phpbb_topics');
}

if (!defined('FORUMS_TABLE')) {
    define('FORUMS_TABLE', 'phpbb_forums');
}

if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', 'phpbb_users');
}

// =============================================================================
// CONFIGURATION DES TESTS
// =============================================================================

// Configuration des tests
$test_config = [
    'database' => $db_config,
    'services' => $services_config,
    'directories' => [
        'logs' => __DIR__ . '/logs',
        'cache' => __DIR__ . '/cache',
        'temp' => __DIR__ . '/temp',
    ],
];

// =============================================================================
// INITIALISATION FINALE
// =============================================================================

// Initialiser l'environnement de test
if (function_exists('xdebug_start_trace')) {
    xdebug_start_trace(__DIR__ . '/logs/xdebug_trace');
}

// Log de démarrage des tests
error_log('[Tests] Bootstrap initialisé - ' . date('Y-m-d H:i:s'));

// =============================================================================
// FONCTIONS DE TEST GLOBALES
// =============================================================================

/**
 * Vérifier si l'environnement de test est configuré
 * 
 * @return bool
 */
function is_test_environment()
{
    return defined('PHPBB_TESTING') && PHPBB_TESTING === true;
}

/**
 * Obtenir la configuration de test
 * 
 * @return array
 */
function get_test_config()
{
    global $test_config;
    return $test_config;
}

/**
 * Nettoyer l'environnement de test
 */
function cleanup_test_environment()
{
    // Nettoyer les fichiers temporaires
    $temp_files = glob(__DIR__ . '/temp/*');
    foreach ($temp_files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Nettoyer les logs de test
    $log_files = glob(__DIR__ . '/logs/*.log');
    foreach ($log_files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// =============================================================================
// FIN DU BOOTSTRAP
// =============================================================================

// Marquer le bootstrap comme terminé
define('PHPBB_TEST_BOOTSTRAP_LOADED', true);

// Log de fin du bootstrap
error_log('[Tests] Bootstrap terminé - ' . date('Y-m-d H:i:s'));
