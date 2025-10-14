# Configuration de l'extension Reactions

## ðŸ‡«ðŸ‡· ParamÃ¨tres de configuration

L'extension utilise plusieurs paramÃ¨tres stockÃ©s dans la table `phpbb_config` :

### ParamÃ¨tres principaux

| ParamÃ¨tre                                 | Valeur par dÃ©faut | Description                                                        |
|-------------------------------------------|------------------|--------------------------------------------------------------------|
| `bastien59960_reactions_enabled`          | `1`              | Active/dÃ©sactive l'extension (1 = activÃ©, 0 = dÃ©sactivÃ©)           |
| `bastien59960_reactions_max_per_post`     | `20`             | Nombre max de types de rÃ©actions diffÃ©rents par post                |
| `bastien59960_reactions_max_per_user`     | `10`             | Nombre max de rÃ©actions par utilisateur et par post                 |
| `bastien59960_reactions_spam_time`        | `2700`           | DÃ©lai anti-spam (en secondes) pour le digest e-mail (dÃ©faut 45 min) |

### Configuration via l'ACP

1. Allez dans **Administration > Extensions > Post Reactions**
2. Modifiez les valeurs selon vos besoins
3. Sauvegardez

### Configuration manuelle (SQL)

```sql
-- Activer l'extension
UPDATE phpbb_config SET config_value = '1' WHERE config_name = 'bastien59960_reactions_enabled';
-- Limite de types de rÃ©actions par post
UPDATE phpbb_config SET config_value = '20' WHERE config_name = 'bastien59960_reactions_max_per_post';
-- Limite de rÃ©actions par utilisateur
UPDATE phpbb_config SET config_value = '10' WHERE config_name = 'bastien59960_reactions_max_per_user';
-- DÃ©lai anti-spam (en secondes)
UPDATE phpbb_config SET config_value = '2700' WHERE config_name = 'bastien59960_reactions_spam_time';
```

### Limites et comportement

- **max_per_post** : EmpÃªche qu'un post ait trop de types de rÃ©actions diffÃ©rents (20 par dÃ©faut)
- **max_per_user** : EmpÃªche qu'un utilisateur rÃ©agisse trop souvent sur le mÃªme post (10 par dÃ©faut)
- **spam_time** : DÃ©lai minimal entre deux digests e-mail pour un mÃªme utilisateur

### Messages d'erreur

- "Limite de types de rÃ©actions par message atteinte"
- "Limite de rÃ©actions par utilisateur atteinte"

### Migration automatique

Les paramÃ¨tres sont crÃ©Ã©s lors de l'installation de l'extension (migration).

### Recommandations

- **Petits forums** : Gardez les valeurs par dÃ©faut
- **Forums trÃ¨s actifs** : Augmentez les limites si besoin
- **Forums avec modÃ©ration stricte** : Diminuez les limites pour Ã©viter le spam

### DÃ©pannage

1. VÃ©rifiez que les paramÃ¨tres existent dans `phpbb_config`
2. Purgez le cache de phpBB
3. VÃ©rifiez les logs d'erreur
4. Testez avec l'URL de test si disponible

---

# Reactions Extension Configuration (English)

## ðŸ‡¬ðŸ‡§ Configuration parameters

The extension uses several parameters stored in the `phpbb_config` table:

### Main parameters

| Parameter                                 | Default value | Description                                                        |
|--------------------------------------------|--------------|--------------------------------------------------------------------|
| `bastien59960_reactions_enabled`           | `1`          | Enable/disable the extension (1 = enabled, 0 = disabled)           |
| `bastien59960_reactions_max_per_post`      | `20`         | Max number of different reaction types per post                    |
| `bastien59960_reactions_max_per_user`      | `10`         | Max number of reactions per user per post                          |
| `bastien59960_reactions_spam_time`         | `2700`       | Anti-spam delay (in seconds) for email digest (default 45 min)     |

### Configuration via ACP

1. Go to **Administration > Extensions > Post Reactions**
2. Edit the values as needed
3. Save

### Manual configuration (SQL)

```sql
-- Enable the extension
UPDATE phpbb_config SET config_value = '1' WHERE config_name = 'bastien59960_reactions_enabled';
-- Limit of reaction types per post
UPDATE phpbb_config SET config_value = '20' WHERE config_name = 'bastien59960_reactions_max_per_post';
-- Limit of reactions per user
UPDATE phpbb_config SET config_value = '10' WHERE config_name = 'bastien59960_reactions_max_per_user';
-- Anti-spam delay (in seconds)
UPDATE phpbb_config SET config_value = '2700' WHERE config_name = 'bastien59960_reactions_spam_time';
```

### Limits and behavior

- **max_per_post**: Prevents a post from having too many different reaction types (default 20)
- **max_per_user**: Prevents a user from reacting too many times to the same post (default 10)
- **spam_time**: Minimum delay between two email digests for the same user

### Error messages

- "Post reaction type limit reached"
- "User reaction limit reached"

### Automatic migration

Parameters are created during extension installation (migration).

### Recommendations

- **Small forums**: Keep default values
- **Very active forums**: Increase limits if needed
- **Strictly moderated forums**: Lower limits to avoid spam

### Troubleshooting

1. Check that the parameters exist in `phpbb_config`
2. Purge the phpBB cache
3. Check error logs
4. Test with the test URL if available
