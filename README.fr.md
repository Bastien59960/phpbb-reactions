# Bastien59960 Reactions - Extension phpBB 3.3+

[English](README.md)

**Ajoutez un feedback communautaire moderne et utile sous chaque message.**

Quand on veut reagir sans devoir ecrire un post, il faut un systeme simple, rapide et propre. Bastien59960 Reactions apporte a phpBB un systeme robuste de reactions emoji avec UX temps reel, controles de notification, digest email et desinscription securisee.

## Pourquoi l'installer

- Augmenter l'engagement avec un feedback leger sur chaque post.
- Garder des notifications utiles avec delai anti-spam et preferences par membre.
- Offrir une experience AJAX fluide sans rechargement complet de page.
- Garder le controle operationnel depuis l'ACP avec des limites et options de picker configurables.

## Fonctionnalites principales

### UX reactions et synchro live

- Reactions emoji sous les messages, avec compteurs et tooltips utilisateurs.
- Endpoints AJAX d'ajout/suppression pour une interaction rapide.
- Intervalle de synchronisation en arriere-plan configurable.
- Comportement du picker configurable (taille, categories, recherche, chargement complet).

### Notifications et cron digest

- Notifications forum pour les nouvelles reactions.
- Tache cron de digest email, agregee par destinataire et par message.
- Delai anti-spam entre deux digest (reglage ACP).
- Garde-fous d'execution: fenetre de temps et plafond par run.
- Les choix faits dans `UCP > Preferences du forum > Modifier les preferences des notifications` sont synchronises avec les anciens flags utilisateur de l'extension pour eviter les preferences divergentes.

### Gestion de desinscription des digest emails

- Lien de desinscription signe dans les emails digest.
- Endpoint `GET /app.php/reactions/unsubscribe` qui valide le token et met a jour la preference membre.
- Reponses HTTP explicites (`200`, `403`, `404`) et message utilisateur adapte.
- Integration optionnelle avec les logs AdminHelper (`unsubscribe_type = reactions_notify`) si disponible.

### Controles ACP et UCP

- Reglages ACP pour les limites et l'affichage:
  - nombre max de types de reactions par post
  - nombre max de reactions par membre et par post
  - delai du digest
  - parametres du picker et de l'affichage
- Support des preferences UCP pour opt-in/opt-out digest email.
- Les lignes visibles dans la page standard `ucp_notifications` pilotent les notifications reactions (cloche et digest email).
- L'ancien panneau UCP reactions et le lien de desinscription digest restent synchronises avec ces preferences pour compatibilite.

### Hygiene des donnees

- Nettoyage des candidats orphelins/auto-reactions pendant le cron.
- Marquage des elements traites pour eviter les notifications repetees.

## Dépendances inter-extensions

| Extension | Rôle | Type |
|---|---|---|
| `bastien59960/adminhelper` | Lit `phpbb3_post_reactions` pour les statistiques de notifications et les actions de maintenance email dans l'ACP | **Consommateur optionnel** — reactions expose sa table ; adminhelper l'utilise uniquement si elle existe (`sql_table_exists`) |

`reactions` ne dépend d'**aucune autre extension** du projet.

## Prerequis

- PHP `>= 7.4.0`
- phpBB `>= 3.3.0`
- MySQL/MariaDB en `utf8mb4` recommande pour une couverture emoji complete

## Installation

1. Copier `bastien59960/reactions` dans `ext/`.
2. Activer l'extension:

```bash
php bin/phpbbcli.php extension:enable bastien59960/reactions
```

## Mise a jour

Apres mise a jour des fichiers:

```bash
php bin/phpbbcli.php db:migrate
php bin/phpbbcli.php cache:purge
```

## Desinstallation

```bash
php bin/phpbbcli.php extension:disable bastien59960/reactions
php bin/phpbbcli.php extension:purge bastien59960/reactions
```

## Configuration ACP rapide

Dans **ACP > Extensions > Reactions**:

- Regler le delai du digest (`bastien59960_reactions_spam_time`).
- Regler les limites par post et par membre.
- Ajuster largeur/hauteur/taille des icones du picker.
- Activer/desactiver categories, recherche et chargement complet JSON.
- Regler l'intervalle de rafraichissement de synchro.

## Cron et commandes utiles

### Lancer le cron phpBB

```bash
php /var/www/forum/bin/phpbbcli.php cron:run
```

### Lancer uniquement la tache digest reactions

```bash
php /var/www/forum/bin/phpbbcli.php cron:run cron.task.bastien59960.reactions.notification
```

### Script local de diagnostic cron

```bash
bash ext/bastien59960/reactions/tools/check-crons.sh
```

### Crontab systeme recommande (fiabilite production)

Le cron web phpBB est fragile : si `$task->run()` leve une exception, le `cron_lock`
(DB) peut rester orphelin jusqu'a 3600s, bloquant tous les crons du forum.

**Solution deployee :** L'extension `bastien59960/adminhelper` surcharge le service
`cron.event_listener` avec un `try-finally` garantissant la liberation du lock.
Un watchdog crontab reinitialise les locks orphelins toutes les 5 minutes.

En complement, une entree crontab systeme assure l'envoi des digests meme si le
cron web est temporairement bloque :

```bash
# Crontab root (crontab -e)
# Watchdog cron_lock phpBB (ext adminhelper)
*/5 * * * * bash /var/www/forum/ext/bastien59960/adminhelper/tools/cron_watchdog.sh

# Digest reactions par cron systeme (backup du cron web phpBB)
*/45 * * * * cd /var/www/forum && php bin/phpbbcli.php cron:run bastien59960.reactions.notification >> /var/log/reactions_cron.log 2>&1
```

## Donnees stockees (resume)

Points de stockage principaux:

- table `post_reactions`: evenements reactions, emojis, horodatage et flags digest.
- `users.user_reactions_notify` et `users.user_reactions_cron_email`: flags de compatibilite historiques, maintenus synchronises avec phpBB.
- tables notifications phpBB: source de verite pour les preferences cloche/email visibles dans `ucp_notifications`.

## Securite et vie privee

- Protections CSRF et controles de permissions sur les actions reactions.
- Token de desinscription base sur signature HMAC.
- Endpoint digest unsubscribe limite a la preference email reactions.
- Aucun secret serveur ou token API stocke dans les fichiers de l'extension.

## Limites connues

- Fidelite emoji complete dependante du parametrage DB en `utf8mb4`.
- Delai de livraison email depend du cron phpBB et de la queue.
- Integration logs AdminHelper conditionnelle a la presence de sa table de logs.

## Licence

[GPL-2.0-only](LICENSE)

## Auteur

**Bastien** (`bastien59960`)
