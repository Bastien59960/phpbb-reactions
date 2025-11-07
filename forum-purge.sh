#!/bin/bash
# ==============================================================================
# Fichier : forum-purge.sh
# Auteur : Bastien (bastien59960)
# GitHub : https://github.com/bastien59960/reactions
#
# Rôle :
# Script de maintenance complet pour le forum phpBB. Il effectue un cycle
# complet de nettoyage du cache, de réinitialisation de l'extension "Reactions"
# et de vérification de l'état final. Conçu pour accélérer le débogage.
# ==============================================================================

# ==============================================================================
# CONFIGURATION
# ==============================================================================
FORUM_ROOT="/home/bastien/www/forum"
DB_USER="phpmyadmin"
DB_NAME="bastien-phpbb"

# --- Couleurs ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
WHITE_ON_RED='\033[1;41;37m'
NC='\033[0m'

# ==============================================================================
# FUNCTION
# ==============================================================================

# Fonction de vérification améliorée
check_status() {
    local exit_code=$?
    local step_description=$1 # e.g., "Nettoyage du cache de production."
    local output=$2           # Full output of the command

    # Vérifie si la sortie contient une erreur fatale PHP
    if echo "$output" | grep -q -E "PHP Fatal error|PHP Parse error"; then
        echo -e "${WHITE_ON_RED}❌ ERREUR FATALE DÉTECTÉE lors de l'étape : $step_description${NC}"
        echo -e "${WHITE_ON_RED}   Détails de l'erreur :${NC}"
        echo "$output" | grep -E "PHP Fatal error|PHP Parse error" | sed 's/^/   /' # Indent error line
        echo -e "${NC}" # Réinitialise la couleur après l'erreur
        exit 1
    # Puis vérifie le code de sortie
    elif [ $exit_code -ne 0 ]; then
        echo -e "${WHITE_ON_RED}❌ ERREUR (CODE DE SORTIE NON NUL) lors de l'étape : $step_description${NC}"
        echo -e "${NC}" # Réinitialise la couleur
        exit 1
    # Si tout va bien
    else
        echo -e "${GREEN}✅ SUCCÈS : $step_description${NC}"
    fi
}

# ==============================================================================
# START
# ==============================================================================

clear
echo -e "            .-\"\"\"-."
echo -e "           /       \\"
echo -e "           \\.---. ./"
echo -e "           ( @ @ )    👾 SPACE INVADER MODE ENGAGED"
echo -e "    _..oooO--(_)--Oooo.._\n"

echo -e "╔══════════════════════════════════════════════════════════════╗"
echo -e "║   ⚙️  MAINTENANCE PHPBB — RESET CRON & EXTENSION RELOAD       ║"
echo -e "║      (Powered by Bastien – goth sysadmin edition 🦇)           ║"
echo -e "╚══════════════════════════════════════════════════════════════╝"
echo -e "🚀 Lancement du script de maintenance (ordre validé).\n"
sleep 0.2

# ==============================================================================
# DEMANDE DU MOT DE PASSE MYSQL (UNE SEULE FOIS)
# ==============================================================================
echo -e "🔑 Veuillez entrer le mot de passe MySQL pour l'utilisateur ${YELLOW}$DB_USER${NC} :"
read -s MYSQL_PASSWORD # -s pour masquer l'entrée
echo "" # Nouvelle ligne après l'entrée masquée



# ==============================================================================
# 1️⃣ NETTOYAGE AGRESSIF DU CACHE
# ==============================================================================
echo "───[ 1️⃣  NETTOYAGE AGRESSIF DU CACHE & STORE ]────────────────────────"
sleep 0.2

# Suppression de TOUT le contenu du cache de production pour forcer une reconstruction complète
rm -vrf "$FORUM_ROOT/cache/production/"*
check_status "Nettoyage du cache de production."

# Suppression de TOUT le contenu du store (sauf .htaccess et index.htm)
find "$FORUM_ROOT/store" -mindepth 1 -not -name ".htaccess" -not -name "index.htm" -exec rm -vrf {} +
check_status "Nettoyage du store."

# Rétablissement des permissions pour éviter les erreurs d'écriture
chmod -vR 777 "$FORUM_ROOT/cache/"
chmod -vR 777 "$FORUM_ROOT/store/"
check_status "Permissions de cache/store rétablies (777)."

