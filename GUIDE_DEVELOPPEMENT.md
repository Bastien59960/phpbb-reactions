# Guide de développement de l'extension Reactions

## 🇫🇷 Pour les développeurs

### Structure du code
- `controller/` : Contrôleurs AJAX, UCP, etc.
- `event/` : Listener d'événements phpBB
- `notification/` : Types de notifications personnalisés
- `cron/` : Tâche cron pour les digests e-mail
- `migrations/` : Migrations de base de données
- `language/` : Fichiers de langue (fr, en)
- `styles/` : Templates, JS, CSS

### Points d'entrée principaux
- `controller/ajax.php` : Toutes les requêtes AJAX (ajout, retrait, affichage)
- `notification/type/reaction.php` : Notification cloche
- `notification/type/reaction_email_digest.php` : Notification digest e-mail
- `cron/notification_task.php` : Envoi périodique des digests
- `event/listener.php` : Intégration avec le forum

### Bonnes pratiques
- Respecter la structure MVC de phpBB
- Utiliser les services déclarés dans `config/services.yml`
- Documenter chaque fichier et chaque méthode
- Garder les fichiers de langue synchronisés (FR/EN)
- Utiliser les logs pour le debug

### Contribution
- Forkez le repo, créez une branche, ouvrez une pull request
- Décrivez clairement vos changements
- Ajoutez des tests si possible

### Tests & debug
- Utilisez le contrôleur de test si besoin
- Activez le mode debug pour plus de logs
- Vérifiez les migrations et la cohérence de la base

---

# Reactions Extension Development Guide (English)

## 🇬🇧 For developers

### Code structure
- `controller/`: AJAX, UCP, etc. controllers
- `event/`: phpBB event listener
- `notification/`: Custom notification types
- `cron/`: Cron task for email digests
- `migrations/`: Database migrations
- `language/`: Language files (fr, en)
- `styles/`: Templates, JS, CSS

### Main entry points
- `controller/ajax.php`: All AJAX requests (add, remove, display)
- `notification/type/reaction.php`: Bell notification
- `notification/type/reaction_email_digest.php`: Email digest notification
- `cron/notification_task.php`: Periodic digest sending
- `event/listener.php`: Forum integration

### Best practices
- Follow phpBB's MVC structure
- Use services declared in `config/services.yml`
- Document every file and method
- Keep language files in sync (FR/EN)
- Use logs for debugging

### Contribution
- Fork the repo, create a branch, open a pull request
- Clearly describe your changes
- Add tests if possible

### Tests & debug
- Use the test controller if needed
- Enable debug mode for more logs
- Check migrations and database consistency
