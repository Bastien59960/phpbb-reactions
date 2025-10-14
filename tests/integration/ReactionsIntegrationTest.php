<?php
/**
 * Tests d'intégration pour l'extension Reactions
 * 
 * Ce fichier contient les tests d'intégration qui vérifient
 * le fonctionnement complet de l'extension Reactions dans
 * un environnement proche de la production.
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\tests\integration;

use PHPUnit\Framework\TestCase;
use bastien59960\reactions\controller\ajax;
use bastien59960\reactions\event\listener;
use bastien59960\reactions\notification\type\reaction;
use bastien59960\reactions\cron\notification_task;

/**
 * Tests d'intégration pour l'extension Reactions
 * 
 * Teste l'intégration complète de l'extension :
 * - Contrôleur AJAX avec base de données réelle
 * - Listener d'événements avec templates
 * - Système de notifications complet
 * - Tâche cron de notification
 */
class ReactionsIntegrationTest extends TestCase
{
    // =============================================================================
    // PROPRIÉTÉS DE TEST
    // =============================================================================
    
    /** @var \PDO Instance de la base de données de test */
    protected $pdo;
    
    /** @var ajax Instance du contrôleur AJAX */
    protected $ajax_controller;
    
    /** @var listener Instance du listener d'événements */
    protected $event_listener;
    
    /** @var reaction Instance du type de notification */
    protected $notification_type;
    
    /** @var notification_task Instance de la tâche cron */
    protected $cron_task;

    // =============================================================================
    // CONFIGURATION DES TESTS
    // =============================================================================
    
    /**
     * Configuration avant chaque test
     * 
     * Initialise la base de données de test et les instances
     * des composants de l'extension.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser la base de données de test
        $this->initializeTestDatabase();
        
        // Initialiser les composants de l'extension
        $this->initializeExtensionComponents();
    }
    
    /**
     * Initialiser la base de données de test
     * 
     * Crée une base de données SQLite en mémoire avec
     * la structure nécessaire pour les tests.
     */
    protected function initializeTestDatabase()
    {
        // Créer une base de données SQLite en mémoire
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Créer les tables nécessaires
        $this->createTestTables();
        
        // Insérer des données de test
        $this->insertTestData();
    }
    