# ==============================================================================
# 2️⃣ FORCER LA RÉINITIALISATION DE L'ÉTAT DE L'EXTENSION
# ==============================================================================
echo "───[ 2️⃣  FORCER LA RÉINITIALISATION DE L'ÉTAT DE L'EXTENSION ]──────────"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<EOF
-- Sauvegarde des migrations avant suppression pour diagnostic
CREATE TEMPORARY TABLE IF NOT EXISTS temp_migrations_backup AS 
SELECT * FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';

-- Afficher les migrations qui seront supprimées
SELECT 'Migrations à supprimer:' AS info;
SELECT migration_name, migration_depends_on FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';

-- Supprimer l'extension et ses migrations
DELETE FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions';
DELETE FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';
DELETE FROM phpbb_config WHERE config_name = 'ext_bastien59960_reactions_version';

-- Vérifier que les suppressions ont bien eu lieu
SELECT 'Vérification après suppression:' AS info;
SELECT COUNT(*) as remaining_count FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';
EOF

check_status "État de l'extension réinitialisé dans la base de données."

# Vérifier que les migrations ont bien été supprimées
echo ""
echo "🔍 Vérification de la suppression des migrations..."
MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -e "SELECT COUNT(*) as count FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';" | tail -1 | while read count; do
    if [ "$count" = "0" ]; then
        echo "✅ Toutes les migrations ont été supprimées (count: $count)"
    else
        echo "⚠️  Il reste $count migration(s) en base de données"
        echo "   Affichage des migrations restantes :"
        MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -e "SELECT migration_name FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';"
    fi
done

