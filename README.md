# ðŸš€ phpBB Reactions â€” Extension dâ€™Ã‰mojis Ultra-ComplÃ¨te pour phpBB 3.3+

Ajoutez une dimension sociale et moderne Ã  votre forum phpBBâ€¯: laissez vos membres rÃ©agir Ã  chaque message avec lâ€™intÃ©gralitÃ© des Ã©mojis Unicodeâ€¯!  
**ExpÃ©rience fluide, notifications intelligentes, personnalisation avancÃ©e, et performance garantie.**

---

## âœ¨ FonctionnalitÃ©s phares

- **RÃ©actions illimitÃ©es** : Support complet de tous les Ã©mojis Unicode (ðŸ‘ â¤ï¸ ðŸ˜‚ ðŸ‘Ž â€¦), y compris les emojis composÃ©s et les derniÃ¨res nouveautÃ©s.
- **Palette intelligente** : 10 emojis courants en accÃ¨s rapide, palette Ã©tendue pour tous les autres.
- **Multi-rÃ©actions** : Jusquâ€™Ã  10 rÃ©actions diffÃ©rentes par utilisateur et par post (paramÃ©trable).
- **Compteurs dynamiques** : Affichage en temps rÃ©el du nombre de rÃ©actions par emoji sous chaque message.
- **Tooltips interactifs** : Survolez un emoji pour voir qui a rÃ©agi.
- **AJAX ultra-rapide** : Toutes les interactions (ajout, retrait, affichage) sont instantanÃ©es, sans rechargement de page.
- **Notifications puissantes** :
  - **Cloche** : Notification immÃ©diate dans le forum.
  - **RÃ©sumÃ© e-mail** : Digest pÃ©riodique groupÃ©, anti-spam, personnalisable par lâ€™utilisateur.
- **PrÃ©fÃ©rences utilisateur** : Chaque membre choisit sâ€™il veut recevoir des notifications (cloche, e-mail, ou aucune).
- **Limites configurables** : Nombre max de rÃ©actions par post, par utilisateur, dÃ©lai anti-spamâ€¦ tout est ajustable dans lâ€™ACP.
- **SÃ©curitÃ© avancÃ©e** : Protection CSRF, validation stricte des emojis, contrÃ´le des permissions, anti-spam natif.
- **Support multilingue** : FranÃ§ais et anglais inclus, facilement extensible.
- **Design responsive** : Parfaitement intÃ©grÃ© Ã  prosilver, compatible mobile/tablette.
- **Logs & debug** : Suivi dÃ©taillÃ© des actions et erreurs pour un diagnostic facile.

---

## ðŸ–¼ï¸ AperÃ§u

*(Ajoutez ici une capture dâ€™Ã©cran si souhaitÃ©)*

---

## ðŸ› ï¸ Installation rapide

