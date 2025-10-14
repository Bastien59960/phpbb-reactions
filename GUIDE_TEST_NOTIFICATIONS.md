# Guide de test des notifications â€” Extension Reactions

## ðŸ‡«ðŸ‡· Tester les notifications

### 1. Notifications cloche (immÃ©diates)
- Ajoutez une rÃ©action Ã  un message dâ€™un autre utilisateur
- VÃ©rifiez que lâ€™auteur du message reÃ§oit une notification dans la cloche
- VÃ©rifiez que lâ€™auto-notification (rÃ©agir Ã  son propre message) ne dÃ©clenche rien

### 2. Notifications e-mail (digest)
- Activez lâ€™option "RÃ©sumÃ© e-mail" dans les prÃ©fÃ©rences utilisateur
- Ajoutez plusieurs rÃ©actions sur diffÃ©rents messages
- Lancez la tÃ¢che cron (ou attendez le dÃ©lai anti-spam)
- VÃ©rifiez la rÃ©ception du digest e-mail

### 3. Cas particuliers
- DÃ©sactivez les notifications dans le panneau utilisateur et vÃ©rifiez lâ€™absence de notification
- Testez les limites (max rÃ©actions, dÃ©lai anti-spam)

### 4. DÃ©pannage
- VÃ©rifiez les logs dâ€™erreur si une notification nâ€™est pas reÃ§ue
- Purgez le cache si besoin

---

# Notification Testing Guide â€” Reactions Extension

## ðŸ‡¬ðŸ‡§ Testing notifications

### 1. Bell notifications (instant)
- Add a reaction to another userâ€™s post
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
