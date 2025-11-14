#!/bin/bash
# ==============================================================================
# Fichier : forum-purge.sh
# Auteur : Bastien (bastien59960)
# Version : 1.1.0
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
    # Puis vérifie le code de sortie. Si non nul, c'est une erreur.
    elif [ $exit_code -ne 0 ]; then
        echo -e "${WHITE_ON_RED}❌ ERREUR (CODE DE SORTIE NON NUL) lors de l'étape : $step_description${NC}"
        echo -e "${YELLOW}   Sortie complète de la commande :${NC}"
        # Affiche la sortie complète pour le débogage, avec indentation.
        echo "$output" | sed 's/^/   | /'
        echo -e "${NC}" # Réinitialise la couleur
        # On ne quitte plus le script ici, on retourne le code d'erreur pour que l'appelant puisse décider.
        return $exit_code
    else
        echo -e "${GREEN}✅ SUCCÈS : $step_description${NC}"
    fi
}

# Fonction de nettoyage manuel forcé
force_manual_purge() {
    echo -e "───[ ⚙️ NETTOYAGE MANUEL FORCÉ DE LA BASE DE DONNÉES ]───────────"
    sleep 0.2
    echo -e "   (Le mot de passe a été demandé au début du script.)"
    
    output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'MANUAL_PURGE_EOF'
    -- Supprimer de force l'extension et ses migrations
    SELECT '--- Purge des tables ext et migrations...' AS '';
    DELETE FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions';
    DELETE FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';

    -- Purge des configurations
    SELECT '--- Purge des configurations...' AS '';
    DELETE FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%';

    -- Purge des modules
    SELECT '--- Purge des modules...' AS '';
    DELETE FROM phpbb_modules WHERE module_basename LIKE '%\\bastien59960\\reactions\\%';
    DELETE FROM phpbb_modules WHERE module_langname LIKE '%REACTIONS%';

    -- Purge des types de notifications
    SELECT '--- Purge des types de notifications...' AS '';
    DELETE FROM phpbb_notification_types WHERE notification_type_name LIKE 'notification.type.reaction%';

    -- Purge du schéma (colonnes et tables)
    SELECT '--- Purge du schéma (colonnes et tables)...' AS '';
    ALTER TABLE phpbb_users DROP COLUMN IF EXISTS user_reactions_notify, DROP COLUMN IF EXISTS user_reactions_cron_email;
    -- Suppression des notifications restantes pour éviter les erreurs
    DELETE n FROM phpbb_notifications n
    LEFT JOIN phpbb_notification_types t ON n.notification_type_id = t.notification_type_id
    WHERE t.notification_type_name LIKE 'notification.type.reaction%';
    DROP TABLE IF EXISTS phpbb_post_reactions;
MANUAL_PURGE_EOF
    )
    check_status "Nettoyage manuel forcé de la base de données." "$output"
}

# ==============================================================================
# FONCTION DE NETTOYAGE (TRAP)
# ==============================================================================
# Cette fonction est appelée à la fin du script, quoi qu'il arrive (succès, erreur, interruption).
cleanup() {
    local exit_code=$? # Capture le code de sortie du script

    # Ne rien faire si le script s'est terminé normalement (code 0)
    if [ $exit_code -eq 0 ]; then
        return
    fi

    echo ""
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo -e "${WHITE_ON_RED}  ⚠️  INTERRUPTION DU SCRIPT (CODE ${exit_code}) - LANCEMENT DE LA RESTAURATION D'URGENCE  ⚠️    ${NC}"
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo ""

    # Vérifier si la table de backup existe et si la table principale est vide ou absente
    BACKUP_ROWS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_post_reactions_backup;" 2>/dev/null || echo 0)

    if [ "$BACKUP_ROWS" -gt 0 ]; then
        echo -e "${YELLOW}ℹ️  ${BACKUP_ROWS} réactions trouvées dans la sauvegarde. Tentative de restauration...${NC}"
        
        restore_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'EMERGENCY_RESTORE_EOF'
            -- Vider la table avant de la remplir pour éviter les doublons
            TRUNCATE TABLE phpbb_post_reactions;
            
            -- Insérer les données depuis la sauvegarde en forçant reaction_notified à 0
            INSERT INTO phpbb_post_reactions (reaction_id, post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified)
            SELECT reaction_id, post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified
            FROM phpbb_post_reactions_backup;
EMERGENCY_RESTORE_EOF
        )
        check_status "Restauration d'urgence des réactions." "$restore_output"
    else
        echo -e "${GREEN}ℹ️  Restauration d'urgence non nécessaire (pas de sauvegarde ou sauvegarde vide).${NC}"
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

# Enregistrer la fonction de nettoyage pour qu'elle soit appelée à la sortie du script
# EXIT : Se déclenche à la fin normale ou via `exit`
# INT : Se déclenche sur Ctrl+C
trap cleanup EXIT INT
# ==============================================================================
# DEMANDE DU MOT DE PASSE MYSQL (UNE SEULE FOIS)
# ==============================================================================
echo -e "🔑 Veuillez entrer le mot de passe MySQL pour l'utilisateur ${YELLOW}$DB_USER${NC} :"
read -s MYSQL_PASSWORD # -s pour masquer l'entrée. Le mot de passe sera utilisé via la variable d'environnement MYSQL_PWD.
echo "" # Nouvelle ligne après l'entrée masquée

