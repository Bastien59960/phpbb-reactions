# Guide d'installation et de configuration - Extension Reactions

## Prérequis

### Versions supportées
- **phpBB** : 3.3.0 ou supérieur
- **PHP** : 7.4 ou supérieur
- **MySQL** : 5.7 ou supérieur / MariaDB 10.2 ou supérieur
- **Navigateurs** : Chrome 80+, Firefox 75+, Safari 13+, Edge 80+

### Extensions requises
- **UTF8MB4** : Support des emojis composés
- **JavaScript** : Activé côté client
- **AJAX** : Support des requêtes asynchrones

## Installation

### 1. Téléchargement
```bash
# Cloner le repository
git clone https://github.com/bastien59960/reactions.git

# Ou télécharger l'archive ZIP
wget https://github.com/bastien59960/reactions/archive/main.zip
```

### 2. Upload des fichiers
```bash
# Copier les fichiers dans le répertoire des extensions
cp -r reactions/ /path/to/phpbb/ext/bastien59960/reactions/
```

### 3. Permissions
```bash
# Vérifier les permissions
chmod -R 755 /path/to/phpbb/ext/bastien59960/reactions/
chown -R www-data:www-data /path/to/phpbb/ext/bastien59960/reactions/
```

### 4. Activation via l'ACP
1. Se connecter à l'administration phpBB
2. Aller dans **Extensions** → **Gérer les extensions**
3. Trouver **Reactions** dans la liste
4. Cliquer sur **Activer**

### 5. Vérification de l'installation
1. Aller dans **Extensions** → **Reactions** → **Configuration**
2. Vérifier que l'extension est activée
3. Tester l'affichage des réactions sur un message

## Configuration

### Configuration de base

#### 1. Activation de l'extension
```
ACP → Extensions → Reactions → Configuration
```
- **Activer les réactions** : ✅ Activé
- **Nombre maximum de types de réactions par message** : 20
- **Nombre maximum de réactions par utilisateur et par message** : 10

#### 2. Configuration des notifications
```
ACP → Extensions → Reactions → Notifications
```
- **Délai anti-spam pour les emails** : 2700 secondes (45 minutes)
- **Activer les notifications par cloche** : ✅ Activé
- **Activer les notifications par email** : ✅ Activé

### Configuration avancée

#### 1. Personnalisation des emojis
```bash
# Éditer le fichier des emojis courantes
nano /path/to/phpbb/ext/bastien59960/reactions/styles/prosilver/template/js/emoji-keywords-fr.js
```

#### 2. Personnalisation des styles
```bash
# Éditer le fichier CSS
nano /path/to/phpbb/ext/bastien59960/reactions/styles/prosilver/theme/reactions.css
```

#### 3. Configuration de la base de données
```sql
-- Vérifier la table des réactions
DESCRIBE phpbb_post_reactions;

-- Vérifier les index
SHOW INDEX FROM phpbb_post_reactions;
```

## Configuration des utilisateurs

### Préférences de notification
1. Se connecter au forum
2. Aller dans **Panneau utilisateur** → **Préférences**
3. Cliquer sur **Modifier les préférences de notification**
4. Trouver **Réactions aux messages**
5. Configurer les préférences :
   - **Notification par cloche** : ✅ Activé
   - **Notification par email** : ✅ Activé

### Gestion des réactions
- **Ajouter une réaction** : Cliquer sur le bouton "plus" sous un message
- **Supprimer une réaction** : Cliquer sur une réaction existante
- **Voir les utilisateurs** : Survoler une réaction pour voir la liste

## Maintenance

### Tâches de maintenance

#### 1. Nettoyage des réactions orphelines
```sql
-- Supprimer les réactions pour des messages supprimés
DELETE r FROM phpbb_post_reactions r
LEFT JOIN phpbb_posts p ON r.post_id = p.post_id
WHERE p.post_id IS NULL;
```

#### 2. Optimisation de la base de données
```sql
-- Analyser la table
ANALYZE TABLE phpbb_post_reactions;

-- Optimiser la table
OPTIMIZE TABLE phpbb_post_reactions;
```

#### 3. Vérification des permissions
```bash
# Vérifier les permissions des fichiers
find /path/to/phpbb/ext/bastien59960/reactions/ -type f -exec ls -la {} \;
```

### Surveillance

