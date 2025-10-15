# Guide de Debug - Extension phpBB Reactions

## Problèmes identifiés et corrigés

### 1. Synchronisation des émojis courantes
- **Problème** : Les listes d'émojis n'étaient pas identiques entre les fichiers
- **Solution** : Synchronisé toutes les listes avec `['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳']`

### 2. Templates HTML incorrects
- **Problème** : Le template `reactions.html` utilisait une structure incorrecte
- **Solution** : Corrigé pour correspondre à la structure attendue par le listener

### 3. Logs de debug excessifs
- **Problème** : Trop de logs de debug dans le code de production
- **Solution** : Supprimé les logs de debug non essentiels

### 4. Incohérence dans les noms de variables
- **Problème** : Mélange entre `popular_emojis` et `common_emojis`
- **Solution** : Standardisé sur `common_emojis` partout

## Comment tester l'extension

### 1. Vérifier l'installation
```bash
# Accéder à l'URL de test
https://votre-forum.com/app.php/reactions/test
```

### 2. Vérifier les logs
```bash
# Vérifier les logs d'erreur PHP
tail -f /var/log/php/error.log | grep "Reactions"
```

### 3. Tester les réactions
1. Se connecter au forum
2. Aller sur un topic
3. Cliquer sur le bouton "+" à côté des posts
4. Sélectionner un emoji
5. Vérifier que la réaction s'affiche

### 4. Vérifier la base de données
```sql
-- Vérifier que la table existe
SHOW TABLES LIKE 'phpbb_post_reactions';

-- Vérifier la structure
DESCRIBE phpbb_post_reactions;

-- Vérifier les données
SELECT * FROM phpbb_post_reactions LIMIT 10;
```

## Problèmes courants et solutions

### 1. Les réactions ne s'affichent pas
- Vérifier que l'extension est activée
- Vérifier que les templates sont purgés
- Vérifier les logs d'erreur

### 2. Erreur CSRF
- Vérifier que `REACTIONS_SID` est défini dans le JavaScript
- Vérifier que l'utilisateur est connecté

### 3. Problèmes d'encodage UTF-8
- Vérifier que la base de données utilise `utf8mb4`
- Vérifier que la connexion utilise `utf8mb4_bin`

### 4. JavaScript ne fonctionne pas
- Vérifier que le fichier `reactions.js` est chargé
- Vérifier la console du navigateur pour les erreurs
- Vérifier que `REACTIONS_AJAX_URL` est défini

## Structure des fichiers

```
ext/
├── bastien59960/
│   └── reactions/
│       ├── controller/
│       │   ├── ajax.php      # Gestion AJAX des réactions
│       │   ├── main.php      # Contrôleur principal
│       │   └── test.php      # Contrôleur de test
│       ├── event/
│       │   └── listener.php  # Gestion des événements
│       ├── config/
│       │   ├── services.yml  # Configuration des services
│       │   ├── routing.yml   # Configuration des routes
│       │   └── parameters.yml # Paramètres
│       ├── styles/
│       │   └── prosilver/
│       │       ├── template/
│       │       │   ├── js/
│       │       │   │   └── reactions.js
│       │       │   └── event/
│       │       │       ├── reactions.html
│       │       │       └── viewtopic_body_postrow_content_after.html
│       │       └── theme/
│       │           ├── reactions.css
│       │           └── categories.json
│       └── language/
│           ├── fr/
│           │   └── common.php
│           └── en/
│               └── common.php
```

## Commandes utiles

### Purger le cache des templates
```bash
# Dans l'ACP de phpBB
Administration > Général > Purger le cache
```

### Vérifier les permissions
```bash
# Vérifier que les fichiers sont accessibles
ls -la ext/bastien59960/reactions/
```

### Tester la base de données
```sql
-- Tester l'insertion d'une réaction
INSERT INTO phpbb_post_reactions (post_id, topic_id, user_id, reaction_emoji, reaction_time) 
VALUES (1, 1, 1, '👍', UNIX_TIMESTAMP());
```
