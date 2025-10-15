# Documentation de l'extension Reactions pour phpBB

## 🇫🇷 Vue d'ensemble

L'extension Reactions permet aux utilisateurs de réagir aux messages du forum avec des emojis. Elle inclut un système de notifications complet (cloche + e-mail), une interface moderne et rapide, et une configuration avancée.

### Fonctionnalités principales
- Réactions illimitées (tous les emojis Unicode)
- Palette rapide + palette complète
- Compteurs dynamiques et tooltips interactifs
- Notifications immédiates (cloche) et digest e-mail groupé
- Préférences utilisateur (cloche, e-mail, aucune)
- Limites configurables (par post, par utilisateur, anti-spam)
- Sécurité avancée (CSRF, validation, permissions)
- Multilingue (FR/EN)
- Design responsive et performance optimisée

### Architecture technique
- Contrôleur AJAX pour toutes les interactions dynamiques
- Listener d'événements pour l'intégration forum
- Types de notifications personnalisés
- Tâche cron pour l'envoi des digests e-mail
- Migrations pour la base de données
- Fichiers de langue et templates personnalisés

### Base de données
- Table `phpbb_post_reactions` : stocke toutes les réactions (voir migration)
- Index optimisés pour la performance

### Sécurité
- Validation CSRF, permissions, validation stricte des emojis
- Limites anti-spam et nettoyage automatique

### Performance
- AJAX, index SQL, cache emojis, logs détaillés

### Roadmap
- Réactions personnalisées, statistiques, API REST, WebSockets, PWA, tests automatisés

### Contribution
- Issues et pull requests bienvenus sur GitHub
- Documentation complète dans les fichiers du projet

---

# Reactions Extension Documentation (English)

## 🇬🇧 Overview

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
