# Guide de Debug - Extension phpBB Reactions

## ProblÃ¨mes identifiÃ©s et corrigÃ©s

### 1. Synchronisation des Ã©mojis courantes
- **ProblÃ¨me** : Les listes d'Ã©mojis n'Ã©taient pas identiques entre les fichiers
- **Solution** : SynchronisÃ© toutes les listes avec `['ðŸ‘', 'ðŸ‘Ž', 'â¤ï¸', 'ðŸ˜‚', 'ðŸ˜®', 'ðŸ˜¢', 'ðŸ˜¡', 'ðŸ”¥', 'ðŸ‘Œ', 'ðŸ¥³']`

### 2. Templates HTML incorrects
- **ProblÃ¨me** : Le template `reactions.html` utilisait une structure incorrecte
- **Solution** : CorrigÃ© pour correspondre Ã  la structure attendue par le listener

### 3. Logs de debug excessifs
- **ProblÃ¨me** : Trop de logs de debug dans le code de production
- **Solution** : SupprimÃ© les logs de debug non essentiels

### 4. IncohÃ©rence dans les noms de variables
- **ProblÃ¨me** : MÃ©lange entre `popular_emojis` et `common_emojis`
- **Solution** : StandardisÃ© sur `common_emojis` partout

## Comment tester l'extension

### 1. VÃ©rifier l'installation
```bash
# AccÃ©der Ã  l'URL de test
https://votre-forum.com/app.php/reactions/test
```

### 2. VÃ©rifier les logs
```bash
# VÃ©rifier les logs d'erreur PHP
tail -f /var/log/php/error.log | grep "Reactions"
```

### 3. Tester les rÃ©actions
1. Se connecter au forum
2. Aller sur un topic
3. Cliquer sur le bouton "+" Ã  cÃ´tÃ© des posts
4. SÃ©lectionner un emoji
5. VÃ©rifier que la rÃ©action s'affiche

### 4. VÃ©rifier la base de donnÃ©es
```sql
-- VÃ©rifier que la table existe
SHOW TABLES LIKE 'phpbb_post_reactions';

-- VÃ©rifier la structure
DESCRIBE phpbb_post_reactions;

-- VÃ©rifier les donnÃ©es
SELECT * FROM phpbb_post_reactions LIMIT 10;
```

## ProblÃ¨mes courants et solutions

### 1. Les rÃ©actions ne s'affichent pas
- VÃ©rifier que l'extension est activÃ©e
- VÃ©rifier que les templates sont purgÃ©s
- VÃ©rifier les logs d'erreur

### 2. Erreur CSRF
- VÃ©rifier que `REACTIONS_SID` est dÃ©fini dans le JavaScript
- VÃ©rifier que l'utilisateur est connectÃ©

### 3. ProblÃ¨mes d'encodage UTF-8
- VÃ©rifier que la base de donnÃ©es utilise `utf8mb4`
- VÃ©rifier que la connexion utilise `utf8mb4_bin`

### 4. JavaScript ne fonctionne pas
- VÃ©rifier que le fichier `reactions.js` est chargÃ©
- VÃ©rifier la console du navigateur pour les erreurs
- VÃ©rifier que `REACTIONS_AJAX_URL` est dÃ©fini

## Structure des fichiers

```
ext/
â”œâ”€â”€ bastien59960/
â”‚   â””â”€â”€ reactions/
â”‚       â”œâ”€â”€ controller/
â”‚       â”‚   â”œâ”€â”€ ajax.php      # Gestion AJAX des rÃ©actions
â”‚       â”‚   â”œâ”€â”€ main.php      # ContrÃ´leur principal
â”‚       â”‚   â””â”€â”€ test.php      # ContrÃ´leur de test
â”‚       â”œâ”€â”€ event/
â”‚       â”‚   â””â”€â”€ listener.php  # Gestion des Ã©vÃ©nements
â”‚       â”œâ”€â”€ config/
â”‚       â”‚   â”œâ”€â”€ services.yml  # Configuration des services
â”‚       â”‚   â”œâ”€â”€ routing.yml   # Configuration des routes
â”‚       â”‚   â””â”€â”€ parameters.yml # ParamÃ¨tres
â”‚       â”œâ”€â”€ styles/
â”‚       â”‚   â””â”€â”€ prosilver/
â”‚       â”‚       â”œâ”€â”€ template/
â”‚       â”‚       â”‚   â”œâ”€â”€ js/
â”‚       â”‚       â”‚   â”‚   â””â”€â”€ reactions.js
â”‚       â”‚       â”‚   â””â”€â”€ event/
â”‚       â”‚       â”‚       â”œâ”€â”€ reactions.html
â”‚       â”‚       â”‚       â””â”€â”€ viewtopic_body_postrow_content_after.html
â”‚       â”‚       â””â”€â”€ theme/
â”‚       â”‚           â”œâ”€â”€ reactions.css
â”‚       â”‚           â””â”€â”€ categories.json
â”‚       â””â”€â”€ language/
â”‚           â”œâ”€â”€ fr/
â”‚           â”‚   â””â”€â”€ common.php
â”‚           â””â”€â”€ en/
â”‚               â””â”€â”€ common.php
```

## Commandes utiles

### Purger le cache des templates
```bash
# Dans l'ACP de phpBB
Administration > GÃ©nÃ©ral > Purger le cache
```

### VÃ©rifier les permissions
```bash
# VÃ©rifier que les fichiers sont accessibles
ls -la ext/bastien59960/reactions/
```

### Tester la base de donnÃ©es
```sql
-- Tester l'insertion d'une rÃ©action
INSERT INTO phpbb_post_reactions (post_id, topic_id, user_id, reaction_emoji, reaction_time) 
VALUES (1, 1, 1, 'ðŸ‘', UNIX_TIMESTAMP());
```
