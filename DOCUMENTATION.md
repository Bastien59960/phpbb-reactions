# Documentation de l'extension Reactions pour phpBB

## ðŸ‡«ðŸ‡· Vue d'ensemble

L'extension Reactions permet aux utilisateurs de rÃ©agir aux messages du forum avec des emojis. Elle inclut un systÃ¨me de notifications complet (cloche + e-mail), une interface moderne et rapide, et une configuration avancÃ©e.

### FonctionnalitÃ©s principales
- RÃ©actions illimitÃ©es (tous les emojis Unicode)
- Palette rapide + palette complÃ¨te
- Compteurs dynamiques et tooltips interactifs
- Notifications immÃ©diates (cloche) et digest e-mail groupÃ©
- PrÃ©fÃ©rences utilisateur (cloche, e-mail, aucune)
- Limites configurables (par post, par utilisateur, anti-spam)
- SÃ©curitÃ© avancÃ©e (CSRF, validation, permissions)
- Multilingue (FR/EN)
- Design responsive et performance optimisÃ©e

### Architecture technique
- ContrÃ´leur AJAX pour toutes les interactions dynamiques
- Listener d'Ã©vÃ©nements pour l'intÃ©gration forum
- Types de notifications personnalisÃ©s
- TÃ¢che cron pour l'envoi des digests e-mail
- Migrations pour la base de donnÃ©es
- Fichiers de langue et templates personnalisÃ©s

### Base de donnÃ©es
- Table `phpbb_post_reactions` : stocke toutes les rÃ©actions (voir migration)
- Index optimisÃ©s pour la performance

### SÃ©curitÃ©
- Validation CSRF, permissions, validation stricte des emojis
- Limites anti-spam et nettoyage automatique

### Performance
- AJAX, index SQL, cache emojis, logs dÃ©taillÃ©s

### Roadmap
- RÃ©actions personnalisÃ©es, statistiques, API REST, WebSockets, PWA, tests automatisÃ©s

### Contribution
- Issues et pull requests bienvenus sur GitHub
- Documentation complÃ¨te dans les fichiers du projet

---

# Reactions Extension Documentation (English)

## ðŸ‡¬ðŸ‡§ Overview

The Reactions extension lets users react to forum posts with emojis. It features a complete notification system (bell + email), a modern and fast interface, and advanced configuration.

### Main features
- Unlimited reactions (all Unicode emojis)
- Quick palette + full palette
- Live counters and interactive tooltips
- Instant notifications (bell) and grouped email digest
- User preferences (bell, email, none)
- Configurable limits (per post, per user, anti-spam)
- Advanced security (CSRF, validation, permissions)
- Multilingual (FR/EN)
- Responsive design and optimized performance

### Technical architecture
- AJAX controller for all dynamic interactions
- Event listener for forum integration
- Custom notification types
- Cron task for email digests
- Database migrations
- Custom language files and templates

### Database
- Table `phpbb_post_reactions`: stores all reactions (see migration)
- Optimized indexes for performance

### Security
- CSRF validation, permissions, strict emoji validation
- Anti-spam limits and automatic cleanup

### Performance
- AJAX, SQL indexes, emoji cache, detailed logs

### Roadmap
- Custom reactions, statistics, REST API, WebSockets, PWA, automated tests

### Contribution
- Issues and pull requests welcome on GitHub
- Full documentation in project files