# ==============================================================================
# 0️⃣.1️⃣ VÉRIFICATION DE LA CONNEXION MYSQL (SÉCURITÉ)
# ==============================================================================
echo -e "───[ 🔗 VÉRIFICATION DE LA CONNEXION MYSQL ]────────────────────────"
echo -e "${YELLOW}ℹ️  Test de la connexion à la base de données avec le mot de passe fourni...${NC}"
sleep 0.2

# Tente une commande simple. Redirige la sortie d'erreur vers la sortie standard.
mysql_test_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -e "SELECT 1;" 2>&1)

# Vérifie si la sortie contient "Access denied"
if echo "$mysql_test_output" | grep -q "Access denied"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Connexion refusée. Le mot de passe MySQL est incorrect.${NC}"
    echo -e "${WHITE_ON_RED}   Le script va s'arrêter pour protéger vos données.${NC}"
    exit 1
else
    echo -e "${GREEN}✅ SUCCÈS : Connexion à la base de données établie.${NC}"
fi


# ==============================================================================
# 0️⃣.5️⃣ SAUVEGARDE DE LA CONFIGURATION SPAM_TIME
# ==============================================================================
echo -e "───[ 0️⃣.5️⃣ SAUVEGARDE DE LA CONFIGURATION SPAM_TIME ]───────────────────"
echo -e "${YELLOW}ℹ️  Sauvegarde de la valeur actuelle du délai anti-spam...${NC}"
sleep 0.2

# Lire la valeur actuelle et la stocker.
# Si la clé n'existe pas (première exécution), la variable sera vide, ce qui est géré à la restauration.
SPAM_TIME_BACKUP=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT config_value FROM phpbb_config WHERE config_name = 'bastien59960_reactions_spam_time';" 2>/dev/null)

# Si la variable est vide, on utilise la valeur par défaut de la migration pour l'affichage.
echo -e "${GREEN}✅ Valeur du délai anti-spam sauvegardée : ${SPAM_TIME_BACKUP:-15} minutes.${NC}"


# ==============================================================================
# 0️⃣ SAUVEGARDE DES DONNÉES DE RÉACTIONS
# ==============================================================================
echo -e "───[ 0️⃣  SAUVEGARDE DES RÉACTIONS EXISTANTES ]────────────────────────"
echo -e "${YELLOW}ℹ️  Création d'une copie de sécurité de la table 'phpbb_post_reactions' avant toute modification.${NC}"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"

# Vérifier si la table existe en utilisant une commande shell séparée
TABLE_EXISTS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions';")

if [ "$TABLE_EXISTS" -gt 0 ]; then
    # La table existe, on exécute le bloc de sauvegarde
    backup_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'BACKUP_EOF'
        -- 1. Créer la table de backup si elle n'existe pas.
        CREATE TABLE IF NOT EXISTS phpbb_post_reactions_backup LIKE phpbb_post_reactions;
        
        -- 2. Vider la table de backup.
        TRUNCATE TABLE phpbb_post_reactions_backup;
        
        -- 3. Copier les données.
        INSERT INTO phpbb_post_reactions_backup SELECT * FROM phpbb_post_reactions;
        
        -- 4. Renvoyer un statut de succès.
        SELECT "BACKUP_DONE" AS status_code, CONCAT("✅ ", COUNT(*), " réactions sauvegardées dans phpbb_post_reactions_backup.") AS status FROM phpbb_post_reactions_backup;
BACKUP_EOF
    )
    # On affiche la sortie de la commande pour le debug
    echo "$backup_output"
    check_status "Sauvegarde de la table 'phpbb_post_reactions'." "$backup_output"
else
    echo -e "${GREEN}ℹ️  Sauvegarde non nécessaire (table source absente).${NC}"
fi

# ==============================================================================
# 1️⃣ DÉSACTIVATION & PURGE PROPRE (TEST DU REVERT)
# ==============================================================================
echo -e "───[ 1️⃣  DÉSACTIVATION & PURGE PROPRE (TEST DU REVERT) ]──────────────"
echo -e "${YELLOW}ℹ️  Utilisation des commandes natives de phpBB pour tester le cycle de vie de l'extension.${NC}"
sleep 0.2

# On tente de désactiver proprement. On ignore les erreurs avec `|| true` car si l'extension est cassée, cette commande échouera.
output_disable=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:disable bastien59960/reactions -vvv 2>&1 || true)
check_status "Désactivation de l'extension via phpbbcli." "$output_disable"

# On purge l'extension. C'est CETTE commande qui exécute les méthodes `revert_schema()` et `revert_data()` des fichiers de migration.
output_purge=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:purge bastien59960/reactions -vvv 2>&1)
# On vérifie le statut, mais on n'arrête pas le script en cas d'échec.
# La variable `purge_failed` nous servira à décider de la suite.
purge_failed=0
check_status "Purge des données de l'extension via phpbbcli (test du revert)." "$output_purge" || purge_failed=1

