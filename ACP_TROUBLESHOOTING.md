# Dépannage ACP — Extension Reactions

## 🇫🇷 Problèmes courants et solutions

### 1. Les réactions n'apparaissent pas
- Purgez le cache de phpBB
- Vérifiez que l'extension est activée dans l'ACP
- Vérifiez que la base de données est en UTF8MB4

### 2. Les notifications ne fonctionnent pas
- Vérifiez les préférences utilisateur (UCP)
- Vérifiez les logs d'erreur
- Assurez-vous que la tâche cron est bien exécutée

### 3. Problèmes de migration
- Vérifiez que toutes les migrations sont passées
- Vérifiez la structure de la table `phpbb_post_reactions`

### 4. Problèmes d'affichage (CSS/JS)
- Purgez le cache du navigateur
- Vérifiez que les fichiers JS/CSS sont bien chargés

### 5. Messages d'erreur fréquents
- "Limite de types de réactions par message atteinte" : augmentez la limite dans l'ACP
- "Invalid emoji" : vérifiez le support UTF8MB4

### Liens utiles
- [Forum de support](https://bastien.debucquoi.com/forum/)
- [Documentation complète](DOCUMENTATION.md)

---

# ACP Troubleshooting — Reactions Extension

## 🇬🇧 Common issues and solutions

### 1. Reactions do not appear
- Purge the phpBB cache
- Check that the extension is enabled in the ACP
- Make sure the database is in UTF8MB4

### 2. Notifications do not work
- Check user preferences (UCP)
- Check error logs
- Make sure the cron task is running

### 3. Migration issues
- Check that all migrations have run
- Check the structure of the `phpbb_post_reactions` table

### 4. Display issues (CSS/JS)
- Purge browser cache
- Check that JS/CSS files are loaded

### 5. Common error messages
- "Post reaction type limit reached": increase the limit in the ACP
- "Invalid emoji": check UTF8MB4 support

### Useful links
- [Support forum](https://bastien.debucquoi.com/forum/)
- [Full documentation](DOCUMENTATION.md)
