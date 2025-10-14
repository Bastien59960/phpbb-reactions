# Changelog - Extension Reactions

Toutes les modifications notables de l'extension Reactions sont documentÃ©es dans ce fichier.

Le format est basÃ© sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhÃ¨re au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-XX

### AjoutÃ©
- **SystÃ¨me de notifications complet** :
  - Notifications par cloche (immÃ©diates)
  - Notifications par email avec dÃ©lai anti-spam (45 min par dÃ©faut)
  - Groupement intelligent des rÃ©actions multiples
  - PrÃ©fÃ©rences utilisateur configurables

- **Type de notification personnalisÃ©** :
  - Classe `bastien59960\reactions\notification\type\reaction`
  - Support des notifications groupÃ©es
  - Gestion des utilisateurs multiples
  - Templates d'email personnalisÃ©s

- **TÃ¢che cron pour les notifications** :
  - Envoi diffÃ©rÃ© des emails avec anti-spam
  - Groupement par message et auteur
  - Marquage automatique des rÃ©actions notifiÃ©es
  - Gestion des erreurs et reprises

- **Configuration Ã©tendue** :
  - Option `bastien59960_reactions_spam_time` pour le dÃ©lai anti-spam
  - Limites configurables par message et utilisateur
  - Activation/dÃ©sactivation globale de l'extension

- **Fichiers de langue complets** :
  - Support franÃ§ais et anglais
  - Messages de notification
  - Templates d'email
  - Messages d'erreur et de succÃ¨s

- **Documentation complÃ¨te** :
  - Guide de dÃ©veloppement
  - Documentation technique
  - Changelog dÃ©taillÃ©
  - Commentaires de code exhaustifs

### ModifiÃ©
- **ContrÃ´leur AJAX** :
  - Ajout du gestionnaire de notifications
  - MÃ©thode `trigger_immediate_notification()` pour les notifications immÃ©diates
  - Gestion des erreurs amÃ©liorÃ©e
  - Logs de debug dÃ©taillÃ©s

- **Listener d'Ã©vÃ©nements** :
  - Configuration des variables de notification
  - Gestion des donnÃ©es utilisateur
  - Optimisation des requÃªtes

- **Services de configuration** :
  - Ajout du service de notification
  - Configuration des dÃ©pendances
  - Tags appropriÃ©s pour les services

- **Base de donnÃ©es** :
  - Colonne `reaction_notified` pour l'anti-spam
  - Index optimisÃ©s pour les requÃªtes de notification
  - Support UTF8MB4 pour les emojis composÃ©s

### CorrigÃ©
- **Nom du service de notification** :
  - Correction de `bastien59960.reactions.notification.type.reaction` vers `bastien59960.reactions.notification`
  - Harmonisation des noms dans tous les fichiers
  - Correction des rÃ©fÃ©rences dans les migrations

- **Gestion des notifications** :
  - Distinction claire entre notifications immÃ©diates et diffÃ©rÃ©es
  - Correction de la logique anti-spam (emails uniquement)
  - Gestion des erreurs de notification

- **Validation des donnÃ©es** :
  - AmÃ©lioration de la validation des emojis
  - Support des emojis composÃ©s (ZWJ)
  - VÃ©rification des autorisations

### SÃ©curitÃ©
- **Validation renforcÃ©e** :
  - VÃ©rification des jetons CSRF
  - Validation des emojis et des donnÃ©es
  - Protection contre les injections SQL

- **Gestion des erreurs** :
  - Logs dÃ©taillÃ©s pour le debug
  - Gestion gracieuse des erreurs
  - Protection des donnÃ©es sensibles

## [1.0.0] - 2025-01-XX

### AjoutÃ©
- **FonctionnalitÃ©s de base** :
  - SystÃ¨me de rÃ©actions aux messages
  - Support des emojis courantes (10 emojis prÃ©dÃ©finis)
  - Support des emojis Ã©tendus via fichier JSON
  - Compteurs en temps rÃ©el

- **Interface utilisateur** :
  - Affichage des rÃ©actions sous chaque message
  - Bouton "plus" pour ouvrir la palette d'emojis
  - Tooltips avec liste des utilisateurs
  - Mode lecture seule pour utilisateurs non connectÃ©s

- **ContrÃ´leur AJAX** :
  - Gestion des requÃªtes d'ajout/suppression de rÃ©actions
  - Validation des donnÃ©es et autorisations
  - Gestion des erreurs et Ã©tats de chargement
  - Support des limites configurables

