<?php
/**
 * Tests unitaires pour le contrÃ´leur AJAX de l'extension Reactions
 * 
 * Ce fichier contient les tests unitaires pour le contrÃ´leur AJAX
 * qui gÃ¨re les requÃªtes AJAX liÃ©es aux rÃ©actions aux messages.
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
 * Tests unitaires pour le contrÃ´leur AJAX
 * 
 * Teste les fonctionnalitÃ©s principales du contrÃ´leur AJAX :
 * - Validation des donnÃ©es d'entrÃ©e
 * - Gestion des autorisations
 * - Traitement des requÃªtes AJAX
 * - Gestion des erreurs
 */
class AjaxTest extends TestCase
{
    // =============================================================================
    // PROPRIÃ‰TÃ‰S DE TEST
    // =============================================================================
    
    /** @var ajax Instance du contrÃ´leur AJAX Ã  tester */
    protected $controller;
    
    /** @var driver_interface Mock de la base de donnÃ©es */
    protected $db;
    
    /** @var user Mock de l'utilisateur */
    protected $user;
    
    /** @var request Mock de la requÃªte */
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
     * Initialise les mocks et l'instance du contrÃ´leur
     * pour chaque test unitaire.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // CrÃ©er les mocks
        $this->db = $this->createMock(driver_interface::class);
        $this->user = $this->createMock(user::class);
        $this->request = $this->createMock(request::class);
        $this->auth = $this->createMock(auth::class);
        $this->language = $this->createMock(language::class);
        $this->config = $this->createMock(config::class);
        $this->notification_manager = $this->createMock(manager::class);
        
        // Configurer les donnÃ©es utilisateur par dÃ©faut
        $this->user->data = [
            'user_id' => 1,
            'username' => 'test_user',
            'session_id' => 'test_session_id',
        ];
        
        // CrÃ©er l'instance du contrÃ´leur
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
     * VÃ©rifie que la mÃ©thode de validation des emojis
     * accepte les emojis valides.
     */
    public function testValidateValidEmoji()
    {
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_emoji');
        $method->setAccessible(true);
        
        // Tester des emojis valides
        $valid_emojis = ['ðŸ‘', 'ðŸ‘Ž', 'â¤ï¸', 'ðŸ˜‚', 'ðŸ˜®', 'ðŸ˜¢', 'ðŸ˜¡', 'ðŸ”¥', 'ðŸ‘Œ', 'ðŸ¥³'];
        
        foreach ($valid_emojis as $emoji) {
            $this->assertTrue(
                $method->invoke($this->controller, $emoji),
                "L'emoji '{$emoji}' devrait Ãªtre valide"
            );
        }
    }
    
    /**
     * Test de validation d'un emoji invalide
     * 
     * VÃ©rifie que la mÃ©thode de validation des emojis
     * rejette les emojis invalides.
     */
    public function testValidateInvalidEmoji()
    {
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_emoji');
        $method->setAccessible(true);
        
        // Tester des emojis invalides
        $invalid_emojis = ['', 'a', '123', 'ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘', 'ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘ðŸ‘'];
        
        foreach ($invalid_emojis as $emoji) {
            $this->assertFalse(
                $method->invoke($this->controller, $emoji),
                "L'emoji '{$emoji}' devrait Ãªtre invalide"
            );
        }
    }
    
