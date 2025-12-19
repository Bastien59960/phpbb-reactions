#!/bin/bash
# ==============================================================================
# Script de nettoyage MANUEL des migrations corrompues
# ==============================================================================

DB_USER="phpmyadmin"
DB_NAME="bastien-phpbb"

echo "🔑 Mot de passe MySQL pour $DB_USER :"
read -s MYSQL_PASSWORD
echo ""

echo "🗑️  Suppression MANUELLE des entrées de migration de l'extension..."
echo "    Cela permettra une réinstallation propre."
echo ""

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'SQL_EOF'
-- Afficher les migrations actuelles de l'extension
SELECT '=== MIGRATIONS ACTUELLES ===' AS '';
SELECT migration_name, migration_start_time, migration_end_time 
FROM phpbb_migrations 
WHERE migration_name LIKE '%bastien59960%reactions%'
ORDER BY migration_name;

-- Supprimer TOUTES les entrées de migration de l'extension
DELETE FROM phpbb_migrations 
WHERE migration_name LIKE '%bastien59960%reactions%';

SELECT CONCAT('✅ ', ROW_COUNT(), ' migration(s) supprimée(s)') AS result;

-- Afficher les types de notifications
SELECT '=== TYPES DE NOTIFICATIONS ===' AS '';
SELECT notification_type_id, notification_type_name, notification_type_enabled
FROM phpbb_notification_types
WHERE notification_type_name LIKE '%reaction%';

-- Afficher l'état de l'extension
SELECT '=== ÉTAT DE L''EXTENSION ===' AS '';
SELECT ext_name, ext_active, ext_state
FROM phpbb_ext
WHERE ext_name = 'bastien59960/reactions';

SQL_EOF

echo ""
echo "✅ Nettoyage terminé !"
echo ""
echo "Maintenant, vous pouvez :"
echo "1. Tenter de réactiver l'extension : php bin/phpbbcli.php extension:enable bastien59960/reactions"
echo "2. Ou continuer avec votre script forum-purge.sh"
