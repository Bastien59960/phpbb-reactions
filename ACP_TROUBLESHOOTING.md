# Dépannage ACP - Extension Reactions

## Problème : Les traductions ne s'affichent pas dans l'ACP

### Symptômes
- Menu ACP affiche `ACP_REACTIONS_TITLE` au lieu de "Réglages des réactions"
- Page de configuration affiche `ACP_REACTIONS_SETTINGS` au lieu de "Configuration des réactions"
- Interface en anglais malgré phpBB configuré en français

### Solutions

#### 1. Vérifier les fichiers de langue ACP
```bash
# Vérifier que les fichiers existent
ls -la language/fr/acp/common.php
ls -la language/en/acp/common.php
```

#### 2. Purger le cache phpBB
```bash
# Dans le répertoire de votre forum
rm -rf cache/*
```

#### 3. Vérifier les permissions
```bash
# Les fichiers de langue doivent être lisibles
chmod 644 language/fr/acp/common.php
chmod 644 language/en/acp/common.php
```

#### 4. Vérifier l'encodage des fichiers
```bash
# Les fichiers doivent être en UTF-8 sans BOM
file language/fr/acp/common.php
file language/en/acp/common.php
```

#### 5. Tester les traductions
```bash
# Exécuter le script de test
php test_language.php
```

### Structure des fichiers de langue

```
language/
├── fr/
│   ├── acp/
│   │   └── common.php    # Traductions ACP françaises
│   └── common.php        # Traductions générales françaises
└── en/
    ├── acp/
    │   └── common.php    # Traductions ACP anglaises
    └── common.php        # Traductions générales anglaises
```

### Clés de traduction ACP requises

```php
// Dans language/*/acp/common.php
$lang = array_merge($lang, array(
    'ACP_REACTIONS_TITLE'                   => 'Réglages des réactions',
    'ACP_REACTIONS_SETTINGS'                => 'Configuration des réactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Configurez les paramètres des réactions aux messages.',
    'REACTIONS_MAX_PER_POST'                => 'Nombre maximal de types de réaction par message',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'Le nombre maximal de types de réaction uniques qu\'un seul message peut recevoir.',
    'REACTIONS_MAX_PER_USER'                => 'Nombre maximal de réactions par utilisateur par message',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'Le nombre maximal de réactions qu\'un seul utilisateur peut ajouter à un seul message.',
));
```

### Vérification dans l'ACP

1. **Aller dans l'ACP** : Administration > Extensions
2. **Vérifier le menu** : "Post Reactions" doit apparaître
3. **Cliquer sur "Post Reactions"** : "Paramètres des réactions" doit s'afficher
4. **Vérifier les champs** : Les labels doivent être en français

### Logs d'erreur

Vérifier les logs d'erreur PHP :
```bash
tail -f /var/log/php/error.log | grep -i reaction
```

### Test rapide

Pour tester si les traductions se chargent :
```php
<?php
// Dans un fichier de test
$lang = array();
include 'language/fr/acp/common.php';
echo $lang['ACP_REACTIONS_TITLE'];
?>
```

### Si le problème persiste

1. **Vérifier la langue par défaut** de votre utilisateur dans l'ACP
2. **Forcer le français** dans les paramètres utilisateur
3. **Vérifier les permissions** de l'extension
4. **Redémarrer** le serveur web si nécessaire

### Commandes utiles

```bash
# Purger tous les caches
rm -rf cache/*

# Vérifier les permissions
find language/ -type f -exec chmod 644 {} \;

# Vérifier l'encodage
find language/ -name "*.php" -exec file {} \;

# Tester la syntaxe PHP
find language/ -name "*.php" -exec php -l {} \;
```
