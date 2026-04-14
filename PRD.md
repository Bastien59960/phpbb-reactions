# Reactions - PRD & Documentation technique

> Extension phpBB `bastien59960/reactions`
> Derniere mise a jour : 2026-03-10

---

## Objectif

Fournir un systeme de reactions emoji fiable sous phpBB avec:
- notifications forum immediates;
- digest email periodique anti-spam;
- lien de desinscription securise.

---

## Comportement UCP attendu

- Les preferences de notifications reactions doivent etre gerees dans la page native phpBB:
  `https://forum.debucquoi.com/ucp.php?i=ucp_notifications&mode=notification_options`
- L'extension ne doit pas exposer de menu lateral UCP dedie `UCP_REACTIONS_SETTINGS`.
- Migration de correction deja prevue: `migrations/release_1_0_3.php`
  - supprime automatiquement le module UCP reactions separe s'il existe.

---

## Deploiement manuel (cron)

Le digest email reactions doit etre execute en cron systeme (ne pas dependre uniquement du cron web phpBB):

```cron
*/45 * * * * cd /var/www/forum && php bin/phpbbcli.php cron:run bastien59960.reactions.notification >> /var/log/reactions_cron.log 2>&1
```

Commande utile:

```bash
php /var/www/forum/bin/phpbbcli.php cron:run bastien59960.reactions.notification
```

---

## Donnees et configuration

### Tables/colonnes utilisees

- `phpbb3_post_reactions` (reactions, horodatage, flags de notification)
- `phpbb3_users.user_reactions_notify`
- `phpbb3_users.user_reactions_cron_email`
- `phpbb3_user_notifications` (preferences par methode)
- `phpbb3_notifications` / `phpbb3_notification_types`

### Configs principales (`phpbb3_config`)

- `bastien59960_reactions_enabled`
- `bastien59960_reactions_spam_time`
- `bastien59960_reactions_cron_delay_minutes`
- `bastien59960_reactions_sync_interval`
- `bastien59960_reactions_max_per_post`
- `bastien59960_reactions_max_per_user`

---

## Logs d'exploitation

- `/var/log/reactions_cron.log` : execution cron du digest reactions.
- `/var/log/phpbb_cron_watchdog.log` : watchdog cron_lock (extension `adminhelper`).

---

## Validation post-deploiement

1. Verifier que les lignes reactions existent dans `ucp_notifications` (mode `notification_options`).
2. Verifier qu'il n'y a plus d'entree laterale `UCP_REACTIONS_SETTINGS`.
3. Lancer un cron digest manuel et verifier l'ecriture dans `/var/log/reactions_cron.log`.

## Dépendances inter-extensions

### Exposée à (consommateurs)

- **`adminhelper`** : lit `phpbb3_post_reactions` (colonnes `reaction_notified`, `reaction_email_sent`, `reaction_time`) depuis son ACP pour afficher des statistiques de notifications et permettre des actions de maintenance (marquer en attente, restaurer les abonnements email). La dépendance est purement optionnelle côté consommateur : adminhelper utilise `sql_table_exists()` avant tout accès.
- **`stats`** : trace dans ses propres tables si les assets CSS/JS de reactions ont été chargés par session (`reactions_extension_expected`, `reactions_css_seen`, `reactions_js_seen`). Aucun accès direct aux tables de reactions.

### Dépend de

Aucune. `reactions` est entièrement autonome vis-à-vis des autres extensions du projet.
