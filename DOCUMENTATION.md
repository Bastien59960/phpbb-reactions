# Documentation de l'extension Reactions pour phpBB

## Vue d'ensemble

L'extension Reactions permet aux utilisateurs de réagir aux messages du forum avec des emojis. Elle inclut un système de notifications complet avec support des notifications par cloche (immédiates) et par email (avec délai anti-spam).

## Fonctionnalités principales

### 🎯 Réactions aux messages
- **Emojis courantes** : 10 emojis prédéfinis (👍, 👎, ❤️, 😂, 😮, 😢, 😡, 🔥, 👌, 🥳)
- **Emojis étendus** : Support de tous les emojis Unicode via un fichier JSON
- **Compteurs en temps réel** : Affichage du nombre de réactions pour chaque emoji
- **Tooltips informatifs** : Liste des utilisateurs ayant réagi avec un emoji spécifique

### 🔔 Système de notifications
- **Notifications par cloche** : Immédiates, sans délai
- **Notifications par email** : Avec délai anti-spam configurable (45 min par défaut)
- **Groupement intelligent** : Les réactions multiples sont groupées par message
- **Préférences utilisateur** : Configuration dans le panneau utilisateur

### ⚙️ Configuration et limites
- **Limites configurables** :
  - Maximum 20 types de réactions par message (par défaut)
  - Maximum 10 réactions par utilisateur et par message (par défaut)
- **Délai anti-spam** : Configurable pour les notifications par email
- **Activation/désactivation** : Contrôle global de l'extension

## Architecture technique

### Structure des fichiers

```
reactions/
├── ext.php                          # Classe principale de l'extension
├── config/
│   ├── services.yml                 # Configuration des services
│   ├── parameters.yml               # Paramètres de l'extension
│   └── routing.yml                  # Routes de l'extension
├── controller/
│   ├── ajax.php                     # Contrôleur AJAX pour les réactions
│   ├── main.php                     # Contrôleur principal
│   └── test.php                     # Contrôleur de test
├── event/
│   └── listener.php                 # Listener d'événements phpBB
├── notification/
│   └── type/
│       └── reaction.php             # Type de notification pour les réactions
├── cron/
│   └── notification_task.php        # Tâche cron pour les notifications par email
├── migrations/
│   ├── release_1_0_0.php            # Migration de base (table des réactions)
│   └── release_1_0_1.php            # Migration de configuration
├── language/
│   ├── fr/
│   │   ├── common.php               # Langue française
│   │   ├── notification/
│   │   │   └── reaction.php         # Notifications en français
│   │   └── email/
│   │       └── reaction.txt         # Template email français
│   └── en/
│       ├── common.php               # Langue anglaise
│       ├── notification/
│       │   └── reaction.php         # Notifications en anglais
│       └── email/
│           └── reaction.txt         # Template email anglais
└── styles/
    └── prosilver/
        ├── template/
        │   ├── event/
        │   │   └── reactions.html   # Template d'affichage des réactions
        │   └── js/
        │       └── reactions.js     # JavaScript pour l'interactivité
        └── theme/
            └── reactions.css        # Styles CSS
```

### Base de données

#### Table `phpbb_post_reactions`
```sql
CREATE TABLE phpbb_post_reactions (
    reaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reaction_emoji VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    reaction_time INT UNSIGNED NOT NULL,
    reaction_notified TINYINT(1) DEFAULT 0,
    INDEX post_id (post_id),
    INDEX topic_id (topic_id),
    INDEX user_id (user_id),
    INDEX post_notified_idx (post_id, reaction_notified)
);
```

### Services et dépendances

#### Services principaux
- **`bastien59960.reactions.listener`** : Gère l'affichage des réactions
- **`bastien59960.reactions.ajax`** : Traite les requêtes AJAX
- **`bastien59960.reactions.notification`** : Type de notification
- **`bastien59960.reactions.notificationtask`** : Tâche cron

