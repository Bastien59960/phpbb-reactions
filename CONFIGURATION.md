# Configuration de l'extension Reactions

## Paramètres de configuration

L'extension utilise plusieurs paramètres de configuration stockés dans la table `phpbb_config` :

### Paramètres principaux

| Paramètre | Valeur par défaut | Description |
|-----------|------------------|-------------|
| `bastien59960_reactions_enabled` | `1` | Active/désactive l'extension (1 = activé, 0 = désactivé) |
| `bastien59960_reactions_max_per_post` | `20` | Nombre maximum de types de réactions différents par post |
| `bastien59960_reactions_max_per_user` | `10` | Nombre maximum de réactions par utilisateur et par post |

### Configuration via l'ACP

Pour modifier ces paramètres :

1. Allez dans **Administration > Extensions > Post Reactions**
2. Modifiez les valeurs selon vos besoins
3. Sauvegardez les modifications

### Configuration manuelle (base de données)

```sql
-- Activer l'extension
UPDATE phpbb_config SET config_value = '1' WHERE config_name = 'bastien59960_reactions_enabled';

-- Limite de types de réactions par post
UPDATE phpbb_config SET config_value = '20' WHERE config_name = 'bastien59960_reactions_max_per_post';

-- Limite de réactions par utilisateur
UPDATE phpbb_config SET config_value = '10' WHERE config_name = 'bastien59960_reactions_max_per_user';
```

## Comportement des limites

### Limite par post (`max_per_post`)
- Empêche qu'un post ait trop de types de réactions différents
- Par défaut : 20 types maximum
- Exemple : Si un post a déjà 20 émojis différents, aucun nouvel emoji ne peut être ajouté

### Limite par utilisateur (`max_per_user`)
- Empêche qu'un utilisateur réagisse trop souvent sur le même post
- Par défaut : 10 réactions maximum par utilisateur et par post
- Exemple : Si un utilisateur a déjà réagi 10 fois sur un post, il ne peut plus réagir

## Messages d'erreur

L'extension affiche des messages d'erreur appropriés quand les limites sont atteintes :

- **Français** : "Limite de types de réactions par message atteinte"
- **Anglais** : "Post reaction type limit reached"

## Migration automatique

Les paramètres sont automatiquement créés lors de l'installation de l'extension via la migration `release_1_0_1.php`.

## Recommandations

- **Petits forums** : Gardez les valeurs par défaut (20/10)
- **Forums très actifs** : Augmentez les limites si nécessaire
- **Forums avec modération stricte** : Diminuez les limites pour éviter le spam

## Dépannage

Si les limites ne fonctionnent pas :

1. Vérifiez que les paramètres existent dans `phpbb_config`
2. Purgez le cache de phpBB
3. Vérifiez les logs d'erreur
4. Testez avec l'URL de test : `/app.php/reactions/test`
