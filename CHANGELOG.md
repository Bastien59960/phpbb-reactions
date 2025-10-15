# Changelog - Extension Reactions

Toutes les modifications notables de l'extension Reactions sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-XX

### Ajouté
- **Système de notifications complet** :
  - Notifications par cloche (immédiates)
  - Notifications par email avec délai anti-spam (45 min par défaut)
  - Groupement intelligent des réactions multiples
  - Préférences utilisateur configurables

- **Type de notification personnalisé** :
  - Classe `bastien59960\reactions\notification\type\reaction`
  - Support des notifications groupées
  - Gestion des utilisateurs multiples
  - Templates d'email personnalisés

- **Tâche cron pour les notifications** :
  - Envoi différé des emails avec anti-spam
  - Groupement par message et auteur
  - Marquage automatique des réactions notifiées
  - Gestion des erreurs et reprises

- **Configuration étendue** :
  - Option `bastien59960_reactions_spam_time` pour le délai anti-spam
  - Limites configurables par message et utilisateur
  - Activation/désactivation globale de l'extension

- **Fichiers de langue complets** :
  - Support français et anglais
  - Messages de notification
  - Templates d'email
  - Messages d'erreur et de succès

- **Documentation complète** :
  - Guide de développement
  - Documentation technique
  - Changelog détaillé
  - Commentaires de code exhaustifs

### Modifié
- **Contrôleur AJAX** :
  - Ajout du gestionnaire de notifications
  - Méthode `trigger_immediate_notification()` pour les notifications immédiates
  - Gestion des erreurs améliorée
  - Logs de debug détaillés

- **Listener d'événements** :
  - Configuration des variables de notification
  - Gestion des données utilisateur
  - Optimisation des requêtes

- **Services de configuration** :
  - Ajout du service de notification
  - Configuration des dépendances
  - Tags appropriés pour les services

- **Base de données** :
  - Colonne `reaction_notified` pour l'anti-spam
  - Index optimisés pour les requêtes de notification
  - Support UTF8MB4 pour les emojis composés

### Corrigé
- **Nom du service de notification** :
  - Correction de `bastien59960.reactions.notification.type.reaction` vers `bastien59960.reactions.notification`
  - Harmonisation des noms dans tous les fichiers
  - Correction des références dans les migrations

- **Gestion des notifications** :
  - Distinction claire entre notifications immédiates et différées
  - Correction de la logique anti-spam (emails uniquement)
  - Gestion des erreurs de notification

- **Validation des données** :
  - Amélioration de la validation des emojis
  - Support des emojis composés (ZWJ)
  - Vérification des autorisations

### Sécurité
- **Validation renforcée** :
  - Vérification des jetons CSRF
  - Validation des emojis et des données
  - Protection contre les injections SQL

- **Gestion des erreurs** :
  - Logs détaillés pour le debug
  - Gestion gracieuse des erreurs
  - Protection des données sensibles

## [1.0.0] - 2025-01-XX

### Ajouté
- **Fonctionnalités de base** :
  - Système de réactions aux messages
  - Support des emojis courantes (10 emojis prédéfinis)
  - Support des emojis étendus via fichier JSON
  - Compteurs en temps réel

- **Interface utilisateur** :
  - Affichage des réactions sous chaque message
  - Bouton "plus" pour ouvrir la palette d'emojis
  - Tooltips avec liste des utilisateurs
  - Mode lecture seule pour utilisateurs non connectés

- **Contrôleur AJAX** :
  - Gestion des requêtes d'ajout/suppression de réactions
  - Validation des données et autorisations
  - Gestion des erreurs et états de chargement
  - Support des limites configurables

- **Base de données** :
  - Table `phpbb_post_reactions` pour stocker les réactions
  - Index optimisés pour les performances
  - Support UTF8MB4 pour les emojis

- **Configuration** :
  - Options de configuration dans l'ACP
  - Limites par message et utilisateur
  - Activation/désactivation de l'extension

- **Fichiers de langue** :
  - Support français et anglais
  - Messages d'interface utilisateur
  - Messages d'erreur et de succès

- **Styles et JavaScript** :
  - CSS responsive et moderne
  - JavaScript ES6+ pour l'interactivité
  - Gestion des événements et des états

### Modifié
- **Architecture** :
  - Structure modulaire et extensible
  - Séparation des responsabilités
  - Utilisation des services phpBB

### Corrigé
- **Compatibilité** :
  - Support phpBB 3.3.0+
  - Compatibilité avec les thèmes existants
  - Gestion des conflits de CSS

### Sécurité
- **Validation** :
  - Vérification des autorisations
  - Validation des données d'entrée
  - Protection contre les attaques XSS

## [0.9.0] - 2025-01-XX (Version de développement)

### Ajouté
- **Prototype initial** :
  - Structure de base de l'extension
  - Contrôleur AJAX basique
  - Template d'affichage simple
  - Styles CSS de base

### Modifié
- **Architecture** :
  - Refactoring complet de l'architecture
  - Amélioration de la structure des fichiers
  - Optimisation des performances

### Corrigé
- **Bugs de développement** :
  - Correction des erreurs de syntaxe
  - Amélioration de la gestion des erreurs
  - Optimisation des requêtes de base de données

## Notes de version

### Version 1.0.1
Cette version apporte un système de notifications complet avec support des notifications par cloche et par email. Elle inclut également une documentation exhaustive et des améliorations de sécurité.

### Version 1.0.0
Version stable initiale avec toutes les fonctionnalités de base des réactions. Cette version est prête pour la production.

### Version 0.9.0
Version de développement avec les fonctionnalités de base. Cette version était destinée aux tests et à la validation des concepts.

## Roadmap

### Version 1.1.0 (Prévue)
- **Réactions personnalisées** : Emojis spécifiques au forum
- **Statistiques avancées** : Tableaux de bord des réactions
- **API REST** : Interface pour applications tierces
- **Améliorations de performance** : Cache Redis, optimisations

### Version 1.2.0 (Prévue)
- **Intégration mobile** : Optimisation pour les appareils mobiles
- **WebSockets** : Notifications en temps réel
- **PWA** : Application web progressive
- **Tests automatisés** : Suite de tests complète

### Version 2.0.0 (Prévue)
- **Refactoring majeur** : Architecture modernisée
- **Nouvelles fonctionnalités** : Réactions avancées
- **Compatibilité** : Support phpBB 4.0
- **Performance** : Optimisations majeures

## Support

### Versions supportées
- **Version actuelle** : 1.0.1
- **Versions précédentes** : 1.0.0 (support de sécurité uniquement)
- **Versions de développement** : Non supportées

### Compatibilité
- **phpBB** : 3.3.0+
- **PHP** : 7.4+
- **MySQL** : 5.7+ / MariaDB 10.2+
- **Navigateurs** : Chrome 80+, Firefox 75+, Safari 13+, Edge 80+

### Migration
- **Depuis 1.0.0** : Migration automatique via les migrations phpBB
- **Depuis 0.9.0** : Réinstallation recommandée
- **Sauvegarde** : Toujours effectuer une sauvegarde avant la mise à jour

---

*Ce changelog est maintenu à jour avec chaque version de l'extension.*