# Si la purge a échoué, on le signale explicitement.
# Le script continuera jusqu'au diagnostic post-purge pour montrer ce qui reste.
if [ $purge_failed -ne 0 ]; then
    echo -e "${WHITE_ON_RED}⚠️ La commande 'extension:purge' a échoué. Le diagnostic post-purge va révéler ce qui n'a pas été supprimé.${NC}"
fi

# ==============================================================================
# 3️⃣ NETTOYAGE DES MIGRATIONS PROBLÉMATIQUES (TOUTES EXTENSIONS)
# ==============================================================================
echo -e "───[ 3️⃣  NETTOYAGE DES MIGRATIONS CORROMPUES ]───────────────────"
sleep 0.2
echo -e "${YELLOW}ℹ️  Certaines extensions tierces peuvent laisser des migrations corrompues qui empêchent l'activation d'autres extensions.${NC}"
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo "🔍 Recherche de migrations avec dépendances non-array (cause array_merge error)..."
echo ""
# ==============================================================================
CRON_LOCK_FILE="$FORUM_ROOT/store/cron.lock"
if [ -f "$CRON_LOCK_FILE" ]; then
    rm -f "$CRON_LOCK_FILE"
    check_status "Fichier cron.lock supprimé."
else
    echo -e "${GREEN}ℹ️  Aucun cron.lock trouvé (déjà absent).${NC}"
fi

# Exécuter la détection SÉPARÉMENT pour capturer la sortie
DETECTED_MIGRATIONS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'DETECT_EOF'
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
           AND migration_depends_on != ''));
DETECT_EOF
)

