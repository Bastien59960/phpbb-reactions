# phpBB Reactions Extension

Extension **phpBB 3.3.x** permettant aux utilisateurs de réagir aux posts avec l'intégralité des **émojis Unicode** (👍 ❤️ 😂 👎 …).

Elle est conçue pour être **rapide et moderne**, utilisant l'AJAX pour toutes les interactions, sans rechargement de page.

## ✨ Fonctionnalités Clés

* **Réactions illimitées** : Support complet des émojis Unicode (nécessite UTF8MB4).
* **Multi-réactions** : Les utilisateurs peuvent laisser jusqu'à **10 réactions différentes** par post.
* **Comptabilité** : Affichage des compteurs de réactions sous chaque post.
* **Détails en survol** : Liste des utilisateurs ayant réagi visible au survol de l'émoji (pour les utilisateurs connectés).
* **Performance** : Interactions gérées par **AJAX** pour une expérience fluide.
* **Système de notifications** : Intégration complète avec le système de notifications de phpBB.

## 🚧 État Actuel du Développement (Alpha)

L'extension est fonctionnelle pour l'ajout/retrait de réactions, mais des travaux majeurs sont en cours :

1.  **BUG CRITIQUE à corriger :** Les réactions enregistrées en base de données **ne sont pas chargées lors du rafraîchissement** de la page (`viewtopic`).
2.  **MIGRATION BDD requise :** La colonne `reaction_emoji` de la table `phpbb_post_reactions` doit impérativement être migrée en **`utf8mb4_bin`** pour supporter les émojis 4-octets sans erreur.
3.  **Optimisation Mobile :** Le sélecteur d'émojis (Pickup) est en cours de développement pour n'afficher que les 10 émojis les plus courantes par défaut.

## ⚙️ Installation

Cette extension nécessite **phpBB 3.3.15** ou ultérieure.

1.  **Téléchargement :** Téléchargez la dernière version de l'extension (fichier ZIP).
2.  **Structure des dossiers :**
    * Dézippez le contenu du ZIP dans un répertoire temporaire.
    * Le dossier principal est nommé `phpbb-reactions-main`. **Renommez ce dossier en `reactions`**.
    * Dans le répertoire `ext/` de votre forum phpBB, créez un dossier nommé **`bastien59960`** (si ce n'est pas déjà fait).
    * Copiez le dossier **`reactions`** renommé à l'intérieur de `ext/bastien59960/`.
    * Le chemin final doit être : `ext/bastien59960/reactions/`.
3.  **Activation :** Rendez-vous dans le **Panneau de Configuration Administrateur (PCA)** :
    * **Personnalisation** → **Gérer les extensions**.
    * Recherchez `phpBB Reactions Extension` et cliquez sur **Activer**.

## 🛠️ Contribution

Les contributions (Pull Requests, Issues) sont les bienvenues pour accélérer la résolution des bugs critiques et l'ajout de fonctionnalités !

## 📝 À venir (Roadmap)

* **Fonctionnalité d'import** pour migrer les anciennes réactions (si existantes) vers le nouveau système.
* Interface d'administration (ACP) pour la gestion des paramètres (limites, émojis par défaut, etc.).
* Support des WebSockets pour une mise à jour **en temps réel** sans *polling* AJAX.
