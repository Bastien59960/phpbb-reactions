# Guide de Test des Notifications - Extension Reactions

## Résumé des Corrections Apportées

### 1. Problèmes Corrigés

#### A. Méthodes Dupliquées dans `notification/type/reaction.php`
- **Problème** : La méthode `get_item_parent_id()` était définie 3 fois (lignes 177, 211, 219)
- **Solution** : Suppression des doublons, conservation d'une seule version statique correctement commentée

#### B. Configuration du Service de Notification
- **Service** : `bastien59960.reactions.notification` dans `config/services.yml`
- **Tag** : `notification.type` correctement configuré
- **Arguments** : user_loader, dbal.conn, core.root_path, user

#### C. Activation/Désactivation des Notifications
- **Fichier** : `ext.php`
- **Méthodes** : `enable_step()`, `disable_step()`, `purge_step()`
- **Action** : Enregistrement automatique du type de notification lors de l'activation

#### D. Système de Notifications à Deux Niveaux
1. **Notifications Immédiates (Cloche)** :
   - Déclenchées instantanément dans `controller/ajax.php`
   - Méthode : `trigger_immediate_notification()`
   - Sans délai anti-spam

2. **Notifications par Email (Différées)** :
   - Gérées par la tâche cron `cron/notification_task.php`
   - Délai anti-spam : 45 minutes par défaut (configurable)
   - Groupement intelligent des réactions

---

## Procédure de Test

### Étape 1 : Vérification de l'Installation

```bash
# 1. Vérifier que l'extension est activée
# Aller dans : ACP > Personnalisation > Gérer les extensions
# Vérifier que "Post Reactions" est activée

# 2. Vérifier les migrations
# Aller dans : ACP > Maintenance > Base de données
# Vérifier que les migrations sont à jour
```

### Étape 2 : Configuration

```bash
# 1. Configurer le délai anti-spam (si nécessaire)
# Aller dans : ACP > Extensions > Reactions
# Vérifier que "bastien59960_reactions_spam_time" = 2700 (45 min)

# 2. Vérifier les limites
# - bastien59960_reactions_max_per_post = 20
# - bastien59960_reactions_max_per_user = 10
```

### Étape 3 : Test des Préférences Utilisateur

```bash
# 1. Se connecter avec un compte utilisateur
# 2. Aller dans : Panneau de l'utilisateur > Préférences du forum
# 3. Cliquer sur "Modifier les préférences des notifications"
# 4. Vérifier que "Post reactions" apparaît dans la liste
# 5. Vérifier les options disponibles :
#    - Notification par cloche (activée par défaut)
#    - Notification par email (activée par défaut)
```

**Résultat attendu** :
- ✅ Le type de notification "Post reactions" doit apparaître
- ✅ Les deux options (cloche + email) doivent être cochées par défaut

### Étape 4 : Test des Notifications Immédiates (Cloche)

```bash
# 1. Créer un message avec l'utilisateur A
# 2. Se connecter avec l'utilisateur B
# 3. Ajouter une réaction au message de l'utilisateur A
# 4. Vérifier immédiatement les notifications de l'utilisateur A
```

**Résultat attendu** :
- ✅ L'utilisateur A doit recevoir une notification par cloche immédiatement
- ✅ Le titre doit afficher : "[Nom utilisateur B] a réagi à votre message"
- ✅ Cliquer sur la notification doit rediriger vers le message

### Étape 5 : Test des Notifications par Email (Différées)

```bash
# 1. Créer un message avec l'utilisateur A
# 2. Se connecter avec l'utilisateur B
# 3. Ajouter une réaction au message de l'utilisateur A
# 4. Attendre 45 minutes (ou modifier le délai dans l'ACP)
# 5. Exécuter le cron manuellement ou attendre son exécution automatique
# 6. Vérifier l'email de l'utilisateur A
```

**Résultat attendu** :
- ✅ L'utilisateur A doit recevoir un email après le délai anti-spam
- ✅ L'email doit contenir :
  - Le nom de l'utilisateur qui a réagi
  - Le titre du message
  - Un lien vers le message
- ✅ Les réactions doivent être marquées comme notifiées dans la base de données

### Étape 6 : Test du Groupement des Notifications

```bash
# 1. Créer un message avec l'utilisateur A
# 2. Ajouter des réactions avec plusieurs utilisateurs (B, C, D)
# 3. Vérifier les notifications de l'utilisateur A
```

**Résultat attendu** :
- ✅ Notification par cloche : affiche "[Nom B] et 2 autre(s) ont réagi"
- ✅ Email : liste les 3 premiers noms + "et X autres" si plus de 3

---

## Vérification de la Base de Données

### Table `phpbb_post_reactions`

```sql
-- Vérifier la structure de la table
DESCRIBE phpbb_post_reactions;

-- Vérifier les réactions existantes
SELECT * FROM phpbb_post_reactions ORDER BY reaction_time DESC LIMIT 10;

-- Vérifier les réactions non notifiées
SELECT * FROM phpbb_post_reactions WHERE reaction_notified = 0;
```