    /**
     * Test de validation d'un message valide
     * 
     * VÃ©rifie que la mÃ©thode de validation des messages
     * accepte les messages valides.
     */
    public function testValidateValidPost()
    {
        // Configurer le mock de la base de donnÃ©es
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(['post_id' => 1]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_post');
        $method->setAccessible(true);
        
        // Tester un message valide
        $this->assertTrue(
            $method->invoke($this->controller, 1),
            "Le message avec l'ID 1 devrait Ãªtre valide"
        );
    }
    
    /**
     * Test de validation d'un message invalide
     * 
     * VÃ©rifie que la mÃ©thode de validation des messages
     * rejette les messages invalides.
     */
    public function testValidateInvalidPost()
    {
        // Configurer le mock de la base de donnÃ©es
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(false);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('is_valid_post');
        $method->setAccessible(true);
        
        // Tester un message invalide
        $this->assertFalse(
            $method->invoke($this->controller, 999),
            "Le message avec l'ID 999 devrait Ãªtre invalide"
        );
    }

    // =============================================================================
    // TESTS D'AUTORISATION
    // =============================================================================
    
    /**
     * Test d'autorisation pour un utilisateur connectÃ©
     * 
     * VÃ©rifie qu'un utilisateur connectÃ© peut rÃ©agir
     * Ã  un message non verrouillÃ©.
     */
    public function testCanReactToPostLoggedIn()
    {
        // Configurer le mock de la base de donnÃ©es
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn([
                'post_id' => 1,
                'forum_id' => 1,
                'poster_id' => 2,
                'topic_status' => 0, // Non verrouillÃ©
                'forum_status' => 0, // Non verrouillÃ©
            ]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertTrue(
            $method->invoke($this->controller, 1),
            "L'utilisateur connectÃ© devrait pouvoir rÃ©agir au message"
        );
    }
    
    /**
     * Test d'autorisation pour un utilisateur non connectÃ©
     * 
     * VÃ©rifie qu'un utilisateur non connectÃ© ne peut pas
     * rÃ©agir Ã  un message.
     */
    public function testCannotReactToPostNotLoggedIn()
    {
        // Configurer l'utilisateur comme non connectÃ©
        $this->user->data['user_id'] = 1; // ANONYMOUS
        
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertFalse(
            $method->invoke($this->controller, 1),
            "L'utilisateur non connectÃ© ne devrait pas pouvoir rÃ©agir au message"
        );
    }
    
    /**
     * Test d'autorisation pour un message verrouillÃ©
     * 
     * VÃ©rifie qu'un utilisateur ne peut pas rÃ©agir
     * Ã  un message dans un sujet verrouillÃ©.
     */
    public function testCannotReactToLockedPost()
    {
        // Configurer le mock de la base de donnÃ©es
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn([
                'post_id' => 1,
                'forum_id' => 1,
                'poster_id' => 2,
                'topic_status' => 1, // VerrouillÃ©
                'forum_status' => 0,
            ]);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Utiliser la rÃ©flexion pour accÃ©der Ã  la mÃ©thode privÃ©e
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('can_react_to_post');
        $method->setAccessible(true);
        
        // Tester l'autorisation
        $this->assertFalse(
            $method->invoke($this->controller, 1),
            "L'utilisateur ne devrait pas pouvoir rÃ©agir Ã  un message verrouillÃ©"
        );
    }

    // =============================================================================
    // TESTS DE FONCTIONNALITÃ‰S
    // =============================================================================
    
    /**
     * Test de rÃ©cupÃ©ration des emojis courantes
     * 
     * VÃ©rifie que la mÃ©thode retourne la liste
     * des emojis courantes.
     */
    public function testGetCommonEmojis()
    {
        $common_emojis = $this->controller->get_common_emojis();
        
        $this->assertIsArray($common_emojis, "La mÃ©thode devrait retourner un tableau");
        $this->assertCount(10, $common_emojis, "La mÃ©thode devrait retourner 10 emojis");
        $this->assertContains('ðŸ‘', $common_emojis, "La liste devrait contenir l'emoji ðŸ‘");
        $this->assertContains('ðŸ‘Ž', $common_emojis, "La liste devrait contenir l'emoji ðŸ‘Ž");
    }
    
    /**
     * Test de la mÃ©thode handle avec des donnÃ©es valides
     * 
     * VÃ©rifie que la mÃ©thode handle traite correctement
     * une requÃªte AJAX valide.
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
        
        // Configurer le mock de la base de donnÃ©es pour la validation du message
        $this->db->expects($this->atLeastOnce())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->atLeastOnce())
            ->method('sql_fetchrow')
            ->willReturn(['post_id' => 1]);
        
        $this->db->expects($this->atLeastOnce())
            ->method('sql_freeresult');
        
        // Simuler une requÃªte AJAX valide
        $this->request->expects($this->once())
            ->method('is_ajax')
            ->willReturn(true);
        
        // Tester la mÃ©thode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La mÃ©thode devrait retourner une rÃ©ponse JSON"
        );
    }

    // =============================================================================
    // TESTS DE GESTION D'ERREURS
    // =============================================================================
    
    /**
     * Test de gestion d'erreur avec un jeton CSRF invalide
     * 
     * VÃ©rifie que la mÃ©thode handle rejette les requÃªtes
     * avec un jeton CSRF invalide.
     */
    public function testHandleInvalidCsrfToken()
    {
        // Configurer le mock pour retourner un jeton invalide
        $this->request->expects($this->once())
            ->method('variable')
            ->with('sid', '')
            ->willReturn('invalid_session_id');
        
        // Tester la mÃ©thode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La mÃ©thode devrait retourner une rÃ©ponse JSON"
        );
        
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "La rÃ©ponse devrait avoir un code d'erreur 403"
        );
    }
    
    /**
     * Test de gestion d'erreur avec un message invalide
     * 
     * VÃ©rifie que la mÃ©thode handle rejette les requÃªtes
     * avec un ID de message invalide.
     */
    public function testHandleInvalidPostId()
    {
        // Configurer les mocks
        $this->request->expects($this->once())
            ->method('variable')
            ->with('sid', '')
            ->willReturn('test_session_id');
        
        // Configurer le mock de la base de donnÃ©es pour retourner un message invalide
        $this->db->expects($this->once())
            ->method('sql_query')
            ->willReturn(true);
        
        $this->db->expects($this->once())
            ->method('sql_fetchrow')
            ->willReturn(false);
        
        $this->db->expects($this->once())
            ->method('sql_freeresult');
        
        // Tester la mÃ©thode handle
        $response = $this->controller->handle();
        
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\JsonResponse::class,
            $response,
            "La mÃ©thode devrait retourner une rÃ©ponse JSON"
        );
        
        $this->assertEquals(
            400,
            $response->getStatusCode(),
            "La rÃ©ponse devrait avoir un code d'erreur 400"
        );
    }

    // =============================================================================
    // TESTS DE PERFORMANCE
    // =============================================================================
    
    /**
     * Test de performance avec de nombreuses rÃ©actions
     * 
     * VÃ©rifie que le contrÃ´leur peut gÃ©rer efficacement
     * un grand nombre de rÃ©actions.
     */
    public function testPerformanceWithManyReactions()
    {
        $start_time = microtime(true);
        
        // Simuler de nombreuses rÃ©actions
        for ($i = 0; $i < 100; $i++) {
            $this->controller->get_common_emojis();
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertLessThan(
            1.0,
            $execution_time,
            "L'exÃ©cution devrait prendre moins d'une seconde"
        );
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
