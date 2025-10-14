# Tests de l'extension Reactions

Ce rÃ©pertoire contient tous les tests pour l'extension Reactions, incluant les tests unitaires, d'intÃ©gration et de fonctionnalitÃ©.

## Structure des tests

```
tests/
â”œâ”€â”€ bootstrap.php                    # Bootstrap pour les tests
â”œâ”€â”€ phpunit.xml                     # Configuration PHPUnit
â”œâ”€â”€ README.md                       # Ce fichier
â”œâ”€â”€ unit/                           # Tests unitaires
â”‚   â””â”€â”€ controller/
â”‚       â””â”€â”€ AjaxTest.php           # Tests du contrÃ´leur AJAX
â”œâ”€â”€ integration/                    # Tests d'intÃ©gration
â”‚   â””â”€â”€ ReactionsIntegrationTest.php # Tests d'intÃ©gration complets
â”œâ”€â”€ functional/                     # Tests de fonctionnalitÃ©
â”œâ”€â”€ logs/                          # Logs des tests
â”œâ”€â”€ cache/                         # Cache des tests
â””â”€â”€ temp/                          # Fichiers temporaires
```

## Types de tests

### Tests unitaires
- **Objectif** : Tester les composants individuels de l'extension
- **Couverture** : Classes, mÃ©thodes, fonctions
- **Isolation** : Chaque test est indÃ©pendant
- **Mocks** : Utilisation de mocks pour les dÃ©pendances

### Tests d'intÃ©gration
- **Objectif** : Tester l'interaction entre les composants
- **Couverture** : Flux de donnÃ©es, intÃ©gration base de donnÃ©es
- **Environnement** : Base de donnÃ©es de test, services rÃ©els
- **DonnÃ©es** : DonnÃ©es de test rÃ©alistes

### Tests de fonctionnalitÃ©
- **Objectif** : Tester les fonctionnalitÃ©s complÃ¨tes
- **Couverture** : Cas d'usage utilisateur, scÃ©narios complets
- **Environnement** : Environnement proche de la production
- **Interface** : Tests d'interface utilisateur

## ExÃ©cution des tests

### PrÃ©requis
```bash
# Installer les dÃ©pendances de test
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

# Tests d'intÃ©gration uniquement
phpunit tests/integration/

# Tests de fonctionnalitÃ© uniquement
phpunit tests/functional/

# Avec couverture de code
phpunit --coverage-html coverage/

# Tests spÃ©cifiques
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

## Ã‰criture de tests

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
        // Nettoyage aprÃ¨s chaque test
    }
}
```

### Structure d'un test d'intÃ©gration
```php
<?php
namespace bastien59960\reactions\tests\integration;

use PHPUnit\Framework\TestCase;

class MonTestIntegration extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialisation de la base de donnÃ©es de test
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

### GÃ©nÃ©ration du rapport
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
- `vendor/` - DÃ©pendances externes
- `tests/` - Code de test
- `composer.json` - Configuration
- `README.md` - Documentation
- `CHANGELOG.md` - Historique
- `DOCUMENTATION.md` - Documentation
- `GUIDE_DEVELOPPEMENT.md` - Guide de dÃ©veloppement
- `GUIDE_INSTALLATION.md` - Guide d'installation

## DonnÃ©es de test

### Base de donnÃ©es de test
- **Type** : SQLite en mÃ©moire
- **Tables** : Structure complÃ¨te de l'extension
- **DonnÃ©es** : DonnÃ©es de test rÃ©alistes
- **Isolation** : Base de donnÃ©es fraÃ®che pour chaque test

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

// Logs dÃ©taillÃ©s
error_log('[Test] Message de debug');
```

### Outils de debug
- **Xdebug** : DÃ©bogueur PHP
- **PHPUnit** : Outils de test intÃ©grÃ©s
- **Coverage** : Analyse de couverture
- **Profiler** : Analyse de performance

## Bonnes pratiques

### Ã‰criture de tests
- **Nommage** : Noms descriptifs et clairs
- **Structure** : Arrange-Act-Assert
- **Isolation** : Tests indÃ©pendants
- **DonnÃ©es** : DonnÃ©es de test minimales
- **Assertions** : Assertions spÃ©cifiques

### Maintenance
- **Mise Ã  jour** : Tests Ã  jour avec le code
- **Refactoring** : Refactoring des tests
- **Documentation** : Documentation des tests
- **Performance** : Optimisation des tests

### IntÃ©gration continue
- **Automatisation** : Tests automatiques
- **Rapports** : Rapports de test
- **Notifications** : Notifications d'Ã©chec
- **DÃ©ploiement** : Tests avant dÃ©ploiement

## DÃ©pannage

### ProblÃ¨mes courants
- **MÃ©moire** : Augmenter la limite de mÃ©moire
- **Temps** : Augmenter le temps d'exÃ©cution
- **Base de donnÃ©es** : VÃ©rifier la configuration
- **Permissions** : VÃ©rifier les permissions de fichiers

### Solutions
```bash
# ProblÃ¨me de mÃ©moire
php -d memory_limit=1G phpunit

# ProblÃ¨me de temps
php -d max_execution_time=600 phpunit

# ProblÃ¨me de base de donnÃ©es
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# ProblÃ¨me de permissions
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

### CommunautÃ©
- [PHPUnit GitHub](https://github.com/sebastianbergmann/phpunit)
- [PHP Testing Community](https://www.php.net/manual/en/intro.pdo.php)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/phpunit)

---

*Cette documentation est maintenue Ã  jour avec chaque version de l'extension.*