# Vérifier les fichiers de migration manquants
echo ""
echo "🔍 Vérification des fichiers de migration..."
MIGRATION_DIR="$FORUM_ROOT/ext/bastien59960/reactions/migrations"
if [ -d "$MIGRATION_DIR" ]; then
    echo "📁 Répertoire migrations trouvé : $MIGRATION_DIR"
    echo "📋 Fichiers présents :"
    ls -1 "$MIGRATION_DIR"/*.php 2>/dev/null | while read file; do
        echo "   ✅ $(basename "$file")"
    done
else
    echo "❌ Répertoire migrations introuvable : $MIGRATION_DIR"
fi
echo ""


# ==============================================================================
# 3️⃣ DÉSACTIVATION DE L'EXTENSION
# ==============================================================================
echo "───[ 3️⃣  DÉSACTIVATION DE L'EXTENSION (bastien59960/reactions) ]────────────"
sleep 0.2

# On tente de désactiver l'extension. On ajoute `|| true` pour que le script ne
# s'arrête pas si l'extension est déjà désactivée (ce qui produit une erreur).
# Le script devient ainsi "ré-exécutable" même après un échec.
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:disable bastien59960/reactions -vvv 2>&1 || true)
check_status "Tentative de désactivation de l'extension terminée." "$output"

# ==============================================================================
# 4️⃣ PURGE DES DONNÉES DE L'EXTENSION
# ==============================================================================
echo "───[ 4️⃣  PURGE MANUELLE DES DONNÉES (SANS SUPPRIMER LA TABLE) ]──────"
sleep 0.2
echo "   (Le mot de passe a été demandé au début du script.)"

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'MANUAL_PURGE_EOF'
-- Suppression des configurations de l'extension (votre version, plus précise)
DELETE FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%';
DELETE FROM phpbb_config WHERE config_name = 'reactions_ucp_preferences_installed';

-- Suppression des modules UCP de l'extension (votre version)
DELETE FROM phpbb_modules WHERE module_basename LIKE '%reactions%';

-- Suppression des types de notification de l'extension (votre version, plus sûre)
DELETE FROM phpbb_notification_types WHERE notification_type_name LIKE 'notification.type.reaction%';

-- Confirmation
SELECT '✅ Purge manuelle des configs, modules et types de notif terminée.' AS status;
MANUAL_PURGE_EOF
check_status "Purge manuelle des données de l'extension (tables de réactions CONSERVÉES)."

# ==============================================================================
# 5️⃣ PURGE CACHE (APRÈS DÉSACTIVATION)
# ==============================================================================
echo "───[ 5️⃣  PURGE DU CACHE (APRÈS DÉSACTIVATION) ]────────────────────"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé après désactivation." "$output"


# ==============================================================================
# 6️⃣ SUPPRESSION FICHIER cron.lock
# ==============================================================================
echo "───[ 6️⃣  SUPPRESSION DU FICHIER cron.lock ]──────────────────────"
sleep 0.2
CRON_LOCK_FILE="$FORUM_ROOT/cache/cron.lock"
if [ -f "$CRON_LOCK_FILE" ]; then
    rm -f "$CRON_LOCK_FILE"
    check_status "Fichier cron.lock supprimé."
else
    echo -e "${GREEN}ℹ️  Aucun cron.lock trouvé (déjà absent).${NC}"
fi

# ==============================================================================
# 6️⃣.5️⃣ NETTOYAGE DES MIGRATIONS PROBLÉMATIQUES (TOUTES EXTENSIONS)
# ==============================================================================
echo "───[ 6️⃣.5️⃣  NETTOYAGE DES MIGRATIONS PROBLÉMATIQUES ]───────────────────"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo "🔍 Recherche de migrations avec dépendances non-array (cause array_merge error)..."
echo ""

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'CLEANUP_EOF'
-- Détecter les migrations problématiques (dépendances non-array)
SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔍 MIGRATIONS PROBLÉMATIQUES DÉTECTÉES' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    migration_name,
    LEFT(migration_depends_on, 80) as depends_preview,
    CASE 
        WHEN migration_depends_on LIKE 'a:%' THEN '✅ ARRAY'
        WHEN migration_depends_on LIKE 's:%' THEN '❌ STRING (PROBLÉMATIQUE)'
        WHEN migration_depends_on IS NULL THEN 'NULL'
        WHEN migration_depends_on = '' THEN 'EMPTY'
        ELSE '❓ OTHER (PROBLÉMATIQUE)'
    END as type_detected
FROM phpbb_migrations
WHERE (migration_depends_on LIKE 's:%' 
       OR (migration_depends_on NOT LIKE 'a:%' 
           AND migration_depends_on NOT LIKE 's:%'
           AND migration_depends_on IS NOT NULL 
           AND migration_depends_on != ''))
ORDER BY migration_name;

-- Supprimer les migrations problématiques (sauf celles de notre extension déjà supprimées)
SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🗑️  SUPPRESSION DES MIGRATIONS PROBLÉMATIQUES' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

DELETE FROM phpbb_migrations
WHERE (migration_depends_on LIKE 's:%' 
       OR (migration_depends_on NOT LIKE 'a:%' 
           AND migration_depends_on NOT LIKE 's:%'
           AND migration_depends_on IS NOT NULL 
           AND migration_depends_on != ''))
  AND migration_name NOT LIKE '%bastien59960%reactions%';

SELECT CONCAT('✅ Migrations problématiques supprimées (', ROW_COUNT(), ' ligne(s))') AS result;
CLEANUP_EOF

check_status "Nettoyage des migrations problématiques terminé."

# ==============================================================================
# 7️⃣ SQL RESET – UN SEUL PROMPT
# ==============================================================================
echo "───[ 7️⃣  RÉINITIALISATION SQL (CRON & NOTIFICATIONS) ]──────────"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<EOF
UPDATE phpbb_config SET config_value = 0 WHERE config_name = 'cron_lock';
UPDATE phpbb_post_reactions SET reaction_notified = 0;
EOF

check_status "Requêtes SQL exécutées : reaction_notified + cron_lock."
check_status "Verrou du cron réinitialisé en base de données."

# ==============================================================================
# 7️⃣.5️⃣ DIAGNOSTIC SQL AVANT RÉACTIVATION
# ==============================================================================
echo "───[ 7️⃣.5️⃣  DIAGNOSTIC SQL (AVANT RÉACTIVATION) ]──────────────────────"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo ""

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'DIAGNOSTIC_EOF'
-- ============================================================================
-- DIAGNOSTIC COMPLET DE L'ÉTAT DE LA BASE DE DONNÉES
-- ============================================================================

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '📊 ÉTAT DES TYPES DE NOTIFICATIONS' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    notification_type_id,
    notification_type_name,
    notification_type_enabled,
    CASE 
        WHEN notification_type_name LIKE '%reaction%' THEN '🔴 REACTION'
        ELSE '⚪ AUTRE'
    END AS type_category
FROM phpbb_notification_types
WHERE notification_type_name LIKE '%reaction%'
ORDER BY notification_type_name;

SELECT '───────────────────────────────────────────────────────────────' AS '';
SELECT '📋 TOUS LES TYPES DE NOTIFICATIONS (pour référence)' AS '';
SELECT '───────────────────────────────────────────────────────────────' AS '';

SELECT 
    notification_type_id,
    notification_type_name,
    notification_type_enabled
FROM phpbb_notification_types
ORDER BY notification_type_name
LIMIT 20;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🗂️  ÉTAT DES TABLES CRÉÉES PAR LA MIGRATION' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    UPDATE_TIME,
    CASE 
        WHEN TABLE_NAME = 'phpbb_post_reactions' THEN '✅ Table principale des réactions'
        ELSE '⚪ Autre table'
    END AS description
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('phpbb_post_reactions')
ORDER BY TABLE_NAME;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '📝 COLONNES AJOUTÉES DANS phpbb_users' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    CASE 
        WHEN COLUMN_NAME LIKE '%reaction%' THEN '🔴 COLONNE REACTION'
        ELSE '⚪ Autre'
    END AS category
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'phpbb_users'
  AND COLUMN_NAME LIKE '%reaction%'
ORDER BY COLUMN_NAME;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '⚙️  CONFIGURATIONS DE L''EXTENSION' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    config_name,
    config_value,
    CASE 
        WHEN config_name LIKE 'bastien59960_reactions%' THEN '🔴 CONFIG REACTION'
        ELSE '⚪ Autre'
    END AS category
FROM phpbb_config
WHERE config_name LIKE 'bastien59960_reactions%'
   OR config_name LIKE 'reactions_ucp%'
ORDER BY config_name;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '📦 MODULES UCP CRÉÉS' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    module_id,
    module_basename,
    module_enabled,
    module_display,
    parent_id
FROM phpbb_modules
WHERE module_basename LIKE '%reactions%'
   OR module_langname LIKE '%reactions%'
ORDER BY module_id;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔄 ÉTAT DES MIGRATIONS' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    migration_name,
    migration_depends_on,
    CASE 
        WHEN migration_name LIKE '%bastien59960%reactions%' THEN '🔴 MIGRATION REACTION'
        ELSE '⚪ Autre'
    END AS category
FROM phpbb_migrations
WHERE migration_name LIKE '%bastien59960%'
   OR migration_name LIKE '%reactions%'
ORDER BY migration_name;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔌 ÉTAT DE L''EXTENSION DANS phpbb_ext' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    ext_name,
    ext_active,
    ext_state
FROM phpbb_ext
WHERE ext_name LIKE '%reactions%'
ORDER BY ext_name;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '📊 STATISTIQUES DES RÉACTIONS' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    COUNT(*) AS total_reactions,
    SUM(CASE WHEN reaction_notified = 0 THEN 1 ELSE 0 END) AS reactions_non_notifiees,
    SUM(CASE WHEN reaction_notified = 1 THEN 1 ELSE 0 END) AS reactions_notifiees,
    MIN(reaction_time) AS premiere_reaction,
    MAX(reaction_time) AS derniere_reaction
FROM phpbb_post_reactions;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔍 VÉRIFICATION DES NOTIFICATIONS ORPHELINES' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    COUNT(*) AS notifications_orphelines
FROM phpbb_notifications n
LEFT JOIN phpbb_notification_types t ON n.notification_type_id = t.notification_type_id
WHERE t.notification_type_id IS NULL;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '✅ DIAGNOSTIC TERMINÉ' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';
DIAGNOSTIC_EOF

echo ""
echo -e "${GREEN}✅ Diagnostic SQL terminé.${NC}"
echo ""

# ==============================================================================
# 8️⃣ RÉACTIVATION EXTENSION
# ==============================================================================
echo "───[ 8️⃣  RÉACTIVATION DE L'EXTENSION (bastien59960/reactions) ]─────────────"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions -vvv 2>&1)
check_status "Extension réactivée." "$output"

# ==============================================================================
# 8️⃣.2️⃣ RESET DES NOTIFICATIONS (MAINTENANT QUE LA TABLE EXISTE)
# ==============================================================================
echo "───[ 8️⃣.2️⃣  RESET DES NOTIFICATIONS (POST-RÉACTIVATION) ]──────────"
sleep 0.2

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<EOF
-- Cette commande est maintenant exécutée après la création de la table par la migration.
-- Elle peut échouer si l'importation n'a pas eu lieu, c'est pourquoi on ajoute '|| true'
UPDATE phpbb_post_reactions SET reaction_notified = 0;
EOF

check_status "Statut 'reaction_notified' réinitialisé (si la table contient des données)."

# ==============================================================================
# 8️⃣.5️⃣ DIAGNOSTIC SQL APRÈS RÉACTIVATION (si erreur)
# ==============================================================================
if echo "$output" | grep -q -E "PHP Fatal error|PHP Parse error|array_merge"; then
    echo ""
    echo "───[ 8️⃣.5️⃣  DIAGNOSTIC APPROFONDI APRÈS ERREUR ]──────────────────────────────"
    sleep 0.2
    echo -e "${YELLOW}⚠️  Une erreur a été détectée. Diagnostic approfondi...${NC}"
    echo ""
    
    # Afficher l'erreur complète
    echo "📋 Sortie complète de l'erreur :"
    echo "$output" | grep -A 20 -B 5 "array_merge\|Fatal error" | head -50
    echo ""
    
    # Sauvegarder la sortie complète dans un fichier pour analyse
    ERROR_LOG="$FORUM_ROOT/ext/bastien59960/reactions/error_output.log"
    echo "$output" > "$ERROR_LOG"
    echo "💾 Sortie complète sauvegardée dans : $ERROR_LOG"
    echo ""
    
    # DIAGNOSTIC SQL : Vérifier l'état de la base de données après l'erreur
    echo "🔍 Diagnostic SQL après erreur..."
    MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'ERROR_SQL_EOF'
-- Vérifier toutes les migrations problématiques
SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔴 MIGRATIONS PROBLÉMATIQUES (non-array)' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    migration_name,
    LEFT(migration_depends_on, 50) as depends_preview,
    LENGTH(migration_depends_on) as length,
    CASE 
        WHEN migration_depends_on LIKE 'a:%' THEN '✅ ARRAY'
        WHEN migration_depends_on LIKE 's:%' THEN '❌ STRING'
        WHEN migration_depends_on IS NULL THEN 'NULL'
        WHEN migration_depends_on = '' THEN 'EMPTY'
        ELSE '❓ OTHER'
    END as type_detected
FROM phpbb_migrations
WHERE (migration_depends_on NOT LIKE 'a:%' 
       AND migration_depends_on IS NOT NULL 
       AND migration_depends_on != '')
   OR migration_name LIKE '%bastien59960%reactions%'
ORDER BY 
    CASE 
        WHEN migration_depends_on LIKE 's:%' THEN 1
        WHEN migration_name LIKE '%bastien59960%reactions%' THEN 2
        ELSE 3
    END,
    migration_name;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '📊 STATISTIQUES GLOBALES' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN migration_depends_on LIKE 'a:%' THEN 1 ELSE 0 END) as arrays,
    SUM(CASE WHEN migration_depends_on LIKE 's:%' THEN 1 ELSE 0 END) as strings,
    SUM(CASE WHEN migration_depends_on IS NULL THEN 1 ELSE 0 END) as nulls,
    SUM(CASE WHEN migration_depends_on = '' THEN 1 ELSE 0 END) as empty
FROM phpbb_migrations;
ERROR_SQL_EOF
    echo ""
    
    # Vérifier les fichiers de migration
    echo "🔍 Vérification des fichiers de migration..."
    MIGRATION_DIR="$FORUM_ROOT/ext/bastien59960/reactions/migrations"
    if [ -d "$MIGRATION_DIR" ]; then
        for file in "$MIGRATION_DIR"/*.php; do
            if [ -f "$file" ]; then
                filename=$(basename "$file")
                echo "   📄 Analyse de $filename..."
                
                # Vérifier les méthodes critiques
                if grep -q "function depends_on" "$file"; then
                    if grep -A 3 "function depends_on" "$file" | grep -q "return array"; then
                        echo "      ✅ depends_on() retourne un array"
                    else
                        echo "      ⚠️  depends_on() pourrait ne pas retourner un array"
                    fi
                fi
                
                if grep -q "function update_schema" "$file"; then
                    if grep -A 5 "function update_schema" "$file" | grep -q "return array"; then
                        echo "      ✅ update_schema() retourne un array"
                    else
                        echo "      ⚠️  update_schema() pourrait ne pas retourner un array"
                    fi
                fi
                
                if grep -q "function update_data" "$file"; then
                    if grep -A 5 "function update_data" "$file" | grep -q "return array"; then
                        echo "      ✅ update_data() retourne un array"
                    else
                        echo "      ⚠️  update_data() pourrait ne pas retourner un array"
                    fi
                fi
            fi
        done
    fi
    echo ""
    
    MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'ERROR_DIAGNOSTIC_EOF'
-- ============================================================================
-- DIAGNOSTIC APPROFONDI APRÈS ERREUR
-- ============================================================================

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '🔴 DIAGNOSTIC D''ERREUR - ÉTAT ACTUEL' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

SELECT '📋 Types de notifications (détail complet)' AS '';
SELECT 
    notification_type_id,
    notification_type_name,
    notification_type_enabled,
    LENGTH(notification_type_name) AS name_length,
    HEX(notification_type_name) AS name_hex
FROM phpbb_notification_types
WHERE notification_type_name LIKE '%reaction%'
ORDER BY notification_type_id;

SELECT '───────────────────────────────────────────────────────────────' AS '';
SELECT '🔍 Vérification des noms de types problématiques' AS '';
SELECT '───────────────────────────────────────────────────────────────' AS '';

SELECT 
    notification_type_id,
    notification_type_name,
    CASE 
        WHEN notification_type_name LIKE 'bastien59960%' THEN '⚠️  NOM INCORRECT (contient namespace)'
        WHEN notification_type_name NOT LIKE 'notification.type.%' THEN '⚠️  FORMAT INATTENDU'
        ELSE '✅ Format correct'
    END AS status
FROM phpbb_notification_types
WHERE notification_type_name LIKE '%reaction%';

SELECT '───────────────────────────────────────────────────────────────' AS '';
SELECT '📊 État des migrations (dernières exécutées)' AS '';
SELECT '───────────────────────────────────────────────────────────────' AS '';

SELECT 
    migration_name,
    migration_depends_on
FROM phpbb_migrations
WHERE migration_name LIKE '%bastien59960%'
ORDER BY migration_name DESC
LIMIT 5;

SELECT '───────────────────────────────────────────────────────────────' AS '';
SELECT '🔌 État exact de l''extension' AS '';
SELECT '───────────────────────────────────────────────────────────────' AS '';

SELECT 
    ext_name,
    ext_active,
    ext_state,
    ext_version,
    CASE 
        WHEN ext_state = '' THEN '⚠️  État vide'
        WHEN ext_state IS NULL THEN '⚠️  État NULL'
        ELSE '✅ État défini'
    END AS state_status
FROM phpbb_ext
WHERE ext_name LIKE '%reactions%';

SELECT '───────────────────────────────────────────────────────────────' AS '';
SELECT '📝 Vérification de la structure de la table post_reactions' AS '';
SELECT '───────────────────────────────────────────────────────────────' AS '';

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'phpbb_post_reactions'
ORDER BY ORDINAL_POSITION;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '✅ DIAGNOSTIC D''ERREUR TERMINÉ' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';
ERROR_DIAGNOSTIC_EOF

    echo ""
    echo -e "${YELLOW}💡 CONSEIL : Vérifiez les noms de types de notifications ci-dessus.${NC}"
    echo -e "${YELLOW}   Ils doivent être au format 'notification.type.xxx' et non 'bastien59960.reactions.xxx'${NC}"
    echo ""
fi

# ==============================================================================
# 9️⃣ PURGE CACHE (APRÈS)
# ==============================================================================
echo "───[ 9️⃣  PURGE DU CACHE (APRÈS) - reconstruction services ]──────"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé et container reconstruit." "$output"

# ==============================================================================
# 🔟 TEST FINAL DU CRON
# ==============================================================================
echo "───[ 🔟 TEST FINAL DU CRON ]──────────────────────────────────"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cron:run -vvv 2>&1)
check_status "Exécution de la tâche cron" "$output"


# ==============================================================================
# 8️⃣ CORRECTION DES PERMISSIONS DU CACHE (CRITIQUE)
# 1️⃣1️⃣ CORRECTION DES PERMISSIONS DU CACHE (CRITIQUE)
# ==============================================================================
echo "───[ 1️⃣1️⃣ RÉTABLISSEMENT DES PERMISSIONS (CRITIQUE) ]────────────"
sleep 0.2

# ⚠️ À ADAPTER ! Remplacez 'www-data' par l'utilisateur/groupe de votre serveur web (ex: 'apache', 'nginx', etc.)
WEB_USER="www-data" 
WEB_GROUP="www-data" 
CACHE_DIR="$FORUM_ROOT/cache"

# 1. Définir le propriétaire du répertoire cache
chown -R "$WEB_USER":"$WEB_GROUP" "$CACHE_DIR" 
check_status "Propriétaire du cache mis à jour à $WEB_USER:$WEB_GROUP."

# 2. Définir les permissions d'écriture pour le propriétaire et le groupe (récursif)
# Ce sont les permissions recommandées par phpBB : 777 pour les répertoires et 666 pour les fichiers.
# ATTENTION: Le 'find' est souvent nécessaire après le chown pour s'assurer que PHP puisse écrire partout.
find "$CACHE_DIR" -type d -exec chmod 0777 {} \;
find "$CACHE_DIR" -type f -exec chmod 0666 {} \;

check_status "Permissions de lecture/écriture pour PHP rétablies (777/666)."

# ==============================================================================
# 🔍 CHECK FINAL EXTENSION STATUS (Version corrigée avec l'astérisque)
# ==============================================================================
echo ""
echo "───[ 🔍  VÉRIFICATION FINALE DU STATUT DE L'EXTENSION ]──────────────────────────────"
sleep 0.2

# On utilise bien "extension:show" et on isole la ligne de notre extension
EXT_STATUS=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:show | grep "bastien59960/reactions" || true)

# On affiche la sortie brute récupérée pour le débogage.
echo -e "${YELLOW}ℹ️  Sortie CLI brute pour l'extension :${NC}"
echo "'$EXT_STATUS'"
echo ""

# NOUVELLE VÉRIFICATION : On regarde si la ligne commence par un astérisque,
# ce qui signifie "Activé".
if echo "$EXT_STATUS" | grep -q "^\s*\*"; then
    echo -e "${GREEN}✅ Extension détectée comme ACTIVE (présence du '*') — tout est OK.${NC}"
else
    echo -e "${WHITE_ON_RED}⚠️ ATTENTION : L'extension ne ressort pas comme active (pas de '*' au début).${NC}"
fi

# ==============================================================================
# 🔍 CHECK FINAL CRON TASK STATUS
# ==============================================================================
echo ""
echo "───[ 🔍  VÉRIFICATION FINALE DE LA TÂCHE CRON ]───────────────────────────────"
sleep 0.2

# Ajout d'une temporisation de 3 secondes pour laisser le temps au système de se stabiliser
echo -e "${YELLOW}ℹ️  Attente de 3 secondes avant la vérification...${NC}"
sleep 3

# Le nom à rechercher est le nom logique retourné par get_name(), et non le nom du service.
# C'est ce nom qui est affiché par `cron:list` si la traduction échoue.
CRON_TASK_NAME="bastien59960.reactions.notification"

CRON_LIST_OUTPUT=$(php "$FORUM_ROOT/bin/phpbbcli.php" cron:list -vvv)

echo -e "${YELLOW}ℹ️  Liste des tâches cron disponibles :${NC}"
echo "$CRON_LIST_OUTPUT"

if echo "$CRON_LIST_OUTPUT" | grep -q "$CRON_TASK_NAME"; then
    echo -e "\n${GREEN}✅ Tâche cron '$CRON_TASK_NAME' détectée dans la liste — tout est OK.${NC}\n"
    echo -e "${GREEN}"
    echo "            .-\"\"\"-."
    echo "           /       \\"
    echo "           \\.---. ./"
    echo "           ( ✓ ✓ )    👾 MISSION ACCOMPLISHED"
    echo "    _..oooO--(_)--Oooo.._"
    echo "    \`--. .--. .--. .--'\`"
    echo "       SYSTEM READY"
    echo -e "${NC}"
else
    echo -e "\n${WHITE_ON_RED}❌ ERREUR : La tâche cron '$CRON_TASK_NAME' est ABSENTE de la liste !${NC}\n"
    echo -e "${WHITE_ON_RED}"
    echo "            .-\"\"\"-."
    echo "           /       \\"
    echo "           \\.---. ./"
    echo "           ( ✗ ✗ )    👾 CRITICAL FAILURE"
    echo "    _..oooO--(_)--Oooo.._"
    echo "    \`--. .--. .--. .--'\`"
    echo "       BUG INVASION DETECTED"
    echo -e "${NC}"
fi