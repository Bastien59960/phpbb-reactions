# Bastien59960 Reactions - phpBB 3.3+ Extension

[Français](README.fr.md)

**Add modern, high-signal community feedback to every post.**

As forum content written by real users becomes more valuable, engagement quality matters more than vanity metrics. Bastien59960 Reactions gives phpBB administrators a robust emoji reaction system with live UX, notification controls, digest emails, and unsubscribe-safe delivery flows.

## Why install it

- Increase member engagement with lightweight feedback on every post.
- Keep notifications useful with anti-spam digest timing and per-user preferences.
- Provide a smooth AJAX experience without full page reloads.
- Keep operational control from ACP with configurable limits and picker behavior.

## Key features

### Reactions UX and live sync

- Emoji reactions under posts with counters and user tooltips.
- AJAX add/remove endpoints for fast interaction.
- Configurable background sync interval for near-real-time updates.
- Configurable picker behavior (size, categories, search, full emoji set loading).

### Notifications and digest cron

- In-forum notifications for new reactions.
- Email digest cron task grouping reactions by recipient and by post.
- Anti-spam delay between digests (ACP setting).
- Run-time safeguards: processing window and per-run cap.
- Choices made in `UCP > Board preferences > Edit notification options` are synchronized with the extension's legacy per-user flags so visible checkboxes and actual behavior stay aligned.

### Unsubscribe handling for digest emails

- Signed unsubscribe link in reaction digest emails.
- `GET /app.php/reactions/unsubscribe` endpoint validates token and updates user preference.
- Outcome-aware HTTP responses (`200`, `403`, `404`) and user messaging.
- Optional integration with AdminHelper log table (`unsubscribe_type = reactions_notify`) when available.

### ACP and UCP controls

- ACP settings for limits and display behavior:
  - max reaction types per post
  - max reactions per user per post
  - digest delay
  - picker and display parameters
- UCP preference support for email digest opt-in/out.
- The visible lines in the standard `ucp_notifications` page drive reactions notifications (bell and digest email).
- The legacy reactions UCP panel and digest unsubscribe link remain synchronized with those preferences for backward compatibility.

### Data hygiene

- Cleans up orphan/self-reaction notification candidates during cron processing.
- Marks handled reaction items to avoid repeated notifications.

## Inter-extension dependencies

| Extension | Role | Type |
|---|---|---|
| `bastien59960/adminhelper` | Reads `phpbb3_post_reactions` for notification statistics and email maintenance in ACP | **Optional consumer** — reactions exposes its table; adminhelper uses it only if the table exists (`sql_table_exists`) |

`reactions` itself has **no dependency** on any other extension in this project.

## Requirements

- PHP `>= 7.4.0`
- phpBB `>= 3.3.0`
- MySQL/MariaDB with `utf8mb4` recommended for full emoji coverage

## Installation

1. Copy `bastien59960/reactions` into `ext/`.
2. Enable the extension:

```bash
php bin/phpbbcli.php extension:enable bastien59960/reactions
```

## Update

After updating files:

```bash
php bin/phpbbcli.php db:migrate
php bin/phpbbcli.php cache:purge
```

## Uninstall

```bash
php bin/phpbbcli.php extension:disable bastien59960/reactions
php bin/phpbbcli.php extension:purge bastien59960/reactions
```

## Quick ACP setup

In **ACP > Extensions > Reactions**:

- Set digest delay (`bastien59960_reactions_spam_time`).
- Set per-post and per-user reaction limits.
- Tune picker width/height/icon sizes.
- Enable/disable category tabs, search, and full emoji JSON loading.
- Set refresh interval for live sync.

## Cron and useful commands

### Run phpBB cron

```bash
php /var/www/forum/bin/phpbbcli.php cron:run
```

### Run only reactions digest task

```bash
php /var/www/forum/bin/phpbbcli.php cron:run cron.task.bastien59960.reactions.notification
```

### Basic local cron diagnostics script

```bash
bash ext/bastien59960/reactions/tools/check-crons.sh
```

## Stored data (summary)

Main storage points:

- `post_reactions` table: reaction events, emojis, timestamps, and digest handling flags.
- `users.user_reactions_notify` and `users.user_reactions_cron_email`: legacy compatibility flags kept in sync with phpBB.
- phpBB notifications tables: source of truth for bell/email preferences shown in `ucp_notifications`.

## Security and privacy

- CSRF protections and permission checks apply to reaction actions.
- Unsubscribe token uses HMAC signature derived from forum secret material.
- Digest unsubscribe endpoint updates only reaction email preference.
- No API keys or server secrets are stored in extension files.

## Known limits

- Full emoji fidelity depends on `utf8mb4` database configuration.
- Email delivery timing depends on phpBB queue and cron regularity.
- AdminHelper log integration is conditional on AdminHelper log table availability.

## License

[GPL-2.0-only](LICENSE)

## Author

**Bastien** (`bastien59960`)
