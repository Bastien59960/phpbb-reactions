# DÃ©pannage ACP â€” Extension Reactions

## ðŸ‡«ðŸ‡· ProblÃ¨mes courants et solutions

### 1. Les rÃ©actions n'apparaissent pas
- Purgez le cache de phpBB
- VÃ©rifiez que l'extension est activÃ©e dans l'ACP
- VÃ©rifiez que la base de donnÃ©es est en UTF8MB4

### 2. Les notifications ne fonctionnent pas
- VÃ©rifiez les prÃ©fÃ©rences utilisateur (UCP)
- VÃ©rifiez les logs d'erreur
- Assurez-vous que la tÃ¢che cron est bien exÃ©cutÃ©e

### 3. ProblÃ¨mes de migration
- VÃ©rifiez que toutes les migrations sont passÃ©es
- VÃ©rifiez la structure de la table `phpbb_post_reactions`

### 4. ProblÃ¨mes d'affichage (CSS/JS)
- Purgez le cache du navigateur
- VÃ©rifiez que les fichiers JS/CSS sont bien chargÃ©s

### 5. Messages d'erreur frÃ©quents
- "Limite de types de rÃ©actions par message atteinte" : augmentez la limite dans l'ACP
- "Invalid emoji" : vÃ©rifiez le support UTF8MB4

### Liens utiles
- [Forum de support](https://bastien.debucquoi.com/forum/)
- [Documentation complÃ¨te](DOCUMENTATION.md)

---

# ACP Troubleshooting â€” Reactions Extension

## ðŸ‡¬ðŸ‡§ Common issues and solutions

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