#### 1. Logs d'erreur
```bash
# Vérifier les logs PHP
tail -f /var/log/php/error.log | grep "Reactions"

# Vérifier les logs phpBB
tail -f /path/to/phpbb/cache/log_error.log
```

#### 2. Performance
```sql
-- Vérifier les requêtes lentes
SHOW PROCESSLIST;

-- Analyser les performances
EXPLAIN SELECT * FROM phpbb_post_reactions WHERE post_id = 123;
```

## Dépannage

### Problèmes courants

#### 1. Les réactions ne s'affichent pas
**Causes possibles :**
- Extension non activée
- JavaScript désactivé
- Erreur dans les logs

**Solutions :**
```bash
# Vérifier l'activation
grep "bastien59960_reactions_enabled" /path/to/phpbb/config.php

# Vérifier les logs
tail -f /var/log/php/error.log | grep "Reactions"
```

#### 2. Erreurs JavaScript
**Causes possibles :**
- Fichier JS non chargé
- Erreur de syntaxe
- Conflit avec d'autres extensions

**Solutions :**
```bash
# Vérifier le fichier JS
cat /path/to/phpbb/ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js

# Tester dans la console du navigateur
console.log('Reactions JS loaded');
```

#### 3. Problèmes de base de données
**Causes possibles :**
- Table non créée
- Permissions insuffisantes
- Erreur de migration

**Solutions :**
```sql
-- Vérifier l'existence de la table
SHOW TABLES LIKE 'phpbb_post_reactions';

-- Vérifier la structure
DESCRIBE phpbb_post_reactions;
```

#### 4. Notifications ne fonctionnent pas
**Causes possibles :**
- Service de notification non configuré
- Tâche cron non exécutée
- Erreur dans les logs

**Solutions :**
```bash
# Vérifier la configuration
grep "bastien59960_reactions_spam_time" /path/to/phpbb/config.php

# Vérifier les logs de notification
tail -f /path/to/phpbb/cache/log_error.log | grep "notification"
```

### Logs de debug

#### 1. Activation du mode debug
```php
// Dans config.php
$config['debug'] = true;
$config['debug_extra'] = true;
```

#### 2. Logs spécifiques
```bash
# Logs des réactions
tail -f /var/log/php/error.log | grep "\[Reactions"

# Logs des notifications
tail -f /var/log/php/error.log | grep "notification"
```

## Mise à jour

### Procédure de mise à jour

#### 1. Sauvegarde
```bash
# Sauvegarder la base de données
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Sauvegarder les fichiers
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/phpbb/
```

#### 2. Mise à jour des fichiers
```bash
# Désactiver l'extension
# (via l'ACP ou en renommant le dossier)

# Remplacer les fichiers
cp -r new_version/* /path/to/phpbb/ext/bastien59960/reactions/

# Réactiver l'extension
# (via l'ACP)
```

#### 3. Vérification
```bash
# Vérifier la version
grep "version" /path/to/phpbb/ext/bastien59960/reactions/composer.json

# Tester les fonctionnalités
# (ajouter/supprimer des réactions)
```

## Désinstallation

### Procédure de désinstallation

#### 1. Désactivation
1. Aller dans **ACP** → **Extensions** → **Gérer les extensions**
2. Trouver **Reactions** et cliquer sur **Désactiver**

#### 2. Suppression des données
```sql
-- Supprimer la table des réactions
DROP TABLE IF EXISTS phpbb_post_reactions;

-- Supprimer les options de configuration
DELETE FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%';
```

#### 3. Suppression des fichiers
```bash
# Supprimer le dossier de l'extension
rm -rf /path/to/phpbb/ext/bastien59960/reactions/
```

## Support

### Ressources utiles
- **Documentation** : [DOCUMENTATION.md](DOCUMENTATION.md)
- **Guide de développement** : [GUIDE_DEVELOPPEMENT.md](GUIDE_DEVELOPPEMENT.md)
- **Changelog** : [CHANGELOG.md](CHANGELOG.md)

### Contact
- **Issues GitHub** : [Signaler un bug](https://github.com/bastien59960/reactions/issues)
- **Discussions** : [Forum de discussion](https://github.com/bastien59960/reactions/discussions)

### Communauté
- **Contributions** : [Guide de contribution](CONTRIBUTING.md)
- **Code de conduite** : [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

---

*Ce guide est maintenu à jour avec chaque version de l'extension.*