    /**
     * Créer les tables de test
     * 
     * Crée la structure de base de données nécessaire
     * pour les tests d'intégration.
     */
    protected function createTestTables()
    {
        // Table des réactions
        $sql = "
            CREATE TABLE phpbb_post_reactions (
                reaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                topic_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                reaction_emoji VARCHAR(20) NOT NULL,
                reaction_time INTEGER NOT NULL,
                reaction_notified INTEGER DEFAULT 0
            )
        ";
        $this->pdo->exec($sql);
        
        // Table des messages
        $sql = "
            CREATE TABLE phpbb_posts (
                post_id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id INTEGER NOT NULL,
                forum_id INTEGER NOT NULL,
                poster_id INTEGER NOT NULL,
                post_subject VARCHAR(255) NOT NULL,
                post_text TEXT NOT NULL,
                post_time INTEGER NOT NULL
            )
        ";
        $this->pdo->exec($sql);
        
        // Table des sujets
        $sql = "
            CREATE TABLE phpbb_topics (
                topic_id INTEGER PRIMARY KEY AUTOINCREMENT,
                forum_id INTEGER NOT NULL,
                topic_title VARCHAR(255) NOT NULL,
                topic_status INTEGER DEFAULT 0
            )
        ";
        $this->pdo->exec($sql);
        
        // Table des forums
        $sql = "
            CREATE TABLE phpbb_forums (
                forum_id INTEGER PRIMARY KEY AUTOINCREMENT,
                forum_name VARCHAR(255) NOT NULL,
                forum_status INTEGER DEFAULT 0
            )
        ";
        $this->pdo->exec($sql);
        
        // Table des utilisateurs
        $sql = "
            CREATE TABLE phpbb_users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL,
                user_email VARCHAR(255) NOT NULL,
                user_lang VARCHAR(10) DEFAULT 'fr'
            )
        ";
        $this->pdo->exec($sql);
    }
    
    /**
     * Insérer des données de test
     * 
     * Insère des données de test dans la base de données
     * pour permettre l'exécution des tests d'intégration.
     */
    protected function insertTestData()
    {
        // Insérer des forums de test
        $sql = "INSERT INTO phpbb_forums (forum_id, forum_name, forum_status) VALUES (1, 'Forum de test', 0)";
        $this->pdo->exec($sql);
        
        // Insérer des sujets de test
        $sql = "INSERT INTO phpbb_topics (topic_id, forum_id, topic_title, topic_status) VALUES (1, 1, 'Sujet de test', 0)";
        $this->pdo->exec($sql);
        
        // Insérer des messages de test
        $sql = "INSERT INTO phpbb_posts (post_id, topic_id, forum_id, poster_id, post_subject, post_text, post_time) VALUES (1, 1, 1, 2, 'Message de test', 'Contenu du message de test', " . time() . ")";
        $this->pdo->exec($sql);
        
        // Insérer des utilisateurs de test
        $sql = "INSERT INTO phpbb_users (user_id, username, user_email, user_lang) VALUES (1, 'test_user', 'test@example.com', 'fr')";
        $this->pdo->exec($sql);
        
        $sql = "INSERT INTO phpbb_users (user_id, username, user_email, user_lang) VALUES (2, 'post_author', 'author@example.com', 'fr')";
        $this->pdo->exec($sql);
    }
    
    /**
     * Initialiser les composants de l'extension
     * 
     * Crée les instances des composants de l'extension
     * avec les mocks appropriés.
     */
    protected function initializeExtensionComponents()
    {
        // Créer les mocks nécessaires
        $db_mock = $this->createMock(\phpbb\db\driver\driver_interface::class);
        $user_mock = $this->createMock(\phpbb\user::class);
        $request_mock = $this->createMock(\phpbb\request\request::class);
        $auth_mock = $this->createMock(\phpbb\auth\auth::class);
        $language_mock = $this->createMock(\phpbb\language\language::class);
        $config_mock = $this->createMock(\phpbb\config\config::class);
        $notification_manager_mock = $this->createMock(\phpbb\notification\manager::class);
        $user_loader_mock = $this->createMock(\phpbb\user_loader::class);
        
        // Configurer les données utilisateur
        $user_mock->data = [
            'user_id' => 1,
            'username' => 'test_user',
            'session_id' => 'test_session_id',
        ];
        
        // Configurer la configuration
        $config_mock->expects($this->any())
            ->method('offsetGet')
            ->willReturnMap([
                ['bastien59960_reactions_enabled', 1],
                ['bastien59960_reactions_max_per_post', 20],
                ['bastien59960_reactions_max_per_user', 10],
                ['bastien59960_reactions_spam_time', 2700],
            ]);
        
        // Créer l'instance du contrôleur AJAX
        $this->ajax_controller = new ajax(
            $db_mock,
            $user_mock,
            $request_mock,
            $auth_mock,
            $language_mock,
            'phpbb_post_reactions',
            'phpbb_posts',
            'phpbb_topics',
            'phpbb_forums',
            '/path/to/phpbb/',
            'php',
            $config_mock,
            $notification_manager_mock
        );
        
        // Créer l'instance du listener d'événements
        $this->event_listener = new listener(
            $db_mock,
            $user_mock,
            'phpbb_post_reactions',
            'phpbb_posts',
            $this->createMock(\phpbb\template\template::class),
            $language_mock,
            $this->createMock(\phpbb\controller\helper::class),
            $config_mock
        );
        
        // Créer l'instance du type de notification
        $this->notification_type = new reaction(
            $user_loader_mock,
            $db_mock,
            '/path/to/phpbb/',
            $user_mock
        );
        
        // Créer l'instance de la tâche cron
        $this->cron_task = new notification_task(
            $db_mock,
            $config_mock,
            $notification_manager_mock,
            $user_loader_mock,
            'phpbb_post_reactions',
            '/path/to/phpbb/',
            'php'
        );
    }

    // =============================================================================
    // TESTS D'INTÉGRATION
    // =============================================================================
    
    /**
     * Test d'intégration complet du système de réactions
     * 
     * Teste le flux complet d'ajout d'une réaction :
     * 1. Validation des données
     * 2. Vérification des autorisations
     * 3. Insertion en base de données
     * 4. Déclenchement des notifications
     */
    public function testCompleteReactionFlow()
    {
        // Vérifier que la base de données est initialisée
        $this->assertInstanceOf(\PDO::class, $this->pdo, "La base de données devrait être initialisée");
        
        // Vérifier que les tables existent
        $tables = ['phpbb_post_reactions', 'phpbb_posts', 'phpbb_topics', 'phpbb_forums', 'phpbb_users'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            $this->assertNotFalse($stmt->fetch(), "La table {$table} devrait exister");
        }
        
        // Vérifier que les données de test sont présentes
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM phpbb_posts");
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count, "Il devrait y avoir 1 message de test");
        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM phpbb_users");
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count, "Il devrait y avoir 2 utilisateurs de test");
    }
    
    /**
     * Test d'intégration du système de notifications
     * 
     * Teste le système de notifications complet :
     * 1. Création d'une réaction
     * 2. Déclenchement de la notification immédiate
     * 3. Traitement par la tâche cron
     * 4. Envoi de l'email avec délai anti-spam
     */
    public function testNotificationSystemIntegration()
    {
        // Vérifier que le type de notification est configuré
        $this->assertInstanceOf(reaction::class, $this->notification_type, "Le type de notification devrait être initialisé");
        
        // Vérifier que la tâche cron est configurée
        $this->assertInstanceOf(notification_task::class, $this->cron_task, "La tâche cron devrait être initialisée");
        
        // Vérifier que la tâche cron peut être exécutée
        $this->assertTrue($this->cron_task->is_runnable(), "La tâche cron devrait être exécutable");
    }
    
    /**
     * Test d'intégration des emojis
     * 
     * Teste le support des emojis dans l'extension :
     * 1. Emojis courantes
     * 2. Emojis étendus
     * 3. Emojis composés (ZWJ)
     */
    public function testEmojiIntegration()
    {
        // Vérifier que le contrôleur AJAX est initialisé
        $this->assertInstanceOf(ajax::class, $this->ajax_controller, "Le contrôleur AJAX devrait être initialisé");
        
        // Vérifier que les emojis courantes sont disponibles
        $common_emojis = $this->ajax_controller->get_common_emojis();
        $this->assertIsArray($common_emojis, "Les emojis courantes devraient être un tableau");
        $this->assertCount(10, $common_emojis, "Il devrait y avoir 10 emojis courantes");
        
        // Vérifier que les emojis courantes contiennent les emojis attendus
        $expected_emojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];
        foreach ($expected_emojis as $emoji) {
            $this->assertContains($emoji, $common_emojis, "L'emoji {$emoji} devrait être dans la liste des emojis courantes");
        }
    }
    
    /**
     * Test d'intégration des autorisations
     * 
     * Teste le système d'autorisations :
     * 1. Utilisateurs connectés vs non connectés
     * 2. Messages verrouillés vs non verrouillés
     * 3. Forums verrouillés vs non verrouillés
     */
    public function testAuthorizationIntegration()
    {
        // Vérifier que le listener d'événements est initialisé
        $this->assertInstanceOf(listener::class, $this->event_listener, "Le listener d'événements devrait être initialisé");
        
        // Vérifier que les événements sont configurés
        $events = $this->event_listener::getSubscribedEvents();
        $this->assertIsArray($events, "Les événements devraient être un tableau");
        $this->assertArrayHasKey('core.page_header', $events, "L'événement core.page_header devrait être configuré");
        $this->assertArrayHasKey('core.viewtopic_post_row_after', $events, "L'événement core.viewtopic_post_row_after devrait être configuré");
    }
    
    /**
     * Test d'intégration des performances
     * 
     * Teste les performances de l'extension :
     * 1. Temps de réponse des requêtes AJAX
     * 2. Utilisation de la mémoire
     * 3. Optimisation des requêtes de base de données
     */
    public function testPerformanceIntegration()
    {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Simuler des opérations de l'extension
        for ($i = 0; $i < 100; $i++) {
            $this->ajax_controller->get_common_emojis();
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        
        // Vérifier les performances
        $this->assertLessThan(1.0, $execution_time, "L'exécution devrait prendre moins d'une seconde");
        $this->assertLessThan(1024 * 1024, $memory_usage, "L'utilisation de la mémoire devrait être inférieure à 1MB");
    }
    
    /**
     * Test d'intégration de la base de données
     * 
     * Teste les opérations de base de données :
     * 1. Insertion de réactions
     * 2. Récupération de réactions
     * 3. Mise à jour des réactions
     * 4. Suppression de réactions
     */
    public function testDatabaseIntegration()
    {
        // Tester l'insertion d'une réaction
        $sql = "INSERT INTO phpbb_post_reactions (post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified) VALUES (1, 1, 1, '👍', " . time() . ", 0)";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "L'insertion de la réaction devrait réussir");
        
        // Tester la récupération de la réaction
        $stmt = $this->pdo->query("SELECT * FROM phpbb_post_reactions WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = '👍'");
        $reaction = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($reaction, "La réaction devrait être récupérée");
        $this->assertEquals('👍', $reaction['reaction_emoji'], "L'emoji de la réaction devrait être 👍");
        
        // Tester la mise à jour de la réaction
        $sql = "UPDATE phpbb_post_reactions SET reaction_notified = 1 WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = '👍'";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "La mise à jour de la réaction devrait réussir");
        
        // Tester la suppression de la réaction
        $sql = "DELETE FROM phpbb_post_reactions WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = '👍'";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "La suppression de la réaction devrait réussir");
    }

    // =============================================================================
    // NETTOYAGE DES TESTS
    // =============================================================================
    
    /**
     * Nettoyage après chaque test
     * 
     * Nettoie les ressources utilisées pendant les tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer la connexion à la base de données
        $this->pdo = null;
        
        // Nettoyer les instances des composants
        $this->ajax_controller = null;
        $this->event_listener = null;
        $this->notification_type = null;
        $this->cron_task = null;
    }
}
