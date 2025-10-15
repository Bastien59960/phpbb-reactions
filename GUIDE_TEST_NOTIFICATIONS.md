# Guide de test des notifications — Extension Reactions

## 🇫🇷 Tester les notifications

### 1. Notifications cloche (immédiates)
- Ajoutez une réaction à un message d'un autre utilisateur
- Vérifiez que l'auteur du message reçoit une notification dans la cloche
- Vérifiez que l'auto-notification (réagir à son propre message) ne déclenche rien

### 2. Notifications e-mail (digest)
- Activez l'option "Résumé e-mail" dans les préférences utilisateur
- Ajoutez plusieurs réactions sur différents messages
- Lancez la tâche cron (ou attendez le délai anti-spam)
- Vérifiez la réception du digest e-mail

### 3. Cas particuliers
- Désactivez les notifications dans le panneau utilisateur et vérifiez l'absence de notification
- Testez les limites (max réactions, délai anti-spam)

### 4. Dépannage
- Vérifiez les logs d'erreur si une notification n'est pas reçue
- Purgez le cache si besoin

---

# Notification Testing Guide — Reactions Extension

## 🇬🇧 Testing notifications

### 1. Bell notifications (instant)
- Add a reaction to another user's post
- Check that the post author receives a bell notification
- Check that self-reaction does not trigger a notification

### 2. Email notifications (digest)
- Enable the "Email digest" option in user preferences
- Add several reactions to different posts
- Run the cron task (or wait for the anti-spam delay)
- Check for the digest email

### 3. Special cases
- Disable notifications in the user panel and check that no notification is received
- Test the limits (max reactions, anti-spam delay)

### 4. Troubleshooting
- Check error logs if a notification is not received
- Purge the cache if needed