**Colonnes attendues** :
- `reaction_id` : ID unique (auto_increment)
- `post_id` : ID du message
- `topic_id` : ID du sujet
- `user_id` : ID de l'utilisateur qui réagit
- `reaction_emoji` : Emoji (UTF8MB4)
- `reaction_time` : Timestamp
- `reaction_notified` : Flag anti-spam (0 ou 1)

### Table des Notifications

```sql
-- Vérifier les notifications créées
SELECT * FROM phpbb_notifications 
WHERE notification_type_name = 'bastien59960.reactions.notification'
ORDER BY notification_time DESC 
LIMIT 10;
```

---

## Débogage

### Vérifier les Logs

```bash
# 1. Vérifier les logs PHP
tail -f /var/log/php/error.log | grep Reactions

# 2. Vérifier les logs phpBB
# Aller dans : ACP > Maintenance > Logs d'erreurs
```

### Messages de Log Attendus

```
[Reactions RID=xxxxx] add_reaction enter post_id=123 emoji=👍 user_id=2
[Reactions RID=xxxxx] handle() terminé en XXms
```

### Problèmes Courants

#### 1. Les notifications n'apparaissent pas dans les préférences

**Cause** : Le type de notification n'est pas enregistré

**Solution** :
```php
// Désactiver puis réactiver l'extension
// Ou exécuter manuellement :
$notification_manager = $container->get('notification_manager');
$notification_manager->enable_notifications('bastien59960.reactions.notification');
```

#### 2. Les emails ne sont pas envoyés

**Cause** : Le cron ne s'exécute pas ou le délai anti-spam n'est pas écoulé

**Solution** :
```bash
# 1. Vérifier la configuration du cron
# Aller dans : ACP > Système > Tâches cron

# 2. Exécuter le cron manuellement
php bin/phpbbcli.php cron:run

# 3. Vérifier le délai anti-spam
# Aller dans : ACP > Extensions > Reactions
```

#### 3. Erreur "Service not found"

**Cause** : Le service n'est pas correctement déclaré dans `services.yml`

**Solution** :
```yaml
# Vérifier que le service existe dans config/services.yml
bastien59960.reactions.notification:
    class: bastien59960\reactions\notification\type\reaction
    tags:
        - { name: notification.type }
```

#### 4. Les notifications par cloche ne fonctionnent pas

**Cause** : Le `notification_manager` n'est pas injecté dans le contrôleur AJAX

**Solution** :
```php
// Vérifier que le service AJAX a bien le notification_manager
// dans config/services.yml :
bastien59960.reactions.ajax:
    arguments:
        - '@notification_manager'  # Doit être présent
```

---

## Checklist de Validation

### Configuration
- [ ] Extension activée dans l'ACP
- [ ] Migrations exécutées
- [ ] Service de notification enregistré
- [ ] Délai anti-spam configuré (45 min par défaut)

### Préférences Utilisateur
- [ ] Type de notification visible dans les préférences
- [ ] Option "Cloche" disponible et activée par défaut
- [ ] Option "Email" disponible et activée par défaut

### Notifications Immédiates
- [ ] Notification par cloche reçue instantanément
- [ ] Titre correct avec nom de l'utilisateur
- [ ] Lien vers le message fonctionnel

### Notifications par Email
- [ ] Email reçu après le délai anti-spam
- [ ] Contenu correct (noms, titre, lien)
- [ ] Réactions marquées comme notifiées

### Groupement
- [ ] Plusieurs réactions groupées correctement
- [ ] Affichage "et X autres" fonctionnel
- [ ] Limite de 3 noms respectée

### Base de Données
- [ ] Table `phpbb_post_reactions` créée
- [ ] Colonne `reaction_notified` présente
- [ ] Charset UTF8MB4 configuré
- [ ] Notifications enregistrées dans `phpbb_notifications`

---

## Support et Documentation

### Fichiers de Documentation
- `DOCUMENTATION.md` : Documentation technique complète
- `GUIDE_DEVELOPPEMENT.md` : Guide pour les développeurs
- `CHANGELOG.md` : Historique des modifications
- `ACP_TROUBLESHOOTING.md` : Guide de dépannage ACP

### Fichiers de Langue
- `language/fr/common.php` : Langue française
- `language/fr/notification/reaction.php` : Notifications en français
- `language/fr/email/reaction.txt` : Template email français
- `language/en/common.php` : Langue anglaise
- `language/en/notification/reaction.php` : Notifications en anglais
- `language/en/email/reaction.txt` : Template email anglais

### Fichiers Principaux
- `ext.php` : Activation/désactivation des notifications
- `config/services.yml` : Configuration des services
- `notification/type/reaction.php` : Type de notification
- `controller/ajax.php` : Notifications immédiates
- `cron/notification_task.php` : Notifications différées

---

## Contact et Support

Pour toute question ou problème :
1. Vérifier les logs d'erreurs
2. Consulter `ACP_TROUBLESHOOTING.md`
3. Vérifier la configuration dans l'ACP
4. Tester avec les logs de debug activés

**Version de l'extension** : 1.0.1  
**Compatibilité phpBB** : 3.3.0+  
**Auteur** : Bastien59960