# N'afficher le bloc que si des migrations problématiques sont trouvées
if [ -n "$DETECTED_MIGRATIONS" ]; then
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}🔍 MIGRATIONS PROBLÉMATIQUES DÉTECTÉES${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo "$DETECTED_MIGRATIONS" | column -t -s $'\t'
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}🗑️  SUPPRESSION DES MIGRATIONS PROBLÉMATIQUES...${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
else
    echo -e "${GREEN}✅ Aucune migration problématique (non-array) trouvée sur le forum.${NC}"
fi

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'CLEANUP_EOF'
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
# 4️⃣ SUPPRESSION FICHIER cron.lock
# ==============================================================================
echo -e "───[ 4️⃣  SUPPRESSION DU FICHIER cron.lock ]──────────────────────"
echo -e "${YELLOW}ℹ️  Un fichier de verrouillage de cron ('cron.lock') peut bloquer l'exécution des tâches planifiées.${NC}"
sleep 0.2
if [ -f "$FORUM_ROOT/store/cron.lock" ]; then
    rm -f "$FORUM_ROOT/store/cron.lock"
    check_status "Fichier cron.lock supprimé."
else
    echo -e "${GREEN}ℹ️  Aucun cron.lock trouvé (déjà absent).${NC}"
fi
# ==============================================================================
# 5️⃣ NETTOYAGE FINAL DE LA BASE DE DONNÉES (CRON & NOTIFS ORPHELINES)
# ==============================================================================
echo -e "───[ 5️⃣  NETTOYAGE FINAL DE LA BASE DE DONNÉES ]──────────────────────"
echo -e "${YELLOW}ℹ️  Réinitialisation du verrou de cron en BDD et suppression de TOUTES les notifications.${NC}"
sleep 0.2

MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'FINAL_CLEANUP_EOF' > /dev/null
-- Réinitialiser le verrou du cron en base de données
UPDATE phpbb_config SET config_value = 0 WHERE config_name = 'cron_lock';

-- Vider complètement la table des notifications pour un test propre
TRUNCATE TABLE phpbb_notifications;
FINAL_CLEANUP_EOF

check_status "Nettoyage final de la BDD (cron_lock, toutes notifications)."

# ==============================================================================
# 6️⃣ PURGE DU CACHE (AVANT RÉACTIVATION)
# ==============================================================================
echo -e "───[ 6️⃣  PURGE DU CACHE (AVANT RÉACTIVATION) ]────────────────────"
echo -e "${YELLOW}ℹ️  Dernière purge pour s'assurer que le forum est dans un état parfaitement propre avant de réactiver.${NC}"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé avant réactivation." "$output"

# ==============================================================================
# PAUSE STRATÉGIQUE
# ==============================================================================
echo -e "${YELLOW}ℹ️  Pause de 1 seconde pour laisser le temps au système de se stabiliser...${NC}"
sleep 1
# ==============================================================================
# DÉFINITION DU BLOC DE DIAGNOSTIC SQL (HEREDOC)
# ==============================================================================
# Ce bloc est défini une seule fois et redirigé vers le descripteur de fichier 3.
# Il sera réutilisé par les étapes 10 et 12.
exec 3<<'DIAGNOSTIC_EOF'
-- ============================================================================
-- DIAGNOSTIC COMPLET DE L'ÉTAT DE LA BASE DE DONNÉES
-- ============================================================================
-- Ce bloc de requêtes SQL est utilisé pour photographier l'état de la base de données concernant l'extension.

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

-- CORRECTION : Vérifier si la table existe avant de la requêter
SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions');

-- Utiliser une condition pour exécuter la requête uniquement si la table existe
SET @sql = IF(@table_exists > 0, 
    'SELECT COUNT(*) AS total_reactions, SUM(CASE WHEN reaction_notified = 0 THEN 1 ELSE 0 END) AS non_notifiees, SUM(CASE WHEN reaction_notified = 1 THEN 1 ELSE 0 END) AS notifiees FROM phpbb_post_reactions;',
    'SELECT "La table phpbb_post_reactions n''existe pas encore." AS status;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

# ==============================================================================
# 7️⃣ DIAGNOSTIC SQL POST-PURGE
# ==============================================================================
echo -e "───[ 7️⃣  DIAGNOSTIC POST-PURGE ]────────────────────────────"
echo -e "${YELLOW}ℹ️  Validation de la purge. Recherche de toute trace restante de l'extension...${NC}"
sleep 0.2
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo ""

REMAINING_TRACES=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'POST_PURGE_CHECK_EOF'
-- Ce bloc vérifie toutes les traces que l'extension aurait pu laisser.
-- Il retourne une ligne pour chaque élément trouvé. S'il ne retourne rien, la purge est parfaite.

SELECT 'CONFIG_REMAINING', config_name, config_value FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%'
UNION ALL
SELECT 'MODULE_REMAINING', module_langname, module_basename FROM phpbb_modules WHERE module_basename LIKE '%\\bastien59960\\reactions\\%'
UNION ALL
SELECT 'NOTIFICATION_TYPE_REMAINING', notification_type_name, notification_type_enabled FROM phpbb_notification_types WHERE notification_type_name LIKE 'notification.type.reaction%'
UNION ALL
SELECT 'COLUMN_REMAINING', TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_users' AND COLUMN_NAME LIKE '%reaction%'
UNION ALL
SELECT 'TABLE_REMAINING', TABLE_NAME, 'TABLE' FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions'
UNION ALL
SELECT 'MIGRATION_ENTRY_REMAINING', migration_name, 'MIGRATION' FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%'
UNION ALL
SELECT 'NOTIFICATION_REMAINING', item_id, notification_type_id FROM phpbb_notifications WHERE notification_type_id IN (SELECT notification_type_id FROM phpbb_notification_types WHERE notification_type_name LIKE 'notification.type.reaction%')
UNION ALL
SELECT 'EXT_ENTRY_REMAINING', ext_name, ext_active FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions';

POST_PURGE_CHECK_EOF
)

if [ -z "$REMAINING_TRACES" ]; then
    echo -e "${GREEN}✅ VALIDATION RÉUSSIE : Aucune trace de l'extension n'a été trouvée après la purge.${NC}"
    echo -e "${GREEN}   Les méthodes 'revert_*' des migrations semblent fonctionner correctement.${NC}"
    echo ""
else
    echo -e "${WHITE_ON_RED}⚠️ VALIDATION ÉCHOUÉE : Des traces ont été trouvées après désactivation et désinstallation de l'extension !${NC}"
    echo -e "${YELLOW}   Cela signifie que les méthodes 'revert_*' de vos migrations sont incomplètes.${NC}"
    echo -e "${YELLOW}   Voici la liste exacte de ce qui reste :${NC}"
    echo "┌─────────────────────────────┬────────────────────────────────────────────┬─────────────┐"
    echo "| TYPE DE TRACE RESTANTE      | NOM                                        | VALEUR/INFO |"
    echo "├─────────────────────────────┼────────────────────────────────────────────┼─────────────┤"
    
    # Formatter la sortie pour l'afficher dans un tableau
    echo "$REMAINING_TRACES" | while IFS=$'\t' read -r type name value; do
        # CORRECTION : Tronquer la colonne 'name' si elle est trop longue pour ne pas casser le tableau.
        max_name_len=42
        if [ ${#name} -gt $max_name_len ]; then
            # Tronque et ajoute "..."
            name="${name:0:$((max_name_len-3))}..."
        fi
        printf "| %-27s | %-42s | %-11s |\n" "$type" "$name" "$value"
    done
    
    echo "└─────────────────────────────┴────────────────────────────────────────────┴─────────────┘"
    # Lancer le nettoyage manuel forcé car la purge a échoué
    force_manual_purge
    
    # Si la purge a échoué, on donne un conseil plus précis.
    if [ $purge_failed -ne 0 ]; then
        echo -e "${WHITE_ON_RED}   CONSEIL : L'échec de 'extension:purge' suivi de ces traces restantes pointe vers une erreur dans vos méthodes 'revert_data()' ou 'revert_schema()'. Vérifiez-les !${NC}"
    else
        echo -e "${WHITE_ON_RED}   Le script va s'arrêter. Corrigez vos méthodes 'revert_*' dans les fichiers de migration avant de relancer.${NC}"
    fi
    echo ""
    exit 1 # Arrêter le script car l'état est incohérent
fi

# ==============================================================================
# 8️⃣ RÉACTIVATION EXTENSION
# ==============================================================================
echo -e "───[ 8️⃣  RÉACTIVATION DE L'EXTENSION (bastien59960/reactions) ]─────────"
echo -e "${YELLOW}ℹ️  Lancement de la réactivation. C'est ici que les méthodes 'update_*' des migrations sont exécutées.${NC}"
echo -e "${YELLOW}   Première tentative...${NC}"
sleep 0.2
output_enable=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions -vvv 2>&1)
check_status "Première tentative d'activation de l'extension." "$output_enable"

# ==============================================================================
# 9️⃣ NETTOYAGE BRUTAL ET 2ÈME TENTATIVE (SI ÉCHEC)
# ==============================================================================
# La fonction check_status retourne un code d'erreur si elle échoue.
if [ $? -ne 0 ]; then
    # --------------------------------------------------------------------------
    # NETTOYAGE MANUEL FORCÉ
    # --------------------------------------------------------------------------
    force_manual_purge
    
    # --------------------------------------------------------------------------
    # NOUVELLE PURGE DU CACHE ET SECONDE TENTATIVE
    # --------------------------------------------------------------------------
    echo -e "───[ 9️⃣  PURGE CACHE ET SECONDE TENTATIVE D'ACTIVATION ]──────────"
    sleep 0.2
    
    echo "   Nettoyage agressif du cache à nouveau..."
    rm -vrf "$FORUM_ROOT/cache/production/"* > /dev/null
    php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv > /dev/null 2>&1
    check_status "Cache purgé après nettoyage manuel."
    
    echo -e "${YELLOW}   Seconde tentative d'activation...${NC}"
    output_enable=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions -vvv 2>&1)
    check_status "Seconde tentative d'activation de l'extension." "$output_enable"
fi

# ==============================================================================
# 🔟 DIAGNOSTIC SQL POST-RÉACTIVATION
# ==============================================================================
# On ne lance ce diagnostic que si l'activation a réussi (code de sortie 0)
if [ $? -eq 0 ]; then
    echo -e "───[ 🔟  DIAGNOSTIC POST-RÉACTIVATION (SUCCÈS) ]────────────"
    echo -e "${YELLOW}ℹ️  Vérification de l'état de la base de données après réactivation réussie.${NC}"
    echo -e "${GREEN}ℹ️  Vérification que les migrations ont correctement recréé les structures.${NC}"
    echo ""
    # On ré-exécute le même bloc de diagnostic depuis le descripteur de fichier 3
    MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <&3
fi

# ==============================================================================
# 1️⃣1️⃣ DIAGNOSTIC APPROFONDI POST-ERREUR
# ==============================================================================
if echo "$output_enable" | grep -q -E "PHP Fatal error|PHP Parse error|array_merge"; then
    echo ""
    echo -e "───[ ⚠️  DIAGNOSTIC APPROFONDI APRÈS ERREUR ]───────────────────────"
    echo -e "${YELLOW}ℹ️  Une erreur critique a été détectée. Lancement d'une série de diagnostics pour en trouver la cause.${NC}"
    sleep 0.2
    echo -e "${YELLOW}⚠️  Une erreur a été détectée. Diagnostic approfondi...${NC}"
    echo ""
    
    # Afficher l'erreur complète
    echo "📋 Sortie complète de l'erreur :"
    echo "$output_enable" | grep -A 20 -B 5 "array_merge\|Fatal error" | head -50
    echo ""
    
    # Sauvegarder la sortie complète dans un fichier pour analyse
    ERROR_LOG="$FORUM_ROOT/ext/bastien59960/reactions/error_output.log"
    echo "$output_enable" > "$ERROR_LOG"
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
# 1️⃣3️⃣ VÉRIFICATION FINALE DU STATUT DE L'EXTENSION
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Vérification finale pour confirmer que phpBB considère bien l'extension comme active.${NC}"
echo -e "───[ 1️⃣2️⃣ VÉRIFICATION FINALE DU STATUT DE L'EXTENSION ]───────────"
sleep 0.2

# On utilise bien "extension:show" et on isole la ligne de notre extension
EXT_STATUS=$(php "$FORUM_ROOT/bin/phpbbcli.php" extension:show | grep "bastien59960/reactions" || true)

# NOUVELLE VÉRIFICATION : On regarde si la ligne commence par un astérisque,
# ce qui signifie "Activé".
if echo "$EXT_STATUS" | grep -q "^\s*\*"; then
    echo -e "${GREEN}✅ Extension détectée comme ACTIVE (présence du '*') — tout est OK.${NC}"
else
    echo -e "${WHITE_ON_RED}⚠️ ATTENTION : L'extension ne ressort pas comme active (pas de '*' au début).${NC}"
fi

# ==============================================================================
# 1️⃣4️⃣ PURGE DU CACHE FINALE (CRUCIAL POUR LES CRONS)
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Purge finale pour forcer phpBB à reconstruire son conteneur de services avec l'extension activée.${NC}"
echo -e "───[ 1️⃣3️⃣  PURGE DU CACHE (APRÈS) - reconstruction services ]───────"
sleep 0.2
output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé et container reconstruit." "$output"

# ==============================================================================
# 1️⃣5️⃣ VÉRIFICATION FINALE DE LA TÂCHE CRON
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Vérification finale pour confirmer que la tâche cron de l'extension est bien enregistrée et visible par phpBB.${NC}"
echo -e "───[ 1️⃣5️⃣ VÉRIFICATION FINALE DE LA TÂCHE CRON ]────────────────────"
sleep 0.2

# Ajout d'une temporisation de 1 seconde pour laisser le temps au système de se stabiliser
echo -e "${YELLOW}ℹ️  Attente de 1 seconde avant la vérification...${NC}"
sleep 1

# Le nom à rechercher est le nom logique retourné par get_name(), et non le nom du service.
# C'est ce nom qui est affiché par `cron:list` si la traduction échoue.
CRON_TASK_NAME="bastien59960.reactions.notification"

CRON_LIST_OUTPUT=$(php "$FORUM_ROOT/bin/phpbbcli.php" cron:list -vvv)

echo -e "${YELLOW}ℹ️  Liste des tâches cron disponibles :${NC}"
echo "$CRON_LIST_OUTPUT"

if echo "$CRON_LIST_OUTPUT" | grep -q "$CRON_TASK_NAME"; then
    # ==============================================================================
    # 1️⃣6️⃣ RESTAURATION DE LA CONFIGURATION
    # ==============================================================================
    # On ne restaure que si une valeur a été sauvegardée.
    if [ -n "$SPAM_TIME_BACKUP" ]; then
        echo ""
        echo -e "───[ 1️⃣6️⃣ RESTAURATION DE LA CONFIGURATION ]──────────"
        echo -e "${YELLOW}ℹ️  Restauration de la valeur du délai anti-spam à ${GREEN}${SPAM_TIME_BACKUP} minutes${NC}..."
        sleep 0.2

        # Utiliser INSERT ... ON DUPLICATE KEY UPDATE pour être sûr que la clé existe.
        restore_spam_time_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<RESTORE_SPAM_EOF
INSERT INTO phpbb_config (config_name, config_value, is_dynamic) 
VALUES ('bastien59960_reactions_spam_time', '${SPAM_TIME_BACKUP}', 0)
ON DUPLICATE KEY UPDATE config_value = '${SPAM_TIME_BACKUP}';
RESTORE_SPAM_EOF
        )
        check_status "Restauration de la configuration du délai anti-spam." "$restore_spam_time_output"
    fi

    # ==============================================================================
    # 1️⃣7️⃣ RESTAURATION DES DONNÉES
    # ==============================================================================
    # Cette étape est cruciale. Elle restaure les données sauvegardées au début du script
    # dans la table fraîchement recréée par la réactivation de l'extension.
    if echo "$EXT_STATUS" | grep -q "^\s*\*"; then
        echo -e "───[ 1️⃣7️⃣  RESTAURATION DES RÉACTIONS ]─────────"
        echo -e "${YELLOW}ℹ️  L'extension est active. Réinjection des données depuis la sauvegarde...${NC}"
        sleep 0.2
        echo -e "   (Le mot de passe a été demandé au début du script.)"
        
        # Vérifier si la table de backup existe et contient des données.
        BACKUP_ROWS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_post_reactions_backup;" 2>/dev/null || echo 0)
        
        if [ "$BACKUP_ROWS" -gt 0 ]; then
            # Si la sauvegarde n'est pas vide, exécuter la restauration.
            restore_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'RESTORE_EOF'
                -- Vider la table avant de la remplir pour éviter les doublons.
                TRUNCATE TABLE phpbb_post_reactions;
                
                -- CORRECTION CRITIQUE : Insérer TOUTES les colonnes de la sauvegarde.
                -- Le flag 'reaction_notified' est conservé tel quel depuis la sauvegarde.
                -- Le cron se chargera de traiter les '0'.
                INSERT INTO phpbb_post_reactions (reaction_id, post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified)
                SELECT 
                    reaction_id, post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified
                FROM phpbb_post_reactions_backup
