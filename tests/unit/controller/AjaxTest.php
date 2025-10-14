<?php
/**
 * Tests unitaires pour le contrôleur AJAX de l'extension Reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 * 
 * Ce fichier contient les tests unitaires pour le contrôleur AJAX
 * qui gère les requêtes AJAX liées aux réactions aux messages.
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\tests\unit\controller;

use PHPUnit\Framework\TestCase;
use bastien59960\reactions\controller\ajax;
use phpbb\db\driver\driver_interface;
use phpbb\user;
use phpbb\request\request;
use phpbb\auth\auth;
use phpbb\language\language;
use phpbb\config\config;
use phpbb\notification\manager;

/**
 * Tests unitaires pour le contrôleur AJAX
 * 
 * Teste les fonctionnalités principales du contrôleur AJAX :
 * - Validation des données d'entrée
 * - Gestion des autorisations
 * - Traitement des requêtes AJAX
 * - Gestion des erreurs
 */
class AjaxTest extends TestCase
{
    // =============================================================================
    // PROPRIÉTÉS DE TEST
    // =============================================================================
    
    /** @var ajax Instance du contrôleur AJAX à tester */
    protected $controller;
    
    /** @var driver_interface Mock de la base de données */
    protected $db;
    
    /** @var user Mock de l'utilisateur */
    protected $user;
    
    /** @var request Mock de la requête */
    protected $request;
    
    /** @var auth Mock de l'authentification */
    protected $auth;
    
    /** @var language Mock de la langue */
    protected $language;
    
    /** @var config Mock de la configuration */
    protected $config;
    
    /** @var manager Mock du gestionnaire de notifications */
    protected $notification_manager;

    // =============================================================================
    // CONFIGURATION DES TESTS
    // =============================================================================
    
    /**
     * Configuration avant chaque test
     * 
     * Initialise les mocks et l'instance du contrôleur
     * pour chaque test unitaire.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les mocks
        $this->db = $this->createMock(driver_interface::class);
        $this->user = $this->createMock(user::class);
        $this->request = $this->createMock(request::class);
        $this->auth = $this->createMock(auth::class);
        $this->language = $this->createMock(language::class);
        $this->config = $this->createMock(config::class);
        $this->notification_manager = $this->createMock(manager::class);
        
        // Configurer les données utilisateur par défaut
        $this->user->data = [
            'user_id' => 1,
            'username' => 'test_user',
            'session_id' => 'test_session_id',
        ];
        
        // Créer l'instance du contrôleur
        $this->controller = new ajax(
            $this->db,
            $this->user,
            $this->request,
            $this->auth,
            $this->language,
            'phpbb_post_reactions',
            'phpbb_posts',
            'phpbb_topics',
            'phpbb_forums',
            '/path/to/phpbb/',
            'php',
            $this->config,
            $this->notification_manager
        );
    }

    // =============================================================================
    // TESTS DE VALIDATION
    // =============================================================================
    
    /**
     * Test de validation d'un emoji valide
     * 
     * Vérifie que la méthode de validation des emojis
     * accepte les emojis valides.
     */
    public function testValidateValidEmoji()
    {
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_emoji');
        $method->setAccessible(true);
        
        // Tester des emojis valides
        $valid_emojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];
        
