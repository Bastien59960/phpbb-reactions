# Test Rapide - SystÃ¨me de Notifications

## âš¡ Test en 5 Minutes

### Ã‰tape 1 : RÃ©activer l'Extension (30 secondes)
```
1. ACP > Personnalisation > GÃ©rer les extensions
2. Cliquer sur "DÃ©sactiver" pour l'extension "Post Reactions"
3. Cliquer sur "Activer" pour l'extension "Post Reactions"
4. VÃ©rifier qu'aucune erreur n'apparaÃ®t
```
âœ… **RÃ©sultat attendu** : Message "Extension activÃ©e avec succÃ¨s"

---

### Ã‰tape 2 : VÃ©rifier les PrÃ©fÃ©rences (1 minute)
```
1. Se connecter avec un compte utilisateur
2. Aller dans : Panneau de l'utilisateur
3. Cliquer sur : PrÃ©fÃ©rences du forum
4. Cliquer sur : Modifier les prÃ©fÃ©rences des notifications
5. Chercher "Post reactions" dans la liste
```
âœ… **RÃ©sultat attendu** : 
- "Post reactions" est visible
- Case "Notification" cochÃ©e (cloche)
- Case "Email" cochÃ©e

---

### Ã‰tape 3 : Test Notification Cloche (2 minutes)
```
1. Avec l'utilisateur A : CrÃ©er un nouveau message dans un sujet
2. Se dÃ©connecter
3. Se connecter avec l'utilisateur B
4. Ajouter une rÃ©action ðŸ‘ au message de l'utilisateur A
5. Se dÃ©connecter
6. Se reconnecter avec l'utilisateur A
7. Cliquer sur l'icÃ´ne de cloche (notifications)
```
âœ… **RÃ©sultat attendu** : 
- Notification visible : "[Nom B] a rÃ©agi Ã  votre message"
- Cliquer dessus redirige vers le message

---

### Ã‰tape 4 : Test Email (Optionnel - 45 minutes)
```
1. RÃ©pÃ©ter l'Ã©tape 3
2. Attendre 45 minutes
3. VÃ©rifier l'email de l'utilisateur A
```
âœ… **RÃ©sultat attendu** : Email reÃ§u avec le nom de l'utilisateur B et un lien vers le message

---

## ðŸ” VÃ©rification SQL Rapide

```sql
-- VÃ©rifier que le type de notification est enregistrÃ©
SELECT * FROM phpbb_notification_types 
WHERE notification_type_name = 'bastien59960.reactions.notification';
```
âœ… **RÃ©sultat attendu** : 1 ligne retournÃ©e

```sql
-- VÃ©rifier les notifications crÃ©Ã©es
SELECT COUNT(*) FROM phpbb_notifications 
WHERE notification_type_name = 'bastien59960.reactions.notification';
```
âœ… **RÃ©sultat attendu** : Nombre > 0 aprÃ¨s avoir testÃ©

---

## âŒ Si Ã‡a Ne Fonctionne Pas

### ProblÃ¨me : "Post reactions" n'apparaÃ®t pas dans les prÃ©fÃ©rences

**Solution 1** : Forcer l'enregistrement
```php
// Dans phpBB, exÃ©cuter :
$notification_manager = $container->get('notification_manager');
$notification_manager->enable_notifications('bastien59960.reactions.notification');
```

**Solution 2** : VÃ©rifier les logs
```
ACP > Maintenance > Logs d'erreurs
```

**Solution 3** : VÃ©rifier le service
```
Ouvrir : ext/bastien59960/reactions/config/services.yml
Chercher : bastien59960.reactions.notification
VÃ©rifier : tags: - { name: notification.type }
```

---

### ProblÃ¨me : Notification par cloche non reÃ§ue

**VÃ©rifier** :
1. L'utilisateur A a bien activÃ© les notifications dans ses prÃ©fÃ©rences
2. L'utilisateur B n'est pas l'utilisateur A (on ne se notifie pas soi-mÃªme)
3. Les logs d'erreurs : `ACP > Maintenance > Logs d'erreurs`

**Solution** :
```
VÃ©rifier que le service ajax a bien le notification_manager :
Fichier : config/services.yml
Ligne : bastien59960.reactions.ajax
Argument : - '@notification_manager'
```

---

### ProblÃ¨me : Email non reÃ§u

**VÃ©rifier** :
1. Le cron s'exÃ©cute : `ACP > SystÃ¨me > TÃ¢ches cron`
2. Le dÃ©lai anti-spam : `ACP > Extensions > Reactions` (45 min par dÃ©faut)
3. La configuration email du forum : `ACP > GÃ©nÃ©ral > Configuration email`

**Solution** :
```bash
# ExÃ©cuter le cron manuellement
php bin/phpbbcli.php cron:run
```

---

## ðŸ“Š Tableau de Diagnostic

| SymptÃ´me | Cause Probable | Solution |
|----------|----------------|----------|
| Pas dans prÃ©fÃ©rences | Type non enregistrÃ© | DÃ©sactiver/rÃ©activer extension |
| Cloche ne fonctionne pas | Service mal configurÃ© | VÃ©rifier services.yml |
| Email non reÃ§u | Cron ne tourne pas | ExÃ©cuter cron manuellement |
| Erreur PHP | MÃ©thodes dupliquÃ©es | VÃ©rifier reaction.php |

---

## ðŸŽ¯ Checklist Finale

- [ ] Extension activÃ©e sans erreur
- [ ] "Post reactions" visible dans prÃ©fÃ©rences
- [ ] Options "Cloche" et "Email" cochÃ©es par dÃ©faut
- [ ] Notification par cloche reÃ§ue immÃ©diatement
- [ ] Email reÃ§u aprÃ¨s 45 minutes (optionnel)
- [ ] Aucune erreur dans les logs

---

## ðŸ“ž Besoin d'Aide ?

1. **Documentation complÃ¨te** : `GUIDE_TEST_NOTIFICATIONS.md`
2. **DÃ©tails techniques** : `CORRECTIONS_NOTIFICATIONS.md`
3. **RÃ©sumÃ©** : `RESUME_CORRECTIONS.md`
4. **DÃ©pannage ACP** : `ACP_TROUBLESHOOTING.md`

---

**Temps total estimÃ©** : 5 minutes (sans le test email)  
**DifficultÃ©** : â­ Facile  
**PrÃ©requis** : AccÃ¨s ACP + 2 comptes utilisateur