#### Dépendances
- **phpBB 3.3.0+** : Version minimale requise
- **UTF8MB4** : Support des emojis composés
- **JavaScript ES6+** : Pour l'interactivité côté client

## Flux de données

### 1. Affichage des réactions
```
Page du forum → Listener → Template → JavaScript → CSS
```

### 2. Ajout d'une réaction
```
Clic utilisateur → JavaScript → AJAX → Contrôleur → Base de données → Notification immédiate
```

### 3. Notifications par email
```
Tâche cron → Vérification délai → Groupement → Envoi email → Marquage notifié
```

## Configuration

### Options de configuration
- **`bastien59960_reactions_enabled`** : Activation de l'extension (0/1)
- **`bastien59960_reactions_max_per_post`** : Max types de réactions par message (défaut: 20)
- **`bastien59960_reactions_max_per_user`** : Max réactions par utilisateur (défaut: 10)
- **`bastien59960_reactions_spam_time`** : Délai anti-spam en secondes (défaut: 2700 = 45 min)

### Préférences utilisateur
Les utilisateurs peuvent configurer leurs notifications dans :
**Panneau utilisateur → Préférences → Modifier les préférences de notification**

## API JavaScript

### Fonctions principales
- **`initReactions()`** : Initialise le système de réactions
- **`handleReactionClick(event)`** : Gère les clics sur les réactions
- **`handleMoreButtonClick(event)`** : Gère l'ouverture de la palette
- **`sendReactionRequest(action, postId, emoji)`** : Envoie les requêtes AJAX

### Événements
- **`click`** : Sur les réactions existantes
- **`click`** : Sur le bouton "plus"
- **`mouseenter/mouseleave`** : Sur les tooltips
- **`click`** : Sur la palette d'emojis

## Sécurité

### Mesures de sécurité
- **Validation CSRF** : Vérification des jetons de session
- **Validation des emojis** : Contrôle de la longueur et des caractères
- **Autorisations** : Vérification des droits d'accès aux messages
- **Limites** : Protection contre le spam de réactions

### Validation côté serveur
- Vérification de l'existence du message
- Contrôle des autorisations utilisateur
- Validation des emojis (longueur, caractères)
- Respect des limites configurées

## Performance

### Optimisations
- **Index de base de données** : Optimisation des requêtes fréquentes
- **Cache des emojis** : Chargement différé des emojis étendus
- **Requêtes groupées** : Minimisation des appels AJAX
- **CSS optimisé** : Styles minimaux et efficaces

### Monitoring
- **Logs détaillés** : Traçabilité des erreurs et performances
- **Chronométrage** : Mesure des temps de réponse
- **Identifiants de requête** : Suivi des opérations

## Maintenance

### Tâches de maintenance
- **Nettoyage des réactions** : Suppression des réactions orphelines
- **Optimisation de la base** : Défragmentation des index
- **Mise à jour des emojis** : Synchronisation avec les standards Unicode

### Debug et diagnostic
- **Mode debug** : Activation des logs détaillés
- **Tests de fonctionnalité** : Contrôleur de test intégré
- **Validation des données** : Vérification de l'intégrité

## Évolutions futures

### Fonctionnalités prévues
- **Réactions personnalisées** : Emojis spécifiques au forum
- **Statistiques avancées** : Tableaux de bord des réactions
- **Intégration mobile** : Optimisation pour les appareils mobiles
- **API REST** : Interface pour applications tierces

### Améliorations techniques
- **Cache Redis** : Amélioration des performances
- **WebSockets** : Notifications en temps réel
- **PWA** : Application web progressive
- **Tests automatisés** : Suite de tests complète

## Support et contribution

### Documentation
- **Code commenté** : Documentation inline complète
- **Exemples d'utilisation** : Cas d'usage typiques
- **Guide de développement** : Instructions pour les contributeurs

### Communauté
- **Issues GitHub** : Signalement des bugs et demandes
- **Pull requests** : Contributions de la communauté
- **Documentation** : Amélioration continue de la documentation

---

*Cette documentation est maintenue à jour avec chaque version de l'extension.*
