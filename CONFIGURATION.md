# Configuration de l'extension Reactions

## 🇫🇷 Paramètres de configuration

L'extension utilise plusieurs paramètres stockés dans la table `phpbb_config` :

### Paramètres principaux

| Paramètre                                 | Valeur par défaut | Description                                                        |
|-------------------------------------------|------------------|--------------------------------------------------------------------|
| `bastien59960_reactions_enabled`          | `1`              | Active/désactive l'extension (1 = activé, 0 = désactivé)           |
| `bastien59960_reactions_max_per_post`     | `20`             | Nombre max de types de réactions différents par post                |
| `bastien59960_reactions_max_per_user`     | `10`             | Nombre max de réactions par utilisateur et par post                 |
| `bastien59960_reactions_spam_time`        | `2700`           | Délai anti-spam (en secondes) pour le digest e-mail (défaut 45 min) |

### Configuration via l'ACP

1. Allez dans **Administration > Extensions > Post Reactions**
2. Modifiez les valeurs selon vos besoins
3. Sauvegardez

### Configuration manuelle (SQL)

```sql
-- Activer l'extension
UPDATE phpbb_config SET config_value = '1' WHERE config_name = 'bastien59960_reactions_enabled';
-- Limite de types de réactions par post
UPDATE phpbb_config SET config_value = '20' WHERE config_name = 'bastien59960_reactions_max_per_post';
-- Limite de réactions par utilisateur
UPDATE phpbb_config SET config_value = '10' WHERE config_name = 'bastien59960_reactions_max_per_user';
-- Délai anti-spam (en secondes)
UPDATE phpbb_config SET config_value = '2700' WHERE config_name = 'bastien59960_reactions_spam_time';
```

### Limites et comportement

- **max_per_post** : Empêche qu'un post ait trop de types de réactions différents (20 par défaut)
- **max_per_user** : Empêche qu'un utilisateur réagisse trop souvent sur le même post (10 par défaut)
- **spam_time** : Délai minimal entre deux digests e-mail pour un même utilisateur

### Messages d'erreur

- "Limite de types de réactions par message atteinte"
- "Limite de réactions par utilisateur atteinte"

### Migration automatique

Les paramètres sont créés lors de l'installation de l'extension (migration).

### Recommandations

- **Petits forums** : Gardez les valeurs par défaut
- **Forums très actifs** : Augmentez les limites si besoin
- **Forums avec modération stricte** : Diminuez les limites pour éviter le spam

### Dépannage

1. Vérifiez que les paramètres existent dans `phpbb_config`
2. Purgez le cache de phpBB
3. Vérifiez les logs d'erreur
4. Testez avec l'URL de test si disponible

---

# Reactions Extension Configuration (English)

## 🇬🇧 Configuration parameters

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
