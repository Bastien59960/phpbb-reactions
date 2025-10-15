# Guide de déploiement - Correction des traductions ACP

## Problème identifié
Le fichier `language/fr/acp/common.php` manque sur le serveur, ce qui cause l'affichage des clés de traduction au lieu des textes français dans l'ACP.

## Solutions

### Option 1 : Déploiement via Git (Recommandé)

#### Sur votre machine locale :
```bash
# 1. Commiter les modifications
git add language/fr/acp/common.php
git commit -m "Fix: Ajouter le fichier de traduction ACP français manquant"

# 2. Pousser vers GitHub
git push origin main
```

#### Sur le serveur :
```bash
# 1. Aller dans le répertoire de l'extension
cd /home/bastien/www/forum/ext/bastien59960/reactions

# 2. Récupérer les modifications
git pull origin main

# 3. Purger le cache
cd /home/bastien/www/forum
rm -rf cache/*

# 4. Tester
cd /home/bastien/www/forum/ext/bastien59960/reactions
php test_language.php
```

### Option 2 : Correction directe sur le serveur

#### Télécharger et exécuter le script de correction :
```bash
# 1. Aller dans le répertoire de l'extension
cd /home/bastien/www/forum/ext/bastien59960/reactions

# 2. Créer le répertoire
mkdir -p language/fr/acp

# 3. Créer le fichier de traduction
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
    'ACP_REACTIONS_TITLE'                   => 'Réglages des réactions',

    // Page de paramètres ACP
    'ACP_REACTIONS_SETTINGS'                => 'Configuration des réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Ici, vous pouvez gérer les paramètres pour l\'extension de réactions aux messages.',

    // Champs de configuration
    'REACTIONS_SPAM_TIME'                   => 'Fenêtre anti-spam des notifications',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'Le temps en minutes à attendre avant d\'envoyer une notification groupée à l\'auteur du message. Mettre à 0 pour désactiver les notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Nombre maximal de types de réaction par message',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'Le nombre maximal de types de réaction uniques qu\'un seul message peut recevoir.',
    'REACTIONS_MAX_PER_USER'                => 'Nombre maximal de réactions par utilisateur par message',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'Le nombre maximal de réactions qu\'un seul utilisateur peut ajouter à un seul message.',

    // Termes généraux
    'MINUTES'                               => 'Minutes',
));
EOF

# 4. Définir les permissions
chmod 644 language/fr/acp/common.php

# 5. Tester
php test_language.php

# 6. Purger le cache
cd /home/bastien/www/forum
rm -rf cache/*
```

## Vérification

### 1. Test des traductions
```bash
cd /home/bastien/www/forum/ext/bastien59960/reactions
php test_language.php
```

**Résultat attendu :**
```
=== Test des traductions ACP ===

--- Test français ---
✓ ACP_REACTIONS_TITLE: Réglages des réactions
✓ ACP_REACTIONS_SETTINGS: Configuration des réactions

--- Test anglais ---
✓ ACP_REACTIONS_TITLE: Post Reactions
✓ ACP_REACTIONS_SETTINGS: Reactions Settings

=== Test terminé ===
```

### 2. Test dans l'ACP
1. Aller dans **Administration > Extensions**
2. Chercher **"Post Reactions"** (devrait être en français)
3. Cliquer dessus pour voir la page de configuration
4. Vérifier que tous les labels sont en français

## Structure des fichiers de langue

```
language/
├── fr/
│   ├── acp/
│   │   └── common.php    # ✅ Créé - Traductions ACP françaises
│   └── common.php        # ✅ Existe - Traductions générales françaises
└── en/
    ├── acp/
    │   └── common.php    # ✅ Existe - Traductions ACP anglaises
    └── common.php        # ✅ Existe - Traductions générales anglaises
```

## Dépannage

### Si les traductions ne s'affichent toujours pas :

1. **Vérifier les permissions :**
   ```bash
   chmod 644 language/fr/acp/common.php
   ```

2. **Vérifier l'encodage :**
   ```bash
   file language/fr/acp/common.php
   # Doit afficher : UTF-8 Unicode text
   ```

3. **Vérifier la syntaxe PHP :**
   ```bash
   php -l language/fr/acp/common.php
   # Doit afficher : No syntax errors detected
   ```

4. **Purger tous les caches :**
   ```bash
   cd /home/bastien/www/forum
   rm -rf cache/*
   ```

5. **Redémarrer le serveur web :**
   ```bash
   sudo systemctl restart apache2
   # ou
   sudo systemctl restart nginx
   ```

## Commandes utiles

```bash
# Vérifier la structure des fichiers
find language/ -name "*.php" -type f

# Vérifier les permissions
find language/ -name "*.php" -exec ls -la {} \;

# Tester tous les fichiers PHP
find language/ -name "*.php" -exec php -l {} \;

# Purger le cache
rm -rf /home/bastien/www/forum/cache/*
```