- **Base de donnÃ©es** :
  - Table `phpbb_post_reactions` pour stocker les rÃ©actions
  - Index optimisÃ©s pour les performances
  - Support UTF8MB4 pour les emojis

- **Configuration** :
  - Options de configuration dans l'ACP
  - Limites par message et utilisateur
  - Activation/dÃ©sactivation de l'extension

- **Fichiers de langue** :
  - Support franÃ§ais et anglais
  - Messages d'interface utilisateur
  - Messages d'erreur et de succÃ¨s

- **Styles et JavaScript** :
  - CSS responsive et moderne
  - JavaScript ES6+ pour l'interactivitÃ©
  - Gestion des Ã©vÃ©nements et des Ã©tats

### ModifiÃ©
- **Architecture** :
  - Structure modulaire et extensible
  - SÃ©paration des responsabilitÃ©s
  - Utilisation des services phpBB

### CorrigÃ©
- **CompatibilitÃ©** :
  - Support phpBB 3.3.0+
  - CompatibilitÃ© avec les thÃ¨mes existants
  - Gestion des conflits de CSS

### SÃ©curitÃ©
- **Validation** :
  - VÃ©rification des autorisations
  - Validation des donnÃ©es d'entrÃ©e
  - Protection contre les attaques XSS

## [0.9.0] - 2025-01-XX (Version de dÃ©veloppement)

### AjoutÃ©
- **Prototype initial** :
  - Structure de base de l'extension
  - ContrÃ´leur AJAX basique
  - Template d'affichage simple
  - Styles CSS de base

### ModifiÃ©
- **Architecture** :
  - Refactoring complet de l'architecture
  - AmÃ©lioration de la structure des fichiers
  - Optimisation des performances

### CorrigÃ©
- **Bugs de dÃ©veloppement** :
  - Correction des erreurs de syntaxe
  - AmÃ©lioration de la gestion des erreurs
  - Optimisation des requÃªtes de base de donnÃ©es

## Notes de version

### Version 1.0.1
Cette version apporte un systÃ¨me de notifications complet avec support des notifications par cloche et par email. Elle inclut Ã©galement une documentation exhaustive et des amÃ©liorations de sÃ©curitÃ©.

### Version 1.0.0
Version stable initiale avec toutes les fonctionnalitÃ©s de base des rÃ©actions. Cette version est prÃªte pour la production.

### Version 0.9.0
Version de dÃ©veloppement avec les fonctionnalitÃ©s de base. Cette version Ã©tait destinÃ©e aux tests et Ã  la validation des concepts.

## Roadmap

### Version 1.1.0 (PrÃ©vue)
- **RÃ©actions personnalisÃ©es** : Emojis spÃ©cifiques au forum
- **Statistiques avancÃ©es** : Tableaux de bord des rÃ©actions
- **API REST** : Interface pour applications tierces
- **AmÃ©liorations de performance** : Cache Redis, optimisations

### Version 1.2.0 (PrÃ©vue)
- **IntÃ©gration mobile** : Optimisation pour les appareils mobiles
- **WebSockets** : Notifications en temps rÃ©el
- **PWA** : Application web progressive
- **Tests automatisÃ©s** : Suite de tests complÃ¨te

### Version 2.0.0 (PrÃ©vue)
- **Refactoring majeur** : Architecture modernisÃ©e
- **Nouvelles fonctionnalitÃ©s** : RÃ©actions avancÃ©es
- **CompatibilitÃ©** : Support phpBB 4.0
- **Performance** : Optimisations majeures

## Support

### Versions supportÃ©es
- **Version actuelle** : 1.0.1
- **Versions prÃ©cÃ©dentes** : 1.0.0 (support de sÃ©curitÃ© uniquement)
- **Versions de dÃ©veloppement** : Non supportÃ©es

### CompatibilitÃ©
- **phpBB** : 3.3.0+
- **PHP** : 7.4+
- **MySQL** : 5.7+ / MariaDB 10.2+
- **Navigateurs** : Chrome 80+, Firefox 75+, Safari 13+, Edge 80+

### Migration
- **Depuis 1.0.0** : Migration automatique via les migrations phpBB
- **Depuis 0.9.0** : RÃ©installation recommandÃ©e
- **Sauvegarde** : Toujours effectuer une sauvegarde avant la mise Ã  jour

---

*Ce changelog est maintenu Ã  jour avec chaque version de l'extension.*