1. **PrÃ©-requis** : phpBB 3.3.10+ (UTF8MB4 activÃ© sur la base de donnÃ©es)
2. **TÃ©lÃ©chargement** : [DerniÃ¨re version sur GitHub](https://github.com/bastien59960/reactions)
3. **DÃ©ploiement** :
   - DÃ©zippez dans `ext/bastien59960/reactions/`
   - Activez lâ€™extension dans lâ€™ACP > Personnalisation > GÃ©rer les extensions
4. **Configuration** :
   - Rendez-vous dans lâ€™ACP > Extensions > Post Reactions pour ajuster les paramÃ¨tres (limites, activation, etc.)
   - Les utilisateurs peuvent gÃ©rer leurs prÃ©fÃ©rences dans leur panneau utilisateur

---

## ðŸ“ FonctionnalitÃ©s dÃ©taillÃ©es

### RÃ©actions & interface
- Palette dâ€™emojis rapide et palette complÃ¨te
- Affichage des rÃ©actions sous chaque post
- Tooltips avec la liste des utilisateurs ayant rÃ©agi
- Ajout/retrait de rÃ©action en un clic (AJAX)

### Notifications
- **Cloche** : Notification immÃ©diate Ã  lâ€™auteur du message (hors auto-rÃ©action)
- **RÃ©sumÃ© e-mail** : Digest groupÃ©, anti-spam (dÃ©lai configurable, par dÃ©faut 45 min)
- PrÃ©fÃ©rences individuelles (activer/dÃ©sactiver chaque type)

### Configuration & personnalisation
- Limites par post et par utilisateur (modifiables dans lâ€™ACP)
- Activation/dÃ©sactivation globale de lâ€™extension
- DÃ©lai anti-spam pour les notifications e-mail
- Support complet des emojis Unicode (utf8mb4 requis)

### SÃ©curitÃ© & robustesse
- Validation CSRF sur toutes les requÃªtes
- Validation stricte des emojis (longueur, unicitÃ©, caractÃ¨res)
- Permissions phpBB respectÃ©es (seuls les membres autorisÃ©s peuvent rÃ©agir)
- Nettoyage automatique des notifications orphelines

### Performance
- Index SQL optimisÃ©s
- RequÃªtes AJAX groupÃ©es
- Cache des emojis
- Logs dÃ©taillÃ©s pour le debug

---

## ðŸ“¦ Structure du projet

```
reactions/
â”œâ”€â”€ ext.php
â”œâ”€â”€ config/           # Services, paramÃ¨tres, routes
â”œâ”€â”€ controller/       # ContrÃ´leurs AJAX, UCP, etc.
â”œâ”€â”€ event/            # Listener dâ€™Ã©vÃ©nements phpBB
â”œâ”€â”€ notification/     # Types de notifications personnalisÃ©s
â”œâ”€â”€ cron/             # TÃ¢che cron pour les digests e-mail
â”œâ”€â”€ migrations/       # Migrations de base de donnÃ©es
â”œâ”€â”€ language/         # Fichiers de langue (fr, en)
â”œâ”€â”€ styles/           # Templates, JS, CSS
â””â”€â”€ ...
```

---

## ðŸ”’ SÃ©curitÃ© & bonnes pratiques

- Validation CSRF et permissions Ã  chaque Ã©tape
- Limites anti-spam configurables
- Logs dâ€™erreur et de performance
- Nettoyage automatique des donnÃ©es orphelines

---

## ðŸš¦ Roadmap & Ã©volutions prÃ©vues

- RÃ©actions personnalisÃ©es (emojis propres au forum)
- Statistiques avancÃ©es (tableaux de bord, top rÃ©actions)
- IntÃ©gration mobile et PWA
- API REST pour applications tierces
- Notifications en temps rÃ©el (WebSockets)
- Import/export des rÃ©actions
- Tests automatisÃ©s

---

## ðŸ¤ Contribution & support

- **Bugs, suggestions, contributions** : ouvrez une issue ou une pull request sur GitHub
- **Documentation complÃ¨te** : voir le dossier `/docs` et les fichiers `DOCUMENTATION.md`, `CONFIGURATION.md`
- **CommunautÃ©** : [Forum de support](https://bastien.debucquoi.com/forum/)

---

## ðŸ“„ Licence

GNU General Public License v2.0  
(c) 2025 Bastien59960

---

*Rejoignez la communautÃ©, testez, contribuez, et faites de votre forum un espace vivant et interactifâ€¯!*

---

# ðŸš€ phpBB Reactions â€” The Ultimate Emoji Extension for phpBB 3.3+ (English)

Bring your phpBB forum to life: let your members react to every post with the full range of Unicode emojis!  
**Smooth experience, smart notifications, advanced customization, and top performance.**

---

## âœ¨ Key Features

- **Unlimited reactions**: Full support for all Unicode emojis (ðŸ‘ â¤ï¸ ðŸ˜‚ ðŸ‘Ž â€¦), including composed and latest emojis.
- **Smart palette**: 10 quick-access emojis, full palette for all others.
- **Multi-reactions**: Up to 10 different reactions per user and per post (configurable).
- **Live counters**: Real-time display of reaction counts per emoji under each post.
- **Interactive tooltips**: Hover an emoji to see who reacted.
- **Ultra-fast AJAX**: All interactions (add, remove, display) are instant, no page reload.
- **Powerful notifications**:
  - **Bell**: Instant in-forum notification.
  - **Email digest**: Periodic grouped digest, anti-spam, user-customizable.
- **User preferences**: Each member chooses which notifications to receive (bell, email, or none).
- **Configurable limits**: Max reactions per post, per user, anti-spam delayâ€¦ all adjustable in the ACP.
- **Advanced security**: CSRF protection, strict emoji validation, permission checks, built-in anti-spam.
- **Multilingual**: French and English included, easily extensible.
- **Responsive design**: Perfectly integrated with prosilver, mobile/tablet ready.
- **Logs & debug**: Detailed action and error logs for easy diagnostics.

---

## ðŸ–¼ï¸ Preview

*(Add a screenshot here if desired)*

---

## ðŸ› ï¸ Quick Installation

1. **Requirements**: phpBB 3.3.10+ (UTF8MB4 enabled on the database)
2. **Download**: [Latest version on GitHub](https://github.com/bastien59960/reactions)
3. **Deployment**:
   - Unzip into `ext/bastien59960/reactions/`
   - Enable the extension in ACP > Customise > Manage extensions
4. **Configuration**:
   - Go to ACP > Extensions > Post Reactions to adjust settings (limits, activation, etc.)
   - Users can manage their preferences in their user control panel

---

## ðŸ“ Detailed Features

### Reactions & Interface
- Quick and full emoji palette
- Display of reactions under each post
- Tooltips with the list of users who reacted
- Add/remove reaction in one click (AJAX)

### Notifications
- **Bell**: Instant notification to the post author (except self-reaction)
- **Email digest**: Grouped digest, anti-spam (configurable delay, default 45 min)
- Individual preferences (enable/disable each type)

### Configuration & Customization
- Limits per post and per user (modifiable in ACP)
- Global enable/disable of the extension
- Anti-spam delay for email notifications
- Full Unicode emoji support (utf8mb4 required)

### Security & Robustness
- CSRF validation on all requests
- Strict emoji validation (length, uniqueness, characters)
- phpBB permissions respected (only authorized members can react)
- Automatic cleanup of orphan notifications

### Performance
- Optimized SQL indexes
- Grouped AJAX requests
- Emoji cache
- Detailed logs for debugging

---

## ðŸ“¦ Project Structure

```
reactions/
â”œâ”€â”€ ext.php
â”œâ”€â”€ config/           # Services, parameters, routes
â”œâ”€â”€ controller/       # AJAX, UCP, etc. controllers
â”œâ”€â”€ event/            # phpBB event listener
â”œâ”€â”€ notification/     # Custom notification types
â”œâ”€â”€ cron/             # Cron task for email digests
â”œâ”€â”€ migrations/       # Database migrations
â”œâ”€â”€ language/         # Language files (fr, en)
â”œâ”€â”€ styles/           # Templates, JS, CSS
â””â”€â”€ ...
```

---

## ðŸ”’ Security & Best Practices

- CSRF and permission checks at every step
- Configurable anti-spam limits
- Error and performance logs
- Automatic cleanup of orphan data

---

## ðŸš¦ Roadmap & Upcoming Features

- Custom reactions (forum-specific emojis)
- Advanced statistics (dashboards, top reactions)
- Mobile integration and PWA
- REST API for third-party apps
- Real-time notifications (WebSockets)
- Import/export of reactions
- Automated tests

---

## ðŸ¤ Contribution & Support

- **Bugs, suggestions, contributions**: open an issue or pull request on GitHub
- **Full documentation**: see `/docs` and the files `DOCUMENTATION.md`, `CONFIGURATION.md`
- **Community**: [Support forum](https://bastien.debucquoi.com/forum/)

---

## ðŸ“„ License

GNU General Public License v2.0  
(c) 2025 Bastien59960

---

*Join the community, test, contribute, and make your forum a lively and interactive space!*