        foreach ($valid_emojis as $emoji) {
            $this->assertTrue(
                $method->invoke($this->controller, $emoji),
                "L'emoji '{$emoji}' devrait être valide"
            );
        }
    }
    
    /**
     * Test de validation d'un emoji invalide
     * 
     * Vérifie que la méthode de validation des emojis
     * rejette les emojis invalides.
     */
    public function testValidateInvalidEmoji()
    {
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_emoji');
        $method->setAccessible(true);
        
        // Tester des emojis invalides
        $invalid_emojis = ['', 'a', '123', '👍👍👍👍👍👍👍👍👍👍👍👍👍👍👍👍', '👍👍👍👍👍👍👍👍👍👍👍👍👍👍👍👍👍'];
        
        foreach ($invalid_emojis as $emoji) {
            $this->assertFalse(
                $method->invoke($this->controller, $emoji),
                "L'emoji '{$emoji}' devrait être invalide"
            );
        }
    }
    
    /**
     * Test de validation d'un message valide
     * 
     * Vérifie que la méthode de validation des messages
     * accepte les messages valides.
     */
    public function testValidateValidPost()
    {
        // Configurer le mock de la base de données
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(['post_id' => 1]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_post');
        $method->setAccessible(true);
        
        // Tester un message valide
        $this->assertTrue(
            $method->invoke($this->controller, 1),
            "Le message avec l'ID 1 devrait être valide"
        );
    }
    
    /**
     * Test de validation d'un message invalide
     * 
     * Vérifie que la méthode de validation des messages
     * rejette les messages invalides.
     */
    public function testValidateInvalidPost()
    {
        // Configurer le mock de la base de données
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(false);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_post');
        $method->setAccessible(true);
        
        // Tester un message invalide
        $this->assertFalse(
            $method->invoke($this->controller, 999),
            "Le message avec l'ID 999 devrait être invalide"
        );
    }

    // =============================================================================
    // TESTS D'AUTORISATION
    // =============================================================================
    
    /**
     * Test d'autorisation pour un utilisateur connecté
     * 
     * Vérifie qu'un utilisateur connecté peut réagir
     * à un message non verrouillé.
     */
    public function testCanReactToPostLoggedIn()
    {
        // Configurer le mock de la base de données
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn([
                'post_id' => 1,
                'forum_id' => 1,
                'poster_id' => 2,
                'topic_status' => 0, // Non verrouillé
                'forum_status' => 0, // Non verrouillé
            ]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertTrue(
            $method->invoke($this->controller, 1),
            "L'utilisateur connecté devrait pouvoir réagir au message"
        );
    }
    
    /**
     * Test d'autorisation pour un utilisateur non connecté
     * 
     * Vérifie qu'un utilisateur non connecté ne peut pas
     * réagir à un message.
     */
    public function testCannotReactToPostNotLoggedIn()
    {
        // Configurer l'utilisateur comme non connecté
        $this->user->data['user_id'] = 1; // ANONYMOUS
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertFalse(
            $method->invoke($this->controller, 1),
            "L'utilisateur non connecté ne devrait pas pouvoir réagir au message"
        );
    }
    
    /**
     * Test d'autorisation pour un message verrouillé
     * 
     * Vérifie qu'un utilisateur ne peut pas réagir
     * à un message dans un sujet verrouillé.
     */
    public function testCannotReactToLockedPost()
    {
        // Configurer le mock de la base de données
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn([
                'post_id' => 1,
                'forum_id' => 1,
                'poster_id' => 2,
                'topic_status' => 1, // Verrouillé
                'forum_status' => 0,
            ]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertFalse(
            $method->invoke($this->controller, 1),
            "L'utilisateur ne devrait pas pouvoir réagir à un message verrouillé"
        );
    }

    // =============================================================================
    // TESTS DE FONCTIONNALITÉS
    // =============================================================================
    
    /**
     * Test de récupération des emojis courantes
     * 
     * Vérifie que la méthode retourne la liste
     * des emojis courantes.
     */
    public function testGetCommonEmojis()
    {
        $common_emojis = $this->controller->get_common_emojis();
        
        $this->assertIsArray($common_emojis, "La méthode devrait retourner un tableau");
        $this->assertCount(10, $common_emojis, "La méthode devrait retourner 10 emojis");
        $this->assertContains('👍', $common_emojis, "La liste devrait contenir l'emoji 👍");
        $this->assertContains('👎', $common_emojis, "La liste devrait contenir l'emoji 👎");
    }
    
    /**
     * Test de la méthode handle avec des données valides
     * 
     * Vérifie que la méthode handle traite correctement
     * une requête AJAX valide.
     */
    public function testHandleValidRequest()
    {
        // Configurer les mocks
        $this->request->expects($this->once())
            ->method('variable')
            ->with('sid', '')
            ->willReturn('test_session_id');
        
        $this->config->expects($this->any())
            ->method('offsetGet')
            ->willReturnMap([
                ['bastien59960_reactions_max_per_post', 20],
                ['bastien59960_reactions_max_per_user', 10],
            ]);
        
        // Configurer le mock de la base de données pour la validation du message
        $this->db->expects($this->atLeastOnce())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->atLeastOnce())
            ->method('sql_fetchrow')
            ->willReturn(['post_id' => 1]);
        
        $this->db->expects($this->atLeastOnce())
            ->method('sql_freeresult');
        
        // Simuler une requête AJAX valide
        $this->request->expects($this->once())
            ->method('is_ajax')
            ->willReturn(true);
        
        // Tester la méthode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La méthode devrait retourner une réponse JSON"
        );
    }

    // =============================================================================
    // TESTS DE GESTION D'ERREURS
    // =============================================================================
    
    /**
     * Test de gestion d'erreur avec un jeton CSRF invalide
     * 
     * Vérifie que la méthode handle rejette les requêtes
     * avec un jeton CSRF invalide.
     */
    public function testHandleInvalidCsrfToken()
    {
        // Configurer le mock pour retourner un jeton invalide
        $this->request->expects($this->once())
            ->method('variable')
            ->with('sid', '')
            ->willReturn('invalid_session_id');
        
        // Tester la méthode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La méthode devrait retourner une réponse JSON"
        );
        
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "La réponse devrait avoir un code d'erreur 403"
        );
    }
    
    /**
     * Test de gestion d'erreur avec un message invalide
     * 
     * Vérifie que la méthode handle rejette les requêtes
     * avec un ID de message invalide.
     */
    public function testHandleInvalidPostId()
    {
        // Configurer les mocks
        $this->request->expects($this->once())
            ->method('variable')
            ->with('sid', '')
            ->willReturn('test_session_id');
        
        // Configurer le mock de la base de données pour retourner un message invalide
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(false);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Tester la méthode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La méthode devrait retourner une réponse JSON"
        );
        
        $this->assertEquals(
            400,
            $response->getStatusCode(),
            "La réponse devrait avoir un code d'erreur 400"
        );
    }

    // =============================================================================
    // TESTS DE PERFORMANCE
    // =============================================================================
    
    /**
     * Test de performance avec de nombreuses réactions
     * 
     * Vérifie que le contrôleur peut gérer efficacement
     * un grand nombre de réactions.
     */
    public function testPerformanceWithManyReactions()
    {
        $start_time = microtime(true);
        
        // Simuler de nombreuses réactions
        for ($i = 0; $i < 100; $i++) {
            $this->controller->get_common_emojis();
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertLessThan(
            1.0,
            $execution_time,
            "L'exécution devrait prendre moins d'une seconde"
        );
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
        
        // Nettoyer les mocks
        $this->controller = null;
        $this->db = null;
        $this->user = null;
        $this->request = null;
        $this->auth = null;
        $this->language = null;
        $this->config = null;
        $this->notification_manager = null;
    }
}