RESTORE_EOF
            )
            check_status "Restauration des données depuis 'phpbb_post_reactions_backup'." "$restore_output"
        else
            # 3. Sinon, afficher un message et continuer.
            echo -e "${GREEN}ℹ️  Restauration ignorée : la table de sauvegarde est vide ou absente.${NC}"
        fi
    fi

    # ==============================================================================
    # 1️⃣8️⃣ RÉINITIALISATION DES FLAGS DE NOTIFICATION (POUR DEBUG)
    # ==============================================================================
    echo ""
    echo -e "───[ 1️⃣8️⃣ RÉINITIALISATION DES FLAGS DE NOTIFICATION (DEBUG) ]────────"
    echo -e "${YELLOW}ℹ️  Remise à zéro de tous les flags 'reaction_notified' pour forcer l'envoi d'un email de test.${NC}"
    echo -e "${YELLOW}   Cela permet de tester les corrections UTF-8 sur les emojis et les caractères accentués.${NC}"
    sleep 0.2
    echo -e "   (Le mot de passe a été demandé au début du script.)"
    
    # Remettre tous les flags reaction_notified à 0 pour forcer le traitement par le cron
    reset_flags_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'RESET_FLAGS_EOF'
        -- Vérifier si la table existe
        SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions');
        
        -- Si la table existe, remettre TOUS les flags à 0 (sans condition WHERE pour être sûr)
        SET @sql = IF(@table_exists > 0,
            'UPDATE phpbb_post_reactions SET reaction_notified = 0;',
            'SELECT "Table phpbb_post_reactions n''existe pas" AS message;'
        );
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Afficher le nombre total de réactions qui sont maintenant à 0
        SELECT 
            COUNT(*) AS total_reactions_ready
        FROM phpbb_post_reactions
        WHERE reaction_notified = 0;
