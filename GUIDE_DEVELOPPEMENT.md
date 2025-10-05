# Guide de développement - Extension Reactions

## Introduction

Ce guide explique comment contribuer au développement de l'extension Reactions pour phpBB. Il couvre les conventions de code, les bonnes pratiques, et les procédures de développement.

## Conventions de code

### PHP

#### Structure des classes
```php
<?php
/**
 * Description de la classe
 * 
 * Explication détaillée du rôle de la classe
 * et de ses fonctionnalités principales.
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions;

/**
 * Classe principale
 * 
 * Description détaillée de la classe et de son rôle
 * dans l'architecture de l'extension.
 */
class MaClasse
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données */
    protected $db;
    
    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur de la classe
     * 
     * @param \phpbb\db\driver\driver_interface $db Connexion base de données
     */
    public function __construct(\phpbb\db\driver\driver_interface $db)
    {
        $this->db = $db;
    }
    
    // =============================================================================
    // MÉTHODES PUBLIQUES
    // =============================================================================
    
    /**
     * Description de la méthode
     * 
     * @param int $param1 Description du paramètre
     * @param string $param2 Description du paramètre
     * @return bool Description de la valeur de retour
     */
    public function maMethode($param1, $param2)
    {
        // Implémentation
        return true;
    }
}
```

#### Conventions de nommage
- **Classes** : PascalCase (`MaClasse`)
- **Méthodes** : camelCase (`maMethode`)
- **Propriétés** : camelCase (`maPropriete`)
- **Constantes** : UPPER_SNAKE_CASE (`MA_CONSTANTE`)
- **Variables** : camelCase (`maVariable`)

#### Commentaires
```php
/**
 * Description courte de la méthode
 * 
 * Description détaillée de ce que fait la méthode,
 * comment elle fonctionne, et pourquoi elle existe.
 * 
 * @param int $param1 Description du paramètre
 * @param string $param2 Description du paramètre
 * @return bool Description de la valeur de retour
 * @throws \Exception Description des exceptions possibles
 */
public function maMethode($param1, $param2)
{
    // Commentaire pour expliquer une logique complexe
    $resultat = $param1 + $param2;
    
    return $resultat > 0;
}
```

### JavaScript

#### Structure des fonctions
```javascript
/**
 * Description de la fonction
 * 
 * Explication détaillée du rôle de la fonction
 * et de son comportement.
 * 
 * @param {string} param1 Description du paramètre
 * @param {number} param2 Description du paramètre
 * @return {boolean} Description de la valeur de retour
 */
function maFonction(param1, param2) {
    // Implémentation
    return true;
}
```

#### Conventions de nommage
- **Fonctions** : camelCase (`maFonction`)
- **Variables** : camelCase (`maVariable`)
- **Constantes** : UPPER_SNAKE_CASE (`MA_CONSTANTE`)
- **Classes** : PascalCase (`MaClasse`)

### CSS

#### Structure des règles
```css
/**
 * Description de la règle CSS
 * 
 * Explication du rôle et du comportement
 * de cette règle CSS.
 */

.ma-classe {
    /* Propriété avec commentaire explicatif */
    display: flex;
    
    /* Autre propriété */
    margin: 10px;
}
```

#### Conventions de nommage
- **Classes** : kebab-case (`ma-classe`)
- **IDs** : kebab-case (`mon-id`)
- **Variables CSS** : kebab-case (`--ma-variable`)

## Architecture et design patterns

### Pattern MVC
L'extension suit le pattern Model-View-Controller :

- **Model** : Classes de données et accès à la base de données
- **View** : Templates HTML et CSS
- **Controller** : Logique métier et gestion des requêtes

### Injection de dépendances
Utilisation du conteneur de services phpBB :

```php
// Dans services.yml
services:
    mon.service:
        class: Mon\Namespace\MaClasse
        arguments:
            - '@dbal.conn'
            - '@user'
```

### Événements
Utilisation du système d'événements phpBB :

```php
public static function getSubscribedEvents()
{
    return [
        'core.page_header' => 'maMethode',
    ];
}
```

## Tests et qualité

### Tests unitaires
```php
class MaClasseTest extends \PHPUnit\Framework\TestCase
{
    public function testMaMethode()
    {
        $classe = new MaClasse();
        $resultat = $classe->maMethode(1, 2);
        
        $this->assertEquals(3, $resultat);
    }
}
```

### Tests d'intégration
```php
class IntegrationTest extends \PHPUnit\Framework\TestCase
{
    public function testAjoutReaction()
    {
        // Test d'intégration complet
        $this->assertTrue(true);
    }
}
```

