# Guide de dÃ©ploiement - Correction des traductions ACP

## ProblÃ¨me identifiÃ©
Le fichier `language/fr/acp/common.php` manque sur le serveur, ce qui cause l'affichage des clÃ©s de traduction au lieu des textes franÃ§ais dans l'ACP.

## Solutions

### Option 1 : DÃ©ploiement via Git (RecommandÃ©)

#### Sur votre machine locale :
```bash
# 1. Commiter les modifications
git add language/fr/acp/common.php
git commit -m "Fix: Ajouter le fichier de traduction ACP franÃ§ais manquant"

# 2. Pousser vers GitHub
git push origin main
```

#### Sur le serveur :
```bash
# 1. Aller dans le rÃ©pertoire de l'extension
cd /home/bastien/www/forum/ext/bastien59960/reactions

# 2. RÃ©cupÃ©rer les modifications
git pull origin main

# 3. Purger le cache
cd /home/bastien/www/forum
rm -rf cache/*

# 4. Tester
cd /home/bastien/www/forum/ext/bastien59960/reactions
php test_language.php
```

### Option 2 : Correction directe sur le serveur

#### TÃ©lÃ©charger et exÃ©cuter le script de correction :
```bash
# 1. Aller dans le rÃ©pertoire de l'extension
cd /home/bastien/www/forum/ext/bastien59960/reactions

# 2. CrÃ©er le rÃ©pertoire
mkdir -p language/fr/acp

# 3. CrÃ©er le fichier de traduction
cat > language/fr/acp/common.php << 'EOF'
<?php
/**
*
* acp_common [French]
*
* @copyright (c) 2025 Bastien59960
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    // Titre principal ACP
    'ACP_REACTIONS_TITLE'                   => 'RÃ©glages des rÃ©actions',

    // Page de paramÃ¨tres ACP
    'ACP_REACTIONS_SETTINGS'                => 'Configuration des rÃ©actions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Ici, vous pouvez gÃ©rer les paramÃ¨tres pour l\'extension de rÃ©actions aux messages.',

    // Champs de configuration
    'REACTIONS_SPAM_TIME'                   => 'FenÃªtre anti-spam des notifications',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'Le temps en minutes Ã  attendre avant d\'envoyer une notification groupÃ©e Ã  l\'auteur du message. Mettre Ã  0 pour dÃ©sactiver les notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Nombre maximal de types de rÃ©action par message',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'Le nombre maximal de types de rÃ©action uniques qu\'un seul message peut recevoir.',
    'REACTIONS_MAX_PER_USER'                => 'Nombre maximal de rÃ©actions par utilisateur par message',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'Le nombre maximal de rÃ©actions qu\'un seul utilisateur peut ajouter Ã  un seul message.',

    // Termes gÃ©nÃ©raux
    'MINUTES'                               => 'Minutes',
));
EOF

# 4. DÃ©finir les permissions
chmod 644 language/fr/acp/common.php

# 5. Tester
php test_language.php

# 6. Purger le cache
cd /home/bastien/www/forum
rm -rf cache/*
```

## VÃ©rification

### 1. Test des traductions
```bash
cd /home/bastien/www/forum/ext/bastien59960/reactions
php test_language.php
```

**RÃ©sultat attendu :**
```
=== Test des traductions ACP ===

--- Test franÃ§ais ---
âœ“ ACP_REACTIONS_TITLE: RÃ©glages des rÃ©actions
âœ“ ACP_REACTIONS_SETTINGS: Configuration des rÃ©actions

--- Test anglais ---
âœ“ ACP_REACTIONS_TITLE: Post Reactions
âœ“ ACP_REACTIONS_SETTINGS: Reactions Settings

=== Test terminÃ© ===
```

### 2. Test dans l'ACP
1. Aller dans **Administration > Extensions**
2. Chercher **"Post Reactions"** (devrait Ãªtre en franÃ§ais)
3. Cliquer dessus pour voir la page de configuration
4. VÃ©rifier que tous les labels sont en franÃ§ais

## Structure des fichiers de langue

```
language/
â”œâ”€â”€ fr/
â”‚   â”œâ”€â”€ acp/
â”‚   â”‚   â””â”€â”€ common.php    # âœ… CrÃ©Ã© - Traductions ACP franÃ§aises
â”‚   â””â”€â”€ common.php        # âœ… Existe - Traductions gÃ©nÃ©rales franÃ§aises
â””â”€â”€ en/
    â”œâ”€â”€ acp/
    â”‚   â””â”€â”€ common.php    # âœ… Existe - Traductions ACP anglaises
    â””â”€â”€ common.php        # âœ… Existe - Traductions gÃ©nÃ©rales anglaises
```

## DÃ©pannage

### Si les traductions ne s'affichent toujours pas :

1. **VÃ©rifier les permissions :**
   ```bash
   chmod 644 language/fr/acp/common.php
   ```

2. **VÃ©rifier l'encodage :**
   ```bash
   file language/fr/acp/common.php
   # Doit afficher : UTF-8 Unicode text
   ```

3. **VÃ©rifier la syntaxe PHP :**
   ```bash
   php -l language/fr/acp/common.php
   # Doit afficher : No syntax errors detected
   ```

4. **Purger tous les caches :**
   ```bash
   cd /home/bastien/www/forum
   rm -rf cache/*
   ```

5. **RedÃ©marrer le serveur web :**
   ```bash
   sudo systemctl restart apache2
   # ou
   sudo systemctl restart nginx
   ```

## Commandes utiles

```bash
# VÃ©rifier la structure des fichiers
find language/ -name "*.php" -type f

# VÃ©rifier les permissions
find language/ -name "*.php" -exec ls -la {} \;

# Tester tous les fichiers PHP
find language/ -name "*.php" -exec php -l {} \;

# Purger le cache
rm -rf /home/bastien/www/forum/cache/*
```