RESET_FLAGS_EOF
    )
    
    if [ $? -eq 0 ]; then
        RESET_COUNT=$(echo "$reset_flags_output" | tail -n 1 | tr -d '[:space:]')
        if [ -n "$RESET_COUNT" ] && [ "$RESET_COUNT" != "0" ]; then
            echo -e "${GREEN}✅ SUCCÈS : $RESET_COUNT réaction(s) avec flag 'reaction_notified = 0' (prêtes pour le cron).${NC}"
        else
            echo -e "${YELLOW}ℹ️  Aucune réaction à réinitialiser (toutes sont déjà à 0 ou la table est vide).${NC}"
        fi
    else
        echo -e "${WHITE_ON_RED}⚠️  Erreur lors de la réinitialisation des flags (peut être normal si la table n'existe pas encore).${NC}"
    fi

    # ==============================================================================
    # 1️⃣9️⃣ TEST DE L'EXÉCUTION DU CRON
    # ==============================================================================
    echo -e "───[ 1️⃣9️⃣ TEST FINAL DU CRON ]───────────────────────────────────"
    echo -e "${YELLOW}ℹ️  Tentative d'exécution de toutes les tâches cron pour vérifier que le système est fonctionnel.${NC}"
    echo -e "${YELLOW}   Les réactions restaurées devraient maintenant être traitées.${NC}"
    sleep 0.2

    output=$(php "$FORUM_ROOT/bin/phpbbcli.php" cron:run -vvv 2>&1)
    check_status "Exécution de toutes les tâches cron prêtes." "$output"

    # ==============================================================================
    # 2️⃣0️⃣ VÉRIFICATION POST-CRON (LA PREUVE)
    # ==============================================================================
    echo -e "───[ 2️⃣0️⃣ VÉRIFICATION POST-CRON (LA PREUVE) ]───────────────────"
    echo -e "${YELLOW}ℹ️  Vérification de l'état des réactions dans la base de données après l'exécution du cron.${NC}"
    sleep 0.2

    # Récupérer la valeur de la fenêtre de spam (en minutes) depuis la config phpBB
    # CORRECTION : Utiliser la valeur sauvegardée au début du script, car la clé a été purgée.
    SPAM_MINUTES=${SPAM_TIME_BACKUP:-15} # Utilise la sauvegarde, avec 15 comme fallback ultime.

    if [ -z "$SPAM_MINUTES" ]; then
        echo -e "${WHITE_ON_RED}❌ ERREUR CRITIQUE : La valeur du délai anti-spam est vide et n'a pas pu être récupérée.${NC}"
        echo -e "${YELLOW}   Le script va s'arrêter pour éviter un calcul erroné.${NC}"
        exit 1
    fi

    # Exécuter une requête SQL pour obtenir le statut des réactions
    POST_CRON_STATUS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<POST_CRON_EOF
        -- Vérifier si la table existe pour éviter une erreur
        SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions');

        -- Définir la fenêtre de spam en secondes
        SET @spam_window_seconds = ${SPAM_MINUTES} * 60;
        SET @threshold_timestamp = UNIX_TIMESTAMP() - @spam_window_seconds;

        -- Requête conditionnelle pour obtenir le statut
        SET @sql = IF(@table_exists > 0,
            'SELECT 
                -- CORRECTION : Utiliser IFNULL(..., 0) pour éviter les résultats NULL sur une table vide.
                IFNULL(SUM(CASE WHEN reaction_notified = 0 THEN 1 ELSE 0 END), 0) AS en_attente,
                IFNULL(SUM(CASE WHEN reaction_notified = 1 THEN 1 ELSE 0 END), 0) AS traitees,
                IFNULL(SUM(CASE WHEN reaction_notified = 0 AND reaction_time > @threshold_timestamp THEN 1 ELSE 0 END), 0) AS dans_fenetre_spam,
                IFNULL(SUM(CASE WHEN reaction_notified = 0 AND reaction_time <= @threshold_timestamp THEN 1 ELSE 0 END), 0) AS eligibles_cron,
                IFNULL(COUNT(*), 0) AS total_general
             FROM phpbb_post_reactions;',
            'SELECT "N/A", "N/A", "N/A", "N/A", "N/A";'
        );

        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
