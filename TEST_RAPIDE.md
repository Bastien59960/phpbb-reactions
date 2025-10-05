# Test Rapide - Système de Notifications

## ⚡ Test en 5 Minutes

### Étape 1 : Réactiver l'Extension (30 secondes)
```
1. ACP > Personnalisation > Gérer les extensions
2. Cliquer sur "Désactiver" pour l'extension "Post Reactions"
3. Cliquer sur "Activer" pour l'extension "Post Reactions"
4. Vérifier qu'aucune erreur n'apparaît
```
✅ **Résultat attendu** : Message "Extension activée avec succès"

---

### Étape 2 : Vérifier les Préférences (1 minute)
```
1. Se connecter avec un compte utilisateur
2. Aller dans : Panneau de l'utilisateur
3. Cliquer sur : Préférences du forum
4. Cliquer sur : Modifier les préférences des notifications
5. Chercher "Post reactions" dans la liste
```
✅ **Résultat attendu** : 
- "Post reactions" est visible
- Case "Notification" cochée (cloche)
- Case "Email" cochée

---

### Étape 3 : Test Notification Cloche (2 minutes)
```
1. Avec l'utilisateur A : Créer un nouveau message dans un sujet
2. Se déconnecter
3. Se connecter avec l'utilisateur B
4. Ajouter une réaction 👍 au message de l'utilisateur A
5. Se déconnecter
6. Se reconnecter avec l'utilisateur A
7. Cliquer sur l'icône de cloche (notifications)
```
✅ **Résultat attendu** : 
- Notification visible : "[Nom B] a réagi à votre message"
- Cliquer dessus redirige vers le message

---

### Étape 4 : Test Email (Optionnel - 45 minutes)
```
1. Répéter l'étape 3
2. Attendre 45 minutes
3. Vérifier l'email de l'utilisateur A
```
✅ **Résultat attendu** : Email reçu avec le nom de l'utilisateur B et un lien vers le message

---

## 🔍 Vérification SQL Rapide

```sql
-- Vérifier que le type de notification est enregistré
SELECT * FROM phpbb_notification_types 
WHERE notification_type_name = 'bastien59960.reactions.notification';
```
✅ **Résultat attendu** : 1 ligne retournée

```sql
-- Vérifier les notifications créées
SELECT COUNT(*) FROM phpbb_notifications 
WHERE notification_type_name = 'bastien59960.reactions.notification';
```
✅ **Résultat attendu** : Nombre > 0 après avoir testé

---

## ❌ Si Ça Ne Fonctionne Pas

### Problème : "Post reactions" n'apparaît pas dans les préférences

**Solution 1** : Forcer l'enregistrement
```php
// Dans phpBB, exécuter :
$notification_manager = $container->get('notification_manager');
$notification_manager->enable_notifications('bastien59960.reactions.notification');
```

**Solution 2** : Vérifier les logs
```
ACP > Maintenance > Logs d'erreurs
```

**Solution 3** : Vérifier le service
```
Ouvrir : ext/bastien59960/reactions/config/services.yml
Chercher : bastien59960.reactions.notification
Vérifier : tags: - { name: notification.type }
```

---

### Problème : Notification par cloche non reçue

**Vérifier** :
1. L'utilisateur A a bien activé les notifications dans ses préférences
2. L'utilisateur B n'est pas l'utilisateur A (on ne se notifie pas soi-même)
3. Les logs d'erreurs : `ACP > Maintenance > Logs d'erreurs`

**Solution** :
```
Vérifier que le service ajax a bien le notification_manager :
Fichier : config/services.yml
Ligne : bastien59960.reactions.ajax
Argument : - '@notification_manager'
```

---

### Problème : Email non reçu

**Vérifier** :
1. Le cron s'exécute : `ACP > Système > Tâches cron`
2. Le délai anti-spam : `ACP > Extensions > Reactions` (45 min par défaut)
3. La configuration email du forum : `ACP > Général > Configuration email`

**Solution** :
```bash
# Exécuter le cron manuellement
php bin/phpbbcli.php cron:run
```

---

## 📊 Tableau de Diagnostic

| Symptôme | Cause Probable | Solution |
|----------|----------------|----------|
| Pas dans préférences | Type non enregistré | Désactiver/réactiver extension |
| Cloche ne fonctionne pas | Service mal configuré | Vérifier services.yml |
| Email non reçu | Cron ne tourne pas | Exécuter cron manuellement |
| Erreur PHP | Méthodes dupliquées | Vérifier reaction.php |

---

## 🎯 Checklist Finale

- [ ] Extension activée sans erreur
- [ ] "Post reactions" visible dans préférences
- [ ] Options "Cloche" et "Email" cochées par défaut
- [ ] Notification par cloche reçue immédiatement
- [ ] Email reçu après 45 minutes (optionnel)
- [ ] Aucune erreur dans les logs

---

## 📞 Besoin d'Aide ?

1. **Documentation complète** : `GUIDE_TEST_NOTIFICATIONS.md`
2. **Détails techniques** : `CORRECTIONS_NOTIFICATIONS.md`
3. **Résumé** : `RESUME_CORRECTIONS.md`
4. **Dépannage ACP** : `ACP_TROUBLESHOOTING.md`

---

**Temps total estimé** : 5 minutes (sans le test email)  
**Difficulté** : ⭐ Facile  
**Prérequis** : Accès ACP + 2 comptes utilisateur