### Validation du code
- **PHP_CodeSniffer** : Respect des standards PSR
- **PHPStan** : Analyse statique du code
- **ESLint** : Validation du JavaScript
- **Stylelint** : Validation du CSS

## Gestion des erreurs

### PHP
```php
try {
    // Code qui peut échouer
    $resultat = $this->db->sql_query($sql);
} catch (\Exception $e) {
    // Log de l'erreur
    error_log('[Reactions] Erreur: ' . $e->getMessage());
    
    // Gestion de l'erreur
    throw new \RuntimeException('Erreur lors de l\'opération');
}
```

### JavaScript
```javascript
try {
    // Code qui peut échouer
    const resultat = await fetch(url);
} catch (error) {
    // Log de l'erreur
    console.error('[Reactions] Erreur:', error);
    
    // Gestion de l'erreur
    afficherErreur('Erreur lors de l\'opération');
}
```

## Performance et optimisation

### Base de données
- **Index appropriés** : Optimisation des requêtes fréquentes
- **Requêtes préparées** : Protection contre les injections SQL
- **Pagination** : Limitation des résultats

### JavaScript
- **Debouncing** : Limitation des appels fréquents
- **Lazy loading** : Chargement différé des ressources
- **Cache** : Mise en cache des données

### CSS
- **Sélecteurs optimisés** : Éviter les sélecteurs coûteux
- **Propriétés GPU** : Utilisation des transformations CSS
- **Minification** : Réduction de la taille des fichiers

## Sécurité

### Validation des données
```php
// Validation des entrées utilisateur
$postId = (int) $this->request->variable('post_id', 0);
if ($postId <= 0) {
    throw new \InvalidArgumentException('ID de message invalide');
}
```

### Protection CSRF
```php
// Vérification du jeton CSRF
$sid = $this->request->variable('sid', '');
if ($sid !== $this->user->data['session_id']) {
    throw new \RuntimeException('Jeton CSRF invalide');
}
```

### Échappement des sorties
```php
// Échappement des données pour l'affichage
$emoji = htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8');
```

## Documentation

### Documentation du code
- **PHPDoc** : Documentation des classes et méthodes
- **JSDoc** : Documentation des fonctions JavaScript
- **Commentaires CSS** : Explication des règles complexes

### Documentation utilisateur
- **README** : Guide d'installation et d'utilisation
- **Documentation** : Guide complet des fonctionnalités
- **Exemples** : Cas d'usage typiques

## Déploiement

### Versioning
- **Semantic Versioning** : MAJOR.MINOR.PATCH
- **Changelog** : Historique des modifications
- **Tags Git** : Marquage des versions

### Migration
```php
// Migration de base de données
public function update_schema()
{
    return [
        'add_tables' => [
            $this->table_prefix . 'ma_table' => [
                'COLUMNS' => [
                    'id' => ['UINT', null, 'auto_increment'],
                ],
                'PRIMARY_KEY' => 'id',
            ],
        ],
    ];
}
```

### Tests de déploiement
- **Tests de régression** : Vérification des fonctionnalités existantes
- **Tests de performance** : Mesure des temps de réponse
- **Tests de compatibilité** : Vérification avec différentes versions de phpBB

## Contribution

### Workflow Git
1. **Fork** du repository
2. **Branche feature** : `git checkout -b feature/ma-fonctionnalite`
3. **Commit** : `git commit -m "feat: ajout de ma fonctionnalité"`
4. **Push** : `git push origin feature/ma-fonctionnalite`
5. **Pull Request** : Demande de fusion

### Messages de commit
- **feat** : Nouvelle fonctionnalité
- **fix** : Correction de bug
- **docs** : Documentation
- **style** : Formatage du code
- **refactor** : Refactoring
- **test** : Tests
- **chore** : Tâches de maintenance

### Code Review
- **Vérification du code** : Respect des conventions
- **Tests** : Vérification des tests
- **Documentation** : Mise à jour de la documentation
- **Performance** : Vérification des performances

## Outils de développement

### IDE recommandé
- **PhpStorm** : IDE PHP complet
- **VS Code** : Éditeur léger avec extensions
- **Sublime Text** : Éditeur rapide

### Extensions utiles
- **PHP Intelephense** : IntelliSense PHP
- **ESLint** : Validation JavaScript
- **Prettier** : Formatage du code
- **GitLens** : Intégration Git

### Outils de build
- **Composer** : Gestion des dépendances PHP
- **NPM** : Gestion des dépendances JavaScript
- **Webpack** : Bundling des assets
- **Gulp** : Automatisation des tâches

---

*Ce guide est maintenu à jour avec les évolutions de l'extension.*
