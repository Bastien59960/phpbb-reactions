# Tests de l'extension Reactions

Ce répertoire contient tous les tests pour l'extension Reactions, incluant les tests unitaires, d'intégration et de fonctionnalité.

## Structure des tests

```
tests/
├── bootstrap.php                    # Bootstrap pour les tests
├── phpunit.xml                     # Configuration PHPUnit
├── README.md                       # Ce fichier
├── unit/                           # Tests unitaires
│   └── controller/
│       └── AjaxTest.php           # Tests du contrôleur AJAX
├── integration/                    # Tests d'intégration
│   └── ReactionsIntegrationTest.php # Tests d'intégration complets
├── functional/                     # Tests de fonctionnalité
├── logs/                          # Logs des tests
├── cache/                         # Cache des tests
└── temp/                          # Fichiers temporaires
```

## Types de tests

### Tests unitaires
- **Objectif** : Tester les composants individuels de l'extension
- **Couverture** : Classes, méthodes, fonctions
- **Isolation** : Chaque test est indépendant
- **Mocks** : Utilisation de mocks pour les dépendances

### Tests d'intégration
- **Objectif** : Tester l'interaction entre les composants
- **Couverture** : Flux de données, intégration base de données
- **Environnement** : Base de données de test, services réels
- **Données** : Données de test réalistes

### Tests de fonctionnalité
- **Objectif** : Tester les fonctionnalités complètes
- **Couverture** : Cas d'usage utilisateur, scénarios complets
- **Environnement** : Environnement proche de la production
- **Interface** : Tests d'interface utilisateur

## Exécution des tests

### Prérequis
```bash
# Installer les dépendances de test
composer install --dev

# Installer PHPUnit
composer require --dev phpunit/phpunit
```

### Commandes de test
```bash
# Tous les tests
phpunit

# Tests unitaires uniquement
phpunit tests/unit/

# Tests d'intégration uniquement
phpunit tests/integration/

# Tests de fonctionnalité uniquement
phpunit tests/functional/

# Avec couverture de code
phpunit --coverage-html coverage/

# Tests spécifiques
phpunit tests/unit/controller/AjaxTest.php
```

### Configuration
```bash
# Variables d'environnement
export APP_ENV=test
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# Configuration PHP
php -d memory_limit=512M -d max_execution_time=300
```

## Écriture de tests

### Structure d'un test unitaire
```php
<?php
namespace bastien59960\reactions\tests\unit;

use PHPUnit\Framework\TestCase;
use bastien59960\reactions\MaClasse;

class MaClasseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Configuration avant chaque test
    }
    
    public function testMaMethode()
    {
        // Arrange
        $classe = new MaClasse();
        
        // Act
        $resultat = $classe->maMethode('param');
        
        // Assert
        $this->assertEquals('attendu', $resultat);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Nettoyage après chaque test
    }
}
```

### Structure d'un test d'intégration
```php
<?php
namespace bastien59960\reactions\tests\integration;

use PHPUnit\Framework\TestCase;

class MonTestIntegration extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialisation de la base de données de test
        $this->initializeTestDatabase();
    }
    
    public function testFluxComplet()
    {
        // Test du flux complet
        $this->assertTrue(true);
    }
}
```

## Couverture de code

### Objectifs de couverture
- **Lignes de code** : > 80%
- **Branches** : > 70%
- **Fonctions** : > 90%
- **Classes** : > 85%

### Génération du rapport
```bash
# Rapport HTML
phpunit --coverage-html coverage/

# Rapport texte
phpunit --coverage-text

# Rapport XML
phpunit --coverage-clover coverage/clover.xml
```

### Exclusions
Les fichiers suivants sont exclus de la couverture :
- `vendor/` - Dépendances externes
- `tests/` - Code de test
- `composer.json` - Configuration
- `README.md` - Documentation
- `CHANGELOG.md` - Historique
- `DOCUMENTATION.md` - Documentation
- `GUIDE_DEVELOPPEMENT.md` - Guide de développement
- `GUIDE_INSTALLATION.md` - Guide d'installation

## Données de test

### Base de données de test
- **Type** : SQLite en mémoire
- **Tables** : Structure complète de l'extension
- **Données** : Données de test réalistes
- **Isolation** : Base de données fraîche pour chaque test

### Fichiers de test
- **Emojis** : Fichiers JSON de test
- **Templates** : Templates de test
- **CSS** : Styles de test
- **JavaScript** : Scripts de test

## Debug et diagnostic

### Logs de test
```bash
# Logs PHP
tail -f tests/logs/php_errors.log

# Logs PHPUnit
tail -f tests/logs/junit.xml

# Logs de couverture
tail -f tests/logs/coverage.log
```

### Mode debug
```php
// Activer le mode debug
define('PHPBB_TESTING', true);
define('PHPBB_DEBUG', true);

// Logs détaillés
error_log('[Test] Message de debug');
```

### Outils de debug
- **Xdebug** : Débogueur PHP
- **PHPUnit** : Outils de test intégrés
- **Coverage** : Analyse de couverture
- **Profiler** : Analyse de performance

## Bonnes pratiques

### Écriture de tests
- **Nommage** : Noms descriptifs et clairs
- **Structure** : Arrange-Act-Assert
- **Isolation** : Tests indépendants
- **Données** : Données de test minimales
- **Assertions** : Assertions spécifiques

### Maintenance
- **Mise à jour** : Tests à jour avec le code
- **Refactoring** : Refactoring des tests
- **Documentation** : Documentation des tests
- **Performance** : Optimisation des tests

### Intégration continue
- **Automatisation** : Tests automatiques
- **Rapports** : Rapports de test
- **Notifications** : Notifications d'échec
- **Déploiement** : Tests avant déploiement

## Dépannage

### Problèmes courants
- **Mémoire** : Augmenter la limite de mémoire
- **Temps** : Augmenter le temps d'exécution
- **Base de données** : Vérifier la configuration
- **Permissions** : Vérifier les permissions de fichiers

### Solutions
```bash
# Problème de mémoire
php -d memory_limit=1G phpunit

# Problème de temps
php -d max_execution_time=600 phpunit

# Problème de base de données
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# Problème de permissions
chmod -R 755 tests/
```

## Ressources

### Documentation
- [PHPUnit](https://phpunit.de/documentation.html)
- [PHPUnit Best Practices](https://phpunit.de/best-practices.html)
- [Testing PHP Applications](https://phpunit.de/testing-php-applications.html)

### Outils
- [PHPUnit](https://phpunit.de/)
- [Xdebug](https://xdebug.org/)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPStan](https://phpstan.org/)

### Communauté
- [PHPUnit GitHub](https://github.com/sebastianbergmann/phpunit)
- [PHP Testing Community](https://www.php.net/manual/en/intro.pdo.php)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/phpunit)

---

*Cette documentation est maintenue à jour avec chaque version de l'extension.*