POST_CRON_EOF
    )

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

    # Afficher la valeur de la fenêtre de spam utilisée pour le calcul
    echo -e "${YELLOW}ℹ️  Fenêtre de spam configurée en base de données : ${GREEN}${SPAM_MINUTES} minutes${NC}\n"

    # Afficher le tableau de preuves
    echo -e "${GREEN}📊 PREUVE DU TRAITEMENT CRON :${NC}"
    echo "┌───────────────────────────────────┬──────────┐"
    echo "│ STATUT DES RÉACTIONS              │ NOMBRE   │"
    echo "├───────────────────────────────────┼──────────┤"
    
    # Lire la sortie de la requête SQL
    read -r en_attente traitees dans_fenetre_spam eligibles_cron total_general <<< "$POST_CRON_STATUS"
    printf "| %-33s │ %-8s │\n" "Total des réactions" "${total_general:-0}"
    echo "├───────────────────────────────────┼──────────┤"
    printf "| %-33s │ %-8s │\n" "En attente (non traitées)" "${en_attente:-0}"
    printf "| %-33s │ %-8s │\n" "  └─ Éligibles au cron (anciennes)" "${eligibles_cron:-0}"
    printf "| %-33s │ %-8s │\n" "  └─ Dans la fenêtre de spam" "${dans_fenetre_spam:-0}"
    printf "| %-33s │ %-8s │\n" "Traitées (notifiées)" "${traitees:-0}"
    echo "└───────────────────────────────────┴──────────┘"

    # ==============================================================================
    # 2️⃣1️⃣ VALIDATION FINALE DU TRAITEMENT CRON
    # ==============================================================================
    echo ""
    echo -e "───[ 2️⃣1️⃣ VALIDATION FINALE DU TRAITEMENT CRON ]─────────────────"
    echo -e "${YELLOW}ℹ️  Vérification qu'il ne reste aucune réaction éligible non traitée.${NC}"
    sleep 0.2

    # Si la variable 'eligibles_cron' (calculée à l'étape 19) est supérieure à 0,
    # cela signifie que le cron a échoué à traiter des réactions qui étaient prêtes.
    # On utilise -ne 0 pour être sûr, même si la valeur ne devrait jamais être négative.
    if [ "${eligibles_cron:-0}" -ne 0 ]; then
        echo ""
        echo -e "${WHITE_ON_RED}                                                                                ${NC}"
        echo -e "${WHITE_ON_RED}  🔥🔥🔥  CRITICAL FAILURE: LE CRON N'A PAS TRAITÉ TOUTES LES RÉACTIONS  🔥🔥🔥  ${NC}"
        echo -e "${WHITE_ON_RED}                                                                                ${NC}"
        echo ""
        echo -e "${YELLOW}   Il reste ${eligibles_cron} réaction(s) éligible(s) avec le flag 'reaction_notified = 0'.${NC}"
        echo -e "${YELLOW}   Cela indique un problème majeur dans la logique du cron ou dans l'envoi des e-mails.${NC}"
        echo ""
        echo -e "${YELLOW}   Causes possibles :${NC}"
        echo -e "${YELLOW}   1. Problème de configuration des e-mails sur le serveur (SMTP, sendmail).${NC}"
        echo -e "${YELLOW}   2. Erreur PHP dans la tâche cron (vérifiez les logs d'erreur Apache/PHP).${NC}"
        echo -e "${YELLOW}   3. Fichiers de template ou de langue d'e-mail manquants ou vides.${NC}"
        echo ""
        echo -e "${WHITE_ON_RED}   Le script va s'arrêter. Le diagnostic est un échec critique.${NC}"
        echo ""
        echo -e "${WHITE_ON_RED}"
        echo "            .-\"\"\"-."
        echo "           /       \\"
        echo "           \\.---. ./"
        echo "           ( ✗ ✗ )    👾 CRITICAL FAILURE"
        echo "    _..oooO--(_)--Oooo.._"
        echo "    \`--. .--. .--. .--'\`"
        echo "       BUG INVASION DETECTED"
        echo -e "${NC}"
        exit 1
    else
        echo -e "${GREEN}✅ VALIDATION RÉUSSIE : Toutes les réactions éligibles ont été traitées par le cron.${NC}"
        echo ""
        echo -e "${GREEN}"
        echo "            .-\"\"\"-."
        echo "           /       \\"
        echo "           \\.---. ./"
        echo "           ( ✓ ✓ )    👾 MISSION ACCOMPLISHED"
        echo "    _..oooO--(_)--Oooo.._"
        echo "    \`--. .--. .--. .--'\`"
        echo "       SYSTEM READY"
        echo -e "${NC}"
    fi
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

# ==============================================================================
# 2️⃣2️⃣ CORRECTION FINALE ET DÉFINITIVE DES PERMISSIONS
# ==============================================================================
echo ""
echo -e "───[ 2️⃣2️⃣ CORRECTION FINALE DES PERMISSIONS ]────────────────────"
echo -e "${YELLOW}ℹ️  Application des permissions correctes en toute fin de script pour garantir l'accès au forum.${NC}"

WEB_USER="www-data"
WEB_GROUP="www-data"

sudo chown -R "$WEB_USER":"$WEB_GROUP" "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload"
check_status "Propriétaire des répertoires critiques mis à jour."

sudo find "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload" -type d -exec chmod 0777 {} \;
sudo find "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload" -type f -exec chmod 0666 {} \;
check_status "Permissions de lecture/écriture (777/666) appliquées."