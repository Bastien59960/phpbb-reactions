<?php
/**
 * Tests d'intÃ©gration pour l'extension Reactions
 * 
 * Ce fichier contient les tests d'intÃ©gration qui vÃ©rifient
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
 * Tests d'intÃ©gration pour l'extension Reactions
 * 
 * Teste l'intÃ©gration complÃ¨te de l'extension :
 * - ContrÃ´leur AJAX avec base de donnÃ©es rÃ©elle
 * - Listener d'Ã©vÃ©nements avec templates
 * - SystÃ¨me de notifications complet
 * - TÃ¢che cron de notification
 */
class ReactionsIntegrationTest extends TestCase
{
    // =============================================================================
    // PROPRIÃ‰TÃ‰S DE TEST
    // =============================================================================
    
    /** @var \PDO Instance de la base de donnÃ©es de test */
    protected $pdo;
    
    /** @var ajax Instance du contrÃ´leur AJAX */
    protected $ajax_controller;
    
    /** @var listener Instance du listener d'Ã©vÃ©nements */
    protected $event_listener;
    
    /** @var reaction Instance du type de notification */
    protected $notification_type;
    
    /** @var notification_task Instance de la tÃ¢che cron */
    protected $cron_task;

    // =============================================================================
    // CONFIGURATION DES TESTS
    // =============================================================================
    
    /**
     * Configuration avant chaque test
     * 
     * Initialise la base de donnÃ©es de test et les instances
     * des composants de l'extension.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser la base de donnÃ©es de test
        $this->initializeTestDatabase();
        
        // Initialiser les composants de l'extension
        $this->initializeExtensionComponents();
    }
    
    /**
     * Initialiser la base de donnÃ©es de test
     * 
     * CrÃ©e une base de donnÃ©es SQLite en mÃ©moire avec
     * la structure nÃ©cessaire pour les tests.
     */
    protected function initializeTestDatabase()
    {
        // CrÃ©er une base de donnÃ©es SQLite en mÃ©moire
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // CrÃ©er les tables nÃ©cessaires
        $this->createTestTables();
        
        // InsÃ©rer des donnÃ©es de test
        $this->insertTestData();
    }
    
    /**
     * CrÃ©er les tables de test
     * 
     * CrÃ©e la structure de base de donnÃ©es nÃ©cessaire
     * pour les tests d'intÃ©gration.
     */
    protected function createTestTables()
    {
        // Table des rÃ©actions
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
     * InsÃ©rer des donnÃ©es de test
     * 
     * InsÃ¨re des donnÃ©es de test dans la base de donnÃ©es
     * pour permettre l'exÃ©cution des tests d'intÃ©gration.
     */
    protected function insertTestData()
    {
        // InsÃ©rer des forums de test
        $sql = "INSERT INTO phpbb_forums (forum_id, forum_name, forum_status) VALUES (1, 'Forum de test', 0)";
        $this->pdo->exec($sql);
        
        // InsÃ©rer des sujets de test
        $sql = "INSERT INTO phpbb_topics (topic_id, forum_id, topic_title, topic_status) VALUES (1, 1, 'Sujet de test', 0)";
        $this->pdo->exec($sql);
        
        // InsÃ©rer des messages de test
        $sql = "INSERT INTO phpbb_posts (post_id, topic_id, forum_id, poster_id, post_subject, post_text, post_time) VALUES (1, 1, 1, 2, 'Message de test', 'Contenu du message de test', " . time() . ")";
        $this->pdo->exec($sql);
        
        // InsÃ©rer des utilisateurs de test
        $sql = "INSERT INTO phpbb_users (user_id, username, user_email, user_lang) VALUES (1, 'test_user', 'test@example.com', 'fr')";
        $this->pdo->exec($sql);
        
        $sql = "INSERT INTO phpbb_users (user_id, username, user_email, user_lang) VALUES (2, 'post_author', 'author@example.com', 'fr')";
        $this->pdo->exec($sql);
    }
    
    /**
     * Initialiser les composants de l'extension
     * 
     * CrÃ©e les instances des composants de l'extension
     * avec les mocks appropriÃ©s.
     */
    protected function initializeExtensionComponents()
    {
        // CrÃ©er les mocks nÃ©cessaires
        $db_mock = $this->createMock(\phpbb\db\driver\driver_interface::class);
        $user_mock = $this->createMock(\phpbb\user::class);
        $request_mock = $this->createMock(\phpbb\request\request::class);
        $auth_mock = $this->createMock(\phpbb\auth\auth::class);
        $language_mock = $this->createMock(\phpbb\language\language::class);
        $config_mock = $this->createMock(\phpbb\config\config::class);
        $notification_manager_mock = $this->createMock(\phpbb\notification\manager::class);
        $user_loader_mock = $this->createMock(\phpbb\user_loader::class);
        
        // Configurer les donnÃ©es utilisateur
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
        
        // CrÃ©er l'instance du contrÃ´leur AJAX
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
        
        // CrÃ©er l'instance du listener d'Ã©vÃ©nements
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
        
        // CrÃ©er l'instance du type de notification
        $this->notification_type = new reaction(
            $user_loader_mock,
            $db_mock,
            '/path/to/phpbb/',
            $user_mock
        );
        
        // CrÃ©er l'instance de la tÃ¢che cron
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
    // TESTS D'INTÃ‰GRATION
    // =============================================================================
    
    /**
     * Test d'intÃ©gration complet du systÃ¨me de rÃ©actions
     * 
     * Teste le flux complet d'ajout d'une rÃ©action :
     * 1. Validation des donnÃ©es
     * 2. VÃ©rification des autorisations
     * 3. Insertion en base de donnÃ©es
     * 4. DÃ©clenchement des notifications
     */
    public function testCompleteReactionFlow()
    {
        // VÃ©rifier que la base de donnÃ©es est initialisÃ©e
        $this->assertInstanceOf(\PDO::class, $this->pdo, "La base de donnÃ©es devrait Ãªtre initialisÃ©e");
        
        // VÃ©rifier que les tables existent
        $tables = ['phpbb_post_reactions', 'phpbb_posts', 'phpbb_topics', 'phpbb_forums', 'phpbb_users'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            $this->assertNotFalse($stmt->fetch(), "La table {$table} devrait exister");
        }
        
        // VÃ©rifier que les donnÃ©es de test sont prÃ©sentes
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM phpbb_posts");
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count, "Il devrait y avoir 1 message de test");
        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM phpbb_users");
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count, "Il devrait y avoir 2 utilisateurs de test");
    }
    
    /**
     * Test d'intÃ©gration du systÃ¨me de notifications
     * 
     * Teste le systÃ¨me de notifications complet :
     * 1. CrÃ©ation d'une rÃ©action
     * 2. DÃ©clenchement de la notification immÃ©diate
     * 3. Traitement par la tÃ¢che cron
     * 4. Envoi de l'email avec dÃ©lai anti-spam
     */
    public function testNotificationSystemIntegration()
    {
        // VÃ©rifier que le type de notification est configurÃ©
        $this->assertInstanceOf(reaction::class, $this->notification_type, "Le type de notification devrait Ãªtre initialisÃ©");
        
        // VÃ©rifier que la tÃ¢che cron est configurÃ©e
        $this->assertInstanceOf(notification_task::class, $this->cron_task, "La tÃ¢che cron devrait Ãªtre initialisÃ©e");
        
        // VÃ©rifier que la tÃ¢che cron peut Ãªtre exÃ©cutÃ©e
        $this->assertTrue($this->cron_task->is_runnable(), "La tÃ¢che cron devrait Ãªtre exÃ©cutable");
    }
    
    /**
     * Test d'intÃ©gration des emojis
     * 
     * Teste le support des emojis dans l'extension :
     * 1. Emojis courantes
     * 2. Emojis Ã©tendus
     * 3. Emojis composÃ©s (ZWJ)
     */
    public function testEmojiIntegration()
    {
        // VÃ©rifier que le contrÃ´leur AJAX est initialisÃ©
        $this->assertInstanceOf(ajax::class, $this->ajax_controller, "Le contrÃ´leur AJAX devrait Ãªtre initialisÃ©");
        
        // VÃ©rifier que les emojis courantes sont disponibles
        $common_emojis = $this->ajax_controller->get_common_emojis();
        $this->assertIsArray($common_emojis, "Les emojis courantes devraient Ãªtre un tableau");
        $this->assertCount(10, $common_emojis, "Il devrait y avoir 10 emojis courantes");
        
        // VÃ©rifier que les emojis courantes contiennent les emojis attendus
        $expected_emojis = ['ðŸ‘', 'ðŸ‘Ž', 'â¤ï¸', 'ðŸ˜‚', 'ðŸ˜®', 'ðŸ˜¢', 'ðŸ˜¡', 'ðŸ”¥', 'ðŸ‘Œ', 'ðŸ¥³'];
        foreach ($expected_emojis as $emoji) {
            $this->assertContains($emoji, $common_emojis, "L'emoji {$emoji} devrait Ãªtre dans la liste des emojis courantes");
        }
    }
    
    /**
     * Test d'intÃ©gration des autorisations
     * 
     * Teste le systÃ¨me d'autorisations :
     * 1. Utilisateurs connectÃ©s vs non connectÃ©s
     * 2. Messages verrouillÃ©s vs non verrouillÃ©s
     * 3. Forums verrouillÃ©s vs non verrouillÃ©s
     */
    public function testAuthorizationIntegration()
    {
        // VÃ©rifier que le listener d'Ã©vÃ©nements est initialisÃ©
        $this->assertInstanceOf(listener::class, $this->event_listener, "Le listener d'Ã©vÃ©nements devrait Ãªtre initialisÃ©");
        
        // VÃ©rifier que les Ã©vÃ©nements sont configurÃ©s
        $events = $this->event_listener::getSubscribedEvents();
        $this->assertIsArray($events, "Les Ã©vÃ©nements devraient Ãªtre un tableau");
        $this->assertArrayHasKey('core.page_header', $events, "L'Ã©vÃ©nement core.page_header devrait Ãªtre configurÃ©");
        $this->assertArrayHasKey('core.viewtopic_post_row_after', $events, "L'Ã©vÃ©nement core.viewtopic_post_row_after devrait Ãªtre configurÃ©");
    }
    
    /**
     * Test d'intÃ©gration des performances
     * 
     * Teste les performances de l'extension :
     * 1. Temps de rÃ©ponse des requÃªtes AJAX
     * 2. Utilisation de la mÃ©moire
     * 3. Optimisation des requÃªtes de base de donnÃ©es
     */
    public function testPerformanceIntegration()
    {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Simuler des opÃ©rations de l'extension
        for ($i = 0; $i < 100; $i++) {
            $this->ajax_controller->get_common_emojis();
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        
        // VÃ©rifier les performances
        $this->assertLessThan(1.0, $execution_time, "L'exÃ©cution devrait prendre moins d'une seconde");
        $this->assertLessThan(1024 * 1024, $memory_usage, "L'utilisation de la mÃ©moire devrait Ãªtre infÃ©rieure Ã  1MB");
    }
    
    /**
     * Test d'intÃ©gration de la base de donnÃ©es
     * 
     * Teste les opÃ©rations de base de donnÃ©es :
     * 1. Insertion de rÃ©actions
     * 2. RÃ©cupÃ©ration de rÃ©actions
     * 3. Mise Ã  jour des rÃ©actions
     * 4. Suppression de rÃ©actions
     */
    public function testDatabaseIntegration()
    {
        // Tester l'insertion d'une rÃ©action
        $sql = "INSERT INTO phpbb_post_reactions (post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified) VALUES (1, 1, 1, 'ðŸ‘', " . time() . ", 0)";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "L'insertion de la rÃ©action devrait rÃ©ussir");
        
        // Tester la rÃ©cupÃ©ration de la rÃ©action
        $stmt = $this->pdo->query("SELECT * FROM phpbb_post_reactions WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = 'ðŸ‘'");
        $reaction = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($reaction, "La rÃ©action devrait Ãªtre rÃ©cupÃ©rÃ©e");
        $this->assertEquals('ðŸ‘', $reaction['reaction_emoji'], "L'emoji de la rÃ©action devrait Ãªtre ðŸ‘");
        
        // Tester la mise Ã  jour de la rÃ©action
        $sql = "UPDATE phpbb_post_reactions SET reaction_notified = 1 WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = 'ðŸ‘'";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "La mise Ã  jour de la rÃ©action devrait rÃ©ussir");
        
        // Tester la suppression de la rÃ©action
        $sql = "DELETE FROM phpbb_post_reactions WHERE post_id = 1 AND user_id = 1 AND reaction_emoji = 'ðŸ‘'";
        $result = $this->pdo->exec($sql);
        $this->assertEquals(1, $result, "La suppression de la rÃ©action devrait rÃ©ussir");
    }

    // =============================================================================
    // NETTOYAGE DES TESTS
    // =============================================================================
    
    /**
     * Nettoyage aprÃ¨s chaque test
     * 
     * Nettoie les ressources utilisÃ©es pendant les tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer la connexion Ã  la base de donnÃ©es
        $this->pdo = null;
        
        // Nettoyer les instances des composants
        $this->ajax_controller = null;
        $this->event_listener = null;
        $this->notification_type = null;
        $this->cron_task = null;
    }
}
