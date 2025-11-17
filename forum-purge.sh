#!/bin/bash
# ==============================================================================
# Fichier : forum-purge.sh
# Chemin : bastien59960/reactions/forum-purge.sh
# Auteur : Bastien (bastien59960)
# Version : 1.1.0
# @version 1.0.3
# GitHub : https://github.com/bastien59960/reactions
#
# Rôle :
# Script de maintenance complet pour le forum phpBB. Il effectue un cycle complet
# de nettoyage du cache, de réinitialisation de l'extension "Reactions" et de
# vérification de l'état final. Conçu pour accélérer le débogage.
#
# @copyright (c) 2025 Bastien59960
# @license GNU General Public License, version 2 (GPL-2.0)
# ==============================================================================

# ==============================================================================
# CONFIGURATION
# ==============================================================================
FORUM_ROOT="/home/bastien/www/forum"
DB_USER="phpmyadmin"
DB_NAME="bastien-phpbb"
PHP_ERROR_LOG="/var/log/php/debug.err" # Fichier pour les erreurs PHP et les logs du script

# --- Couleurs ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
WHITE_ON_RED='\033[1;41;37m'
NC='\033[0m'

# --- Commande PHP avec logging ---
# Utilise des directives pour forcer la journalisation des erreurs dans un fichier spécifique.
PHP_CLI="php -d error_reporting=E_ALL -d display_errors=0 -d log_errors=1 -d error_log=\"$PHP_ERROR_LOG\""

# ==============================================================================
# FUNCTION
# ==============================================================================

# Fonction de vérification améliorée
check_status() {
    local exit_code=$?
    local step_description=$1 # e.g., "Nettoyage du cache de production."
    local output=$2           # Full output of the command

    # Vérifie si la sortie contient une erreur fatale PHP
    if echo "$output" | grep -q "array_merge()"; then
        echo -e "${WHITE_ON_RED}❌ ERREUR CRITIQUE 'array_merge' DÉTECTÉE lors de l'étape : $step_description${NC}"
        echo -e "${WHITE_ON_RED}   CAUSE PROBABLE : Une migration (la vôtre ou celle d'une autre extension) a une dépendance invalide.${NC}"
        echo -e "${WHITE_ON_RED}   Détails de l'erreur :${NC}"
        echo "$output" | grep -C 3 "array_merge()" | sed 's/^/   /'
        return 3
    elif echo "$output" | grep -q -E "PHP Fatal error|PHP Parse error"; then
        echo -e "${WHITE_ON_RED}❌ ERREUR FATALE DÉTECTÉE lors de l'étape : $step_description${NC}"
        echo -e "${WHITE_ON_RED}   Détails de l'erreur :${NC}"
        echo "$output" | grep -E "PHP Fatal error|PHP Parse error" | sed 's/^/   /' # Indent error line
        echo -e "${NC}"
        # Retourne un code d'erreur spécifique pour les erreurs fatales PHP
        return 2
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

# ==============================================================================
# FONCTIONS DE DIAGNOSTIC CRON (intégrées depuis check-crons.sh)
# ==============================================================================

# Fonction pour afficher un en-tête de section de diagnostic
print_diag_header() {
    echo -e "\n═══════════════════════════════════════════════════════════════"
    echo -e " $1"
    echo -e "═══════════════════════════════════════════════════════════════"
}

# Fonction pour vérifier une commande et afficher un statut
check_diag() {
    local description=$1
    shift
    local command_output=$("$@" 2>&1) # Capture stdout and stderr
    local exit_code=$?

    if [ $exit_code -eq 0 ]; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} $description"
        return 0
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} $description"
        if [ -n "$command_output" ]; then
            echo -e "     ${YELLOW}Sortie:${NC}\n$command_output" | sed 's/^/     | /'
        fi
        return 1
    fi
}
# Fonction de nettoyage manuel forcé
force_manual_purge() {
    echo -e "───[ ⚙️ NETTOYAGE MANUEL FORCÉ DE LA BASE DE DONNÉES ]───────────"
    sleep 0.2
    echo -e "   (Le mot de passe a été demandé au début du script.)"
    
    output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'MANUAL_PURGE_EOF'
    -- Étape 1 : Récupérer les IDs des types de notification avant de les supprimer
    SET @type_ids_to_delete := (
        SELECT GROUP_CONCAT(notification_type_id) 
        FROM phpbb_notification_types 
        -- CORRECTION : Le nettoyage manuel doit être exhaustif et supprimer TOUTES les formes de traces.
        WHERE notification_type_name LIKE '%reaction%'
    );

    -- Étape 2 : Supprimer les notifications qui dépendent de ces types
    SELECT '--- Purge des notifications...' AS '';
    DELETE FROM phpbb_notifications WHERE FIND_IN_SET(notification_type_id, @type_ids_to_delete);

    -- Étape 3 : Supprimer les types de notifications
    SELECT '--- Purge des types de notifications...' AS '';
    DELETE FROM phpbb_notification_types WHERE notification_type_name LIKE '%reaction%';

    -- Étape 4 : Purge des autres éléments (config, modules, etc.)
    SELECT '--- Purge des configurations...' AS '';
    DELETE FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%';

    SELECT '--- Purge des modules...' AS '';
    DELETE FROM phpbb_modules WHERE module_basename LIKE '%\\bastien59960\\reactions\\%' OR module_langname LIKE '%REACTIONS%';

    SELECT '--- Purge des entrées d''extension et de migration...' AS '';
    DELETE FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions';
    DELETE FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%';

    -- Étape 5 : Purge du schéma (colonnes et tables)
    SELECT '--- Purge du schéma (colonnes et tables)...' AS '';
    ALTER TABLE phpbb_users DROP COLUMN IF EXISTS user_reactions_notify, DROP COLUMN IF EXISTS user_reactions_cron_email;
    DROP TABLE IF EXISTS phpbb_post_reactions;
    DROP TABLE IF EXISTS phpbb_post_reactions_backup;
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

    # Si le script s'est terminé normalement (code 0), on logue la fin et on sort.
    if [ $exit_code -eq 0 ]; then
        log_to_file "SCRIPT END: Le script de purge s'est terminé avec succès."
        return
    fi

    # Si le script est interrompu (code non-nul), on logue l'échec.
    log_to_file "SCRIPT ABORTED: Le script a été interrompu avec le code de sortie $exit_code."
    echo ""
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo -e "${WHITE_ON_RED}  ⚠️  INTERRUPTION DU SCRIPT (CODE ${exit_code}) - LANCEMENT DE LA RESTAURATION D'URGENCE  ⚠️    ${NC}"
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo ""

    # Vérifier si la table de backup existe et si la table principale est vide ou absente
    BACKUP_ROWS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_post_reactions_backup;" 2>/dev/null || echo 0)

    if [ "$BACKUP_ROWS" -gt 0 ]; then
        echo -e "${YELLOW}ℹ️  ${BACKUP_ROWS} réactions trouvées dans la sauvegarde. Tentative de restauration...${NC}"
        
        restore_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'EMERGENCY_RESTORE_EOF'
            -- S'assurer que la table principale existe avant de la restaurer
            CREATE TABLE IF NOT EXISTS phpbb_post_reactions LIKE phpbb_post_reactions_backup;

            -- Vider la table avant de la remplir pour éviter les doublons (plus sûr que TRUNCATE)
            DELETE FROM phpbb_post_reactions;
            
            -- Insérer les données depuis la sauvegarde
            INSERT INTO phpbb_post_reactions SELECT * FROM phpbb_post_reactions_backup;
            SELECT CONCAT(ROW_COUNT(), ' réaction(s) restaurée(s) d''urgence.') as status;
EMERGENCY_RESTORE_EOF
        )
        check_status "Restauration d'urgence des réactions." "$restore_output"
    else
        echo -e "${GREEN}ℹ️  Restauration d'urgence non nécessaire (pas de sauvegarde ou sauvegarde vide).${NC}"
    fi
}

log_to_file() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] - $1" >> "$PHP_ERROR_LOG"
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
echo -e "🔑 Veuillez entrer le mot de passe MySQL pour l'utilisateur ${YELLOW}$DB_USER${NC} :"
read -s MYSQL_PASSWORD # -s pour masquer l'entrée. Le mot de passe sera utilisé via la variable d'environnement MYSQL_PWD.
echo "" # Nouvelle ligne après l'entrée masquée

# ==============================================================================
# 1. VÉRIFICATION DE LA CONNEXION MYSQL (SÉCURITÉ)
# ==============================================================================
echo -e "───[ 1. VÉRIFICATION DE LA CONNEXION MYSQL ]────────────────────────"
echo -e "${YELLOW}ℹ️  Test de la connexion à la base de données avec le mot de passe fourni...${NC}"
sleep 0.1

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
# 2. INITIALISATION DU FICHIER DE LOG
# ==============================================================================
echo -e "───[ 2. INITIALISATION DU FICHIER DE LOG ]───────────────────────"
echo -e "${YELLOW}ℹ️  Tentative d'initialisation du fichier de log à l'emplacement : $PHP_ERROR_LOG${NC}"
echo -e "${YELLOW}   Cela peut nécessiter les droits sudo.${NC}"

# Créer le répertoire parent si nécessaire
if ! sudo mkdir -p "$(dirname "$PHP_ERROR_LOG")"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Impossible de créer le répertoire de log $(dirname "$PHP_ERROR_LOG").${NC}"
fi

# Créer le fichier, donner les permissions et vider le contenu
if ! sudo touch "$PHP_ERROR_LOG" || ! sudo chown "$USER":"$(id -g -n "$USER")" "$PHP_ERROR_LOG"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Impossible de créer ou de définir les permissions pour le fichier de log $PHP_ERROR_LOG.${NC}"
else
    > "$PHP_ERROR_LOG"
    log_to_file "SCRIPT START: Le script de purge a démarré."
    check_status "Initialisation et permissions du fichier de log."
fi

# ==============================================================================
# 3. DIAGNOSTIC INITIAL (AVANT TOUTE MODIFICATION)
# ==============================================================================
echo -e "───[ 3. DIAGNOSTIC INITIAL ]────────────────────────"
echo -e "${YELLOW}ℹ️  État des notifications et des types de notifications avant toute opération...${NC}"
sleep 0.1
 
initial_diag_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'INITIAL_DIAG_EOF'
-- S'assurer que le type est bien enregistré et activé
-- CORRECTION : Le diagnostic initial doit chercher les noms longs attendus, pas un pattern large.
SELECT '--- Types de notifications de réaction ---' AS 'Diagnostic';
SELECT * FROM phpbb_notification_types 
WHERE notification_type_name IN (
    'bastien59960.reactions.notification.type.reaction', 
    'bastien59960.reactions.notification.type.reaction_email_digest'
);

-- Vérifier que la notification a bien été créée
SELECT '--- Dernières 50 notifications de réaction ---' AS 'Diagnostic';
SELECT * FROM phpbb_notifications 
WHERE notification_type_id = (
    SELECT notification_type_id 
    FROM phpbb_notification_types
    WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'
    LIMIT 1
)
ORDER BY notification_time DESC 
LIMIT 50;
INITIAL_DIAG_EOF
)
check_status "Diagnostic initial des notifications." "$initial_diag_output"
# ==============================================================================
# 4. SAUVEGARDE DE LA CONFIGURATION SPAM_TIME
# ==============================================================================
echo -e "───[ 4. SAUVEGARDE DE LA CONFIGURATION SPAM_TIME ]───────────────────"
echo -e "${YELLOW}ℹ️  Sauvegarde de la valeur actuelle du délai anti-spam...${NC}"
sleep 0.1
 
# Lire la valeur actuelle et la stocker.
# Si la clé n'existe pas (première exécution), la variable sera vide, ce qui est géré à la restauration.
SPAM_TIME_BACKUP=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT config_value FROM phpbb_config WHERE config_name = 'bastien59960_reactions_spam_time';" 2>/dev/null)

# Si la variable est vide, on utilise la valeur par défaut de la migration pour l'affichage.
echo -e "${GREEN}✅ Valeur du délai anti-spam sauvegardée : ${SPAM_TIME_BACKUP:-15} minutes.${NC}"


# ==============================================================================
# 5. RESTAURATION PRÉCOCE (SI NÉCESSAIRE)
# ==============================================================================
echo -e "───[ 5. RESTAURATION PRÉCOCE (SI NÉCESSAIRE) ]─────────────────"
echo -e "${YELLOW}ℹ️  Vérification si la table principale est vide pour une restauration précoce...${NC}"
sleep 0.1
 
# Vérifier si la table principale existe et si elle est vide
MAIN_TABLE_EXISTS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions';")

if [ "$MAIN_TABLE_EXISTS" -gt 0 ]; then
    MAIN_TABLE_ROWS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_post_reactions;")
    if [ "$MAIN_TABLE_ROWS" -eq 0 ]; then
        echo -e "${YELLOW}   La table principale 'phpbb_post_reactions' est vide. Tentative de restauration depuis la sauvegarde...${NC}"

        restore_early_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'EARLY_RESTORE_EOF'
            -- Vérifier si la table de backup existe
            SET @backup_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions_backup');

            -- Si la sauvegarde existe, insérer les données
            -- INSERT IGNORE est plus sûr que ON DUPLICATE KEY UPDATE ici.
            INSERT IGNORE INTO phpbb_post_reactions 
            SELECT * FROM phpbb_post_reactions_backup
            WHERE @backup_exists > 0;

            SELECT CONCAT(ROW_COUNT(), ' réaction(s) restaurée(s) (restauration précoce).') as status;
EARLY_RESTORE_EOF
        )
        check_status "Restauration précoce des réactions." "$restore_early_output"
    else
        echo -e "${GREEN}ℹ️  La table principale contient déjà ${MAIN_TABLE_ROWS} réaction(s). Aucune restauration précoce nécessaire.${NC}"
    fi
fi

# ==============================================================================
# 6. SAUVEGARDE DES DONNÉES (RÉACTIONS & NOTIFICATIONS)
# ==============================================================================
echo -e "───[ 6. SAUVEGARDE DES DONNÉES (RÉACTIONS & NOTIFICATIONS) ]─────────"
echo -e "${YELLOW}ℹ️  Création d'une copie de sécurité des réactions et des notifications avant toute modification.${NC}"
sleep 0.1
echo -e "   (Le mot de passe a été fourni au début du script.)"

backup_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'BACKUP_EOF'
-- Sauvegarde des réactions (méthode sécurisée)
DROP TABLE IF EXISTS phpbb_post_reactions_backup;
SET @source_table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions');
SET @sql_backup_reactions = IF(@source_table_exists > 0,
    'CREATE TABLE phpbb_post_reactions_backup LIKE phpbb_post_reactions; INSERT INTO phpbb_post_reactions_backup SELECT * FROM phpbb_post_reactions;',
    'SELECT "Table phpbb_post_reactions non trouvée, sauvegarde ignorée." as status;'
);
PREPARE stmt1 FROM @sql_backup_reactions;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
SET @reactions_count = IF(@source_table_exists > 0, ROW_COUNT(), 0);

-- Sauvegarde des notifications
DROP TABLE IF EXISTS phpbb_notifications_backup;
CREATE TABLE phpbb_notifications_backup LIKE phpbb_notifications;
INSERT INTO phpbb_notifications_backup SELECT * FROM phpbb_notifications;
SET @notif_count = ROW_COUNT();

-- Affichage du résumé
-- Utiliser une sous-requête pour afficher le bon nombre même si la table n'existait pas
-- CORRECTION : Rendre la requête de résumé plus robuste pour éviter l'erreur "table doesn't exist".
SET @backup_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions_backup');
SET @sql_summary = IF(@backup_exists > 0, 
    'SELECT \'Réactions\' AS `Type de sauvegarde`, COUNT(*) AS `Total` FROM phpbb_post_reactions_backup;', 
    'SELECT \'Réactions\' AS `Type de sauvegarde`, \'0 (source absente)\' AS `Total`;'
);
PREPARE stmt_summary FROM @sql_summary;
EXECUTE stmt_summary;
DEALLOCATE PREPARE stmt_summary;
SELECT 'Notifications' AS 'Type de sauvegarde', CONCAT('Total: ', @notif_count) AS 'Statut';
BACKUP_EOF
)

echo "$backup_output"
check_status "Sauvegarde des données (réactions et notifications)." "$backup_output"

# ==============================================================================
# 7. DÉSACTIVATION & PURGE PROPRE (TEST DU REVERT)
# ==============================================================================
echo -e "───[ 7. DÉSACTIVATION & PURGE PROPRE (TEST DU REVERT) ]──────────────"
echo -e "${YELLOW}ℹ️  Utilisation des commandes natives de phpBB pour tester le cycle de vie de l'extension.${NC}"
sleep 0.1

# On tente de désactiver proprement. On ignore les erreurs avec `|| true` car si l'extension est cassée, cette commande échouera.
output_disable=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" extension:disable bastien59960/reactions -vvv 2>&1 || true)
check_status "Désactivation de l'extension via phpbbcli." "$output_disable"

# On tente de purger l'extension. C'est CETTE commande qui exécute les méthodes `revert_*` des migrations.
output_purge=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" extension:purge bastien59960/reactions -vvv 2>&1)

# Vérifier si la purge a échoué (à cause d'une erreur fatale dans les migrations par exemple)
purge_exit_code=0
check_status "Purge des données de l'extension via phpbbcli (test du revert)." "$output_purge"
purge_exit_code=$? # Capture le code de retour de check_status

# On réagit si le code de sortie est non-nul (erreur normale ou fatale)
if [ $purge_exit_code -ne 0 ]; then
    echo ""
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo -e "${WHITE_ON_RED}  ⚠️  ÉCHEC DE LA PURGE AUTOMATIQUE - ANOMALIE DANS LES MIGRATIONS DÉTECTÉE          ${NC}"
    echo -e "${WHITE_ON_RED}                                                                                   ${NC}"
    echo ""
    echo -e "${YELLOW}   EXPLICATION : La commande 'phpbbcli extension:purge' a échoué. C'est souvent le signe d'une erreur fatale${NC}"
    echo -e "${YELLOW}   dans une des méthodes de réversion ('revert_data()' ou 'revert_schema()') de vos fichiers de migration.${NC}"
    echo -e "${YELLOW}   Sortie complète de la commande de purge :${NC}"
    echo "$output_purge" | sed 's/^/   | /'
    echo ""
    echo -e "${YELLOW}   POUR ÉVITER QUE CELA SE REPRODUISE :${NC}"
    echo -e "${YELLOW}   1. Inspectez les fichiers dans le dossier 'migrations/'.${NC}"
    echo -e "${YELLOW}   2. Assurez-vous que CHAQUE méthode 'revert_data()' et 'revert_schema()' se termine par 'return array(...);'${NC}"
    echo -e "${YELLOW}      Même si la méthode ne fait rien, elle doit retourner un tableau vide : 'return array();'${NC}"
    echo ""
    echo -e "${YELLOW}   Voulez-vous continuer avec un nettoyage manuel forcé pour corriger l'état de la base de données ? (y/n)${NC}"
    read -r user_choice

    # Utiliser une regex pour accepter 'y', 'Y', 'yes', 'Yes', etc.
    if [[ "$user_choice" =~ ^[Yy]([Ee][Ss])?$ ]]; then
        echo -e "${GREEN}   SOLUTION IMMÉDIATE : Lancement du nettoyage manuel forcé pour corriger l'état de la base de données.${NC}"
        echo ""
        force_manual_purge
    else
        echo -e "${RED}   Opération annulée par l'utilisateur. Le script va s'arrêter.${NC}"
        exit 1
    fi
fi

# ==============================================================================
# 8. NETTOYAGE DES MIGRATIONS PROBLÉMATIQUES (TOUTES EXTENSIONS)
# ==============================================================================
echo -e "───[ 8. NETTOYAGE DES MIGRATIONS CORROMPUES ]───────────────────"
sleep 0.1
echo -e "${YELLOW}ℹ️  Certaines extensions peuvent laisser des migrations corrompues qui bloquent la réactivation.${NC}"
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo "🔍 Recherche de migrations avec dépendances non-array (cause array_merge error)..."
echo ""

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
# 9. SUPPRESSION FICHIER cron.lock
# ==============================================================================
echo -e "───[ 9. SUPPRESSION DU FICHIER cron.lock ]──────────────────────"
echo -e "${YELLOW}ℹ️  Un fichier de verrouillage de cron ('cron.lock') peut bloquer l'exécution des tâches planifiées.${NC}"
sleep 0.1
if [ -f "$FORUM_ROOT/store/cron.lock" ]; then
    rm -f "$FORUM_ROOT/store/cron.lock"
    check_status "Fichier cron.lock supprimé."
else
    echo -e "${GREEN}ℹ️  Aucun cron.lock trouvé (déjà absent).${NC}"
fi
# ==============================================================================
# 10. NETTOYAGE FINAL DE LA BASE DE DONNÉES (CRON & NOTIFS ORPHELINES)
# ==============================================================================
echo -e "───[ 10. NETTOYAGE FINAL DE LA BASE DE DONNÉES ]──────────────────────"
echo -e "${YELLOW}ℹ️  Réinitialisation du verrou de cron en BDD et suppression de TOUTES les notifications.${NC}"
sleep 0.1
 
MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'FINAL_CLEANUP_EOF' > /dev/null
-- Réinitialiser le verrou du cron en base de données
UPDATE phpbb_config SET config_value = 0 WHERE config_name = 'cron_lock';

-- Vider complètement la table des notifications pour un test propre
TRUNCATE TABLE phpbb_notifications;
FINAL_CLEANUP_EOF

check_status "Nettoyage final de la BDD (cron_lock, toutes notifications)."

# ==============================================================================
# 11. PURGE DU CACHE (AVANT RÉACTIVATION)
# ==============================================================================
echo -e "───[ 11. PURGE DU CACHE (AVANT RÉACTIVATION) ]────────────────────"
echo -e "${YELLOW}ℹ️  Dernière purge pour s'assurer que le forum est dans un état parfaitement propre avant de réactiver.${NC}"
sleep 0.1
output=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé avant réactivation." "$output"

# ==============================================================================
# PAUSE STRATÉGIQUE
# ==============================================================================
echo -e "${YELLOW}ℹ️  Pause de 0.5 seconde pour laisser le temps au système de se stabiliser...${NC}"
sleep 0.5
# ==============================================================================
# 12. DÉFINITION DU BLOC DE DIAGNOSTIC SQL (HEREDOC)
# ==============================================================================
# Ce bloc est défini une seule fois et redirigé vers le descripteur de fichier 3.
# Il sera réutilisé par les étapes 14 et 16.
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
SELECT '🔔 DERNIÈRES 5 NOTIFICATIONS "RÉACTION" CRÉÉES' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';

-- CORRECTION : Ce diagnostic doit chercher le nom long attendu.
SELECT 
    notification_id,
    notification_read,
    notification_time,
    user_id
FROM phpbb_notifications -- Utilisation du nom long pour le diagnostic
WHERE notification_type_id = (SELECT notification_type_id FROM phpbb_notification_types WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction' LIMIT 1)
ORDER BY notification_time DESC 
LIMIT 5;

SELECT '═══════════════════════════════════════════════════════════════' AS '';
SELECT '✅ DIAGNOSTIC TERMINÉ' AS '';
SELECT '═══════════════════════════════════════════════════════════════' AS '';
DIAGNOSTIC_EOF

# ==============================================================================
# 13. DIAGNOSTIC SQL POST-PURGE
# ==============================================================================
echo -e "───[ 13. DIAGNOSTIC POST-PURGE ]────────────────────────────"
echo -e "${YELLOW}ℹ️  Validation de la purge. Recherche de toute trace restante de l'extension...${NC}"
sleep 0.1
echo -e "   (Le mot de passe a été demandé au début du script.)"
echo "" 

REMAINING_TRACES=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'POST_PURGE_CHECK_EOF'
-- Ce bloc vérifie toutes les traces que l'extension aurait pu laisser.
-- Il retourne une ligne pour chaque élément trouvé. S'il ne retourne rien, la purge est parfaite.

SELECT 'CONFIG_REMAINING', config_name, config_value FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%'
UNION ALL
SELECT 'MODULE_REMAINING', module_langname, module_basename FROM phpbb_modules WHERE module_basename LIKE '%\\bastien59960\\reactions\\%'
UNION ALL
-- CORRECTION : La recherche de traces doit être exhaustive et chercher toutes les formes (longues, courtes, ou même juste le mot 'reaction').
SELECT 'NOTIFICATION_TYPE_REMAINING', notification_type_name, notification_type_enabled FROM phpbb_notification_types WHERE notification_type_name LIKE '%reaction%'
UNION ALL 
SELECT 'COLUMN_REMAINING', TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_users' AND COLUMN_NAME LIKE '%reaction%'
UNION ALL
SELECT 'TABLE_REMAINING', TABLE_NAME, 'TABLE' FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions'
UNION ALL
SELECT 'MIGRATION_ENTRY_REMAINING', migration_name, 'MIGRATION' FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%'
UNION ALL
SELECT 'EXT_ENTRY_REMAINING', ext_name, ext_active FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions';

POST_PURGE_CHECK_EOF
)

if [ -z "$REMAINING_TRACES" ] || [ "$(echo "$REMAINING_TRACES" | wc -l)" -eq 0 ]; then
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
    if [ $purge_exit_code -ne 0 ]; then
        echo -e "${WHITE_ON_RED}   CONSEIL : L'échec de 'extension:purge' suivi de ces traces restantes pointe vers une erreur dans vos méthodes 'revert_data()' ou 'revert_schema()'. Vérifiez-les !${NC}"
    else
        echo -e "${WHITE_ON_RED}   Le script va s'arrêter. Corrigez vos méthodes 'revert_*' dans les fichiers de migration avant de relancer.${NC}"
    fi
    echo ""
    exit 1 # Arrêter le script car l'état est incohérent
fi

# ==============================================================================
# 14. VÉRIFICATION PRÉ-ACTIVATION
# ==============================================================================
echo -e "───[ 14. VÉRIFICATION PRÉ-ACTIVATION ]────────────────────────"
echo -e "${YELLOW}ℹ️  Vérification de l'absence de traces avant la réactivation...${NC}"
sleep 0.1

PRE_ENABLE_CHECK=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN <<'PRE_ENABLE_CHECK_EOF'
-- Recherche large de toute trace liée à l'extension (avec parenthèses pour la compatibilité UNION + LIMIT)
(SELECT 'CONFIG' FROM phpbb_config WHERE config_name LIKE 'bastien59960_reactions_%' LIMIT 1)
UNION ALL
(SELECT 'MODULE' FROM phpbb_modules WHERE module_basename LIKE '%\\bastien59960\\reactions\\%' LIMIT 1)
UNION ALL
-- CORRECTION : La recherche de traces doit être exhaustive.
(SELECT 'NOTIFICATION_TYPE' FROM phpbb_notification_types WHERE notification_type_name LIKE '%reaction%' LIMIT 1)
UNION ALL
(SELECT 'TABLE' FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phpbb_post_reactions' LIMIT 1)
UNION ALL
(SELECT 'MIGRATION' FROM phpbb_migrations WHERE migration_name LIKE '%bastien59960%reactions%' LIMIT 1)
UNION ALL
(SELECT 'EXT_ENTRY' FROM phpbb_ext WHERE ext_name = 'bastien59960/reactions' LIMIT 1);
PRE_ENABLE_CHECK_EOF
)

if [ -n "$PRE_ENABLE_CHECK" ]; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Des traces de l'extension ont été trouvées avant la réactivation. L'état n'est pas propre.${NC}"
    echo -e "${YELLOW}   Traces détectées : $(echo $PRE_ENABLE_CHECK | tr '\n' ' ')${NC}"
    echo -e "${WHITE_ON_RED}   Le script va s'arrêter pour éviter une erreur d'activation.${NC}"
    exit 1
else
    echo -e "${GREEN}✅ Aucune trace trouvée. L'environnement est propre pour la réactivation.${NC}"
fi

# ==============================================================================
# 15. RÉACTIVATION EXTENSION
# ==============================================================================
echo -e "───[ 15. RÉACTIVATION DE L'EXTENSION (bastien59960/reactions) ]─────────"
echo -e "${YELLOW}ℹ️  Lancement de la réactivation. C'est ici que les méthodes 'update_*' des migrations sont exécutées.${NC}"
echo -e "${YELLOW}   Première tentative...${NC}"
sleep 0.1
output_enable=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions -vvv 2>&1)
check_status "Première tentative d'activation de l'extension." "$output_enable"

# ==============================================================================
# 16. NETTOYAGE BRUTAL ET 2ÈME TENTATIVE (SI ÉCHEC)
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
    echo -e "───[ 16. PURGE CACHE ET SECONDE TENTATIVE D'ACTIVATION ]──────────"
    sleep 0.1
    
    echo "   Nettoyage agressif du cache à nouveau..."
    rm -vrf "$FORUM_ROOT/cache/production/"* > /dev/null
    $PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv > /dev/null 2>&1
    check_status "Cache purgé après nettoyage manuel."
    
    echo -e "${YELLOW}   Seconde tentative d'activation...${NC}"
    output_enable=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions -vvv 2>&1)
    check_status "Seconde tentative d'activation de l'extension." "$output_enable"
fi

# ==============================================================================
# 17. DIAGNOSTIC SQL POST-RÉACTIVATION
# ==============================================================================
# On ne lance ce diagnostic que si l'activation a réussi (code de sortie 0)
if [ $? -eq 0 ]; then
    echo -e "───[ 17. DIAGNOSTIC POST-RÉACTIVATION (SUCCÈS) ]────────────"
    echo -e "${YELLOW}ℹ️  Vérification de l'état de la base de données après réactivation réussie.${NC}"
    echo -e "${GREEN}ℹ️  Vérification que les migrations ont correctement recréé les structures.${NC}"
    echo ""
    # On ré-exécute le même bloc de diagnostic depuis le descripteur de fichier 3
    MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <&3
fi
# ==============================================================================
# 18. DIAGNOSTIC APPROFONDI POST-ERREUR
# ==============================================================================
if echo "$output_enable" | grep -q -E "PHP Fatal error|PHP Parse error|array_merge"; then
    echo ""
    echo -e "───[ 18. DIAGNOSTIC APPROFONDI APRÈS ERREUR ]───────────────────────"
    echo -e "${YELLOW}ℹ️  Une erreur critique a été détectée. Lancement d'une série de diagnostics pour en trouver la cause.${NC}"
    sleep 0.1
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
        WHEN notification_type_name = 'bastien59960.reactions.notification.type.reaction' THEN '✅ NOM LONG CORRECT (cloche)'
        WHEN notification_type_name = 'bastien59960.reactions.notification.type.reaction_email_digest' THEN '✅ NOM LONG CORRECT (email)'
        ELSE '❌ NOM INVALIDE'
    END as status
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
    echo -e "${YELLOW}   Ils doivent être 'reaction' ou 'reaction_email_digest', et non 'bastien59960.reactions.xxx' ou 'notification.type.xxx'${NC}"
    echo ""
fi

# ==============================================================================
# 19. VÉRIFICATION FINALE DU STATUT DE L'EXTENSION
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Vérification finale pour confirmer que phpBB considère bien l'extension comme active.${NC}"
echo -e "───[ 19. VÉRIFICATION FINALE DU STATUT DE L'EXTENSION ]───────────"
sleep 0.1

# On utilise bien "extension:show" et on isole la ligne de notre extension
EXT_STATUS=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" extension:show | grep "bastien59960/reactions" || true)

# NOUVELLE VÉRIFICATION : On regarde si la ligne commence par un astérisque,
# ce qui signifie "Activé".
if echo "$EXT_STATUS" | grep -q "^\s*\*"; then
    echo -e "${GREEN}✅ Extension détectée comme ACTIVE (présence du '*') — tout est OK.${NC}"
else
    echo -e "${WHITE_ON_RED}⚠️ ATTENTION : L'extension ne ressort pas comme active (pas de '*' au début).${NC}"
fi

# ==============================================================================
# 20. PURGE DU CACHE FINALE (CRUCIAL POUR LES CRONS)
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Purge finale pour forcer phpBB à reconstruire son conteneur de services avec l'extension activée.${NC}"
echo -e "───[ 20. PURGE DU CACHE (APRÈS) - reconstruction services ]───────"
sleep 0.1
output=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" cache:purge -vvv 2>&1)
check_status "Cache purgé et container reconstruit." "$output"

# ==============================================================================
# 21. VÉRIFICATION FINALE DE LA TÂCHE CRON
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Vérification finale pour confirmer que la tâche cron de l'extension est bien enregistrée et visible par phpBB.${NC}"
echo -e "───[ 21. VÉRIFICATION FINALE DE LA TÂCHE CRON ]────────────────────"
sleep 0.1

# Ajout d'une temporisation de 1 seconde pour laisser le temps au système de se stabiliser
echo -e "${YELLOW}ℹ️  Attente de 1 seconde avant la vérification...${NC}"
sleep 1

# Le nom à rechercher est le nom logique retourné par get_name(), et non le nom du service.
# C'est ce nom qui est affiché par `cron:list` si la traduction échoue.
CRON_TASK_NAME="bastien59960.reactions.notification"

CRON_TEST_NAME="bastien59960.reactions.test"

CRON_LIST_OUTPUT=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" cron:list -vvv)

echo -e "${YELLOW}ℹ️  Liste des tâches cron disponibles :${NC}"
echo "$CRON_LIST_OUTPUT"

# ==============================================================================
# 22. DIAGNOSTIC SYSTÉMATIQUE DES TÂCHES CRON
# ==============================================================================
echo ""
echo -e "${YELLOW}ℹ️  Lancement du diagnostic systématique des tâches cron pour valider leur configuration.${NC}"
echo -e "───[ 22. DIAGNOSTIC SYSTÉMATIQUE DES TÂCHES CRON ]───────────"
sleep 0.1

has_error=0

# 1. Vérification des fichiers et de leur syntaxe
print_diag_header "1. VÉRIFICATION DES FICHIERS"
check_diag "Fichier 'notification_task.php' existe" test -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php" || has_error=1
check_diag "Fichier 'test_task.php' existe" test -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php" || has_error=1
check_diag "Syntaxe PHP de 'notification_task.php' est valide" $PHP_CLI -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php" || has_error=1
check_diag "Syntaxe PHP de 'test_task.php' est valide" $PHP_CLI -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php" || has_error=1

# 1.5 Vérification de la syntaxe de services.yml
print_diag_header "1.5 VÉRIFICATION DE LA SYNTAXE DE services.yml"
SERVICES_FILE="$FORUM_ROOT/ext/bastien59960/reactions/config/services.yml"
if [ -f "$SERVICES_FILE" ] && grep -q '^\s*/\*\*' "$SERVICES_FILE"; then
    echo -e "  ${RED}❌ ÉCHEC  :${NC} Le fichier 'services.yml' commence par '/**' (commentaire PHP), ce qui est une syntaxe YAML invalide."
    has_error=1
else
    echo -e "  ${GREEN}✅ SUCCÈS :${NC} La syntaxe des commentaires de 'services.yml' semble correcte."
fi

# 2. Vérification de la configuration des services
print_diag_header "2. VÉRIFICATION DE services.yml"
check_diag "Fichier 'services.yml' existe" test -f "$SERVICES_FILE" || has_error=1
if [ -f "$SERVICES_FILE" ]; then
    # Vérification avec awk corrigé et robuste
    if awk '
        /^[[:space:]]*cron\.task\.bastien59960\.reactions\.notification:/ { in_block=1; found_service=1 }
        /^[a-zA-Z]/ && NR>1 { in_block=0 }
        in_block && /name:[[:space:]]*cron\.task/ { found_tag=1 }
        END {
            if (found_service && found_tag) exit 0
            else exit 1
        }
    ' "$SERVICES_FILE"; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} Déclaration du service 'cron.task.bastien59960.reactions.notification' et tag 'cron.task'"
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} Déclaration du service 'cron.task.bastien59960.reactions.notification' ou tag 'cron.task' manquant."
        has_error=1
    fi

    if awk '
        /^[[:space:]]*cron\.task\.bastien59960\.reactions\.test:/ { in_block=1; found_service=1 }
        /^[a-zA-Z]/ && NR>1 { in_block=0 }
        in_block && /name:[[:space:]]*cron\.task/ { found_tag=1 }
        END {
            if (found_service && found_tag) exit 0
            else exit 1
        }
    ' "$SERVICES_FILE"; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} Déclaration du service 'cron.task.bastien59960.reactions.test' et tag 'cron.task'"
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} Déclaration du service 'cron.task.bastien59960.reactions.test' ou tag 'cron.task' manquant."
        has_error=1
    fi
fi

# 3. Vérification des fichiers de langue
print_diag_header "3. VÉRIFICATION DES FICHIERS DE LANGUE"
LANG_FILE_FR="$FORUM_ROOT/ext/bastien59960/reactions/language/fr/common.php"
check_diag "Fichier de langue 'fr/common.php' existe" test -f "$LANG_FILE_FR" || has_error=1
if [ -f "$LANG_FILE_FR" ]; then
    if grep -q "TASK_BASTIEN59960_REACTIONS_NOTIFICATION" "$LANG_FILE_FR"; then echo -e "  ${GREEN}✅ SUCCÈS :${NC} Clé 'TASK_BASTIEN59960_REACTIONS_NOTIFICATION' présente"; else echo -e "  ${RED}❌ ÉCHEC  :${NC} Clé 'TASK_BASTIEN59960_REACTIONS_NOTIFICATION' absente"; has_error=1; fi
    if grep -q "TASK_BASTIEN59960_REACTIONS_TEST" "$LANG_FILE_FR"; then echo -e "  ${GREEN}✅ SUCCÈS :${NC} Clé 'TASK_BASTIEN59960_REACTIONS_TEST' présente"; else echo -e "  ${RED}❌ ÉCHEC  :${NC} Clé 'TASK_BASTIEN59960_REACTIONS_TEST' absente"; has_error=1; fi
fi

# CORRECTION LOGIQUE : Si une erreur est détectée ici, on arrête le script.
if [ $has_error -ne 0 ]; then
    print_diag_header "🏁 DIAGNOSTIC CRON ÉCHOUÉ"
    echo -e "   ${YELLOW}Pistes de correction :${NC}"
    echo -e "   1. Le problème vient souvent du cache. Essayez de purger le cache :"
    echo -e "      ${YELLOW}$PHP_CLI $FORUM_ROOT/bin/phpbbcli.php cache:purge${NC}"
    echo -e "   2. Si la purge ne suffit pas, désactivez puis réactivez l'extension pour forcer la reconstruction des services."
    echo -e "   3. Vérifiez que les noms des services dans services.yml correspondent exactement (ex. : cron.task.bastien59960.reactions.notification)."
    echo -e "   4. Vérifiez les clés de langue dans $LANG_FILE_FR."
    exit 1
fi

if echo "$CRON_LIST_OUTPUT" | grep -q "$CRON_TASK_NAME"; then
    # ==============================================================================
    # 23. RESTAURATION DE LA CONFIGURATION
    # ==============================================================================
    # On ne restaure que si une valeur a été sauvegardée.
    if [ -n "$SPAM_TIME_BACKUP" ]; then
        echo ""
        echo -e "───[ 23. RESTAURATION DE LA CONFIGURATION ]──────────"
        echo -e "${YELLOW}ℹ️  Restauration de la valeur du délai anti-spam à ${GREEN}${SPAM_TIME_BACKUP} minutes${NC}..."
        sleep 0.1

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
    # 24. RESTAURATION DES DONNÉES
    # ==============================================================================
    # Cette étape est cruciale. Elle restaure les données sauvegardées au début du script
    # dans la table fraîchement recréée par la réactivation de l'extension.
    if echo "$EXT_STATUS" | grep -q "^\s*\*"; then
        echo -e "───[ 24. RESTAURATION DES RÉACTIONS ]─────────"
        echo -e "${YELLOW}ℹ️  L'extension est active. Réinjection des données depuis la sauvegarde...${NC}"
        sleep 0.1
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
# 25. RESTAURATION DES NOTIFICATIONS
# ==============================================================================
echo -e "───[ 25. RESTAURATION DES NOTIFICATIONS 'CLOCHE' ]─────────"
echo -e "${YELLOW}ℹ️  Réinjection des notifications 'cloche' depuis la sauvegarde...${NC}"
sleep 0.1
echo -e "   (Le mot de passe a été demandé au début du script.)"

BACKUP_NOTIF_ROWS=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_notifications_backup;" 2>/dev/null || echo 0)

if [ "$BACKUP_NOTIF_ROWS" -gt 0 ]; then
    restore_notif_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'RESTORE_NOTIF_EOF'
        -- Insérer les notifications sauvegardées, en ignorant les doublons si certains existent déjà.
        INSERT IGNORE INTO phpbb_notifications SELECT * FROM phpbb_notifications_backup;
RESTORE_NOTIF_EOF
    )
    check_status "Restauration des notifications depuis 'phpbb_notifications_backup'." "$restore_notif_output"
else
    echo -e "${GREEN}ℹ️  Restauration des notifications ignorée : la sauvegarde est vide ou absente.${NC}"
fi


# ==============================================================================
# 26. PEUPLEMENT DE LA BASE DE DONNÉES (DEBUG)
# ==============================================================================
echo ""
echo -e "───[ 26. PEUPLEMENT DE LA BASE DE DONNÉES (DEBUG) ]────────"
echo -e "${YELLOW}ℹ️  Vérification si la table des réactions est vide pour la peupler avec des données de test.${NC}"
sleep 0.1

# Vérifier si la table des réactions est vide
REACTIONS_COUNT=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM phpbb_post_reactions;" 2>/dev/null || echo 0)

if [ "$REACTIONS_COUNT" -eq 0 ]; then
    echo -e "${GREEN}   Lancement du peuplement avec des données aléatoires pour le débogage...${NC}"
    
    # Exécuter le script SQL de peuplement et capturer la sortie
    # Exécuter le script SQL de peuplement et capturer la sortie et le code de sortie
    {
        seeding_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" --default-character-set=utf8mb4 <<'SEEDING_EOF'
            -- Étape 1: Vider les tables temporaires si elles existent (sécurité)
            DROP TEMPORARY TABLE IF EXISTS temp_posts, temp_users, temp_emojis;

            -- Étape 2: Créer des tables temporaires pour stocker les posts, utilisateurs et emojis
            CREATE TEMPORARY TABLE temp_posts (post_id INT, topic_id INT, poster_id INT, PRIMARY KEY (post_id));
            CREATE TEMPORARY TABLE temp_users (user_id INT, PRIMARY KEY (user_id));
            CREATE TEMPORARY TABLE temp_emojis (emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin, PRIMARY KEY (emoji));

            -- Étape 3: Peupler les tables temporaires
            INSERT INTO temp_posts (post_id, topic_id, poster_id) SELECT post_id, topic_id, poster_id FROM phpbb_posts WHERE post_visibility = 1 ORDER BY post_time DESC LIMIT 50;
            INSERT INTO temp_users (user_id) SELECT user_id FROM phpbb_users WHERE user_type != 2 AND user_id != 1 ORDER BY RAND() LIMIT 20;
            INSERT INTO temp_emojis (emoji) VALUES ('💩'), ('🤡'), ('🖕'), ('🗿'), ('🐸'), ('👻'), ('🤢'), ('👽'), ('🤏'), ('💀'), (''), ('🧠'), ('👀'), ('🧢'), ('💅'), ('🔥'), ('💯'), ('🤣'), ('🤔'), ('🤯');

            -- Étape 4: Générer les réactions
            -- CORRECTION : La clause LIMIT n'accepte pas de sous-requête.
            -- On calcule la limite dans une variable, puis on l'utilise dans une instruction préparée.
            SET @post_limit = (SELECT CEIL(COUNT(*)/5) FROM temp_users);

            -- Créer une table temporaire pour stocker les IDs des posts ciblés
            DROP TEMPORARY TABLE IF EXISTS temp_target_posts;
            CREATE TEMPORARY TABLE temp_target_posts (post_id INT);

            -- Utiliser une instruction préparée pour contourner la limitation de LIMIT
            SET @sql = 'INSERT INTO temp_target_posts SELECT post_id FROM temp_posts ORDER BY post_id ASC LIMIT ?';
            PREPARE stmt FROM @sql;
            EXECUTE stmt USING @post_limit;
            DEALLOCATE PREPARE stmt;

            -- CORRECTION : Utiliser INSERT IGNORE pour éviter les erreurs de clé dupliquée.
            INSERT IGNORE INTO phpbb_post_reactions (post_id, topic_id, user_id, reaction_emoji, reaction_time, reaction_notified)
            SELECT
                p.post_id, p.topic_id, u.user_id,
                (SELECT emoji FROM temp_emojis ORDER BY RAND() LIMIT 1) AS reaction_emoji,
                UNIX_TIMESTAMP() - FLOOR(RAND() * 2592000) AS reaction_time, -- Réactions sur les 30 derniers jours
                0 AS reaction_notified
            FROM temp_posts p, temp_users u
            WHERE p.poster_id != u.user_id
            -- CORRECTION : Logique ajustée pour générer entre 1 et 10 réactions par post.
            AND RAND() < (1 + (RAND() * 9)) / (SELECT COUNT(*) FROM temp_users)
            LIMIT 400;

            -- Étape 5: Renvoyer un résumé de ce qui a été fait
            SELECT 
                CONCAT('Utilisateurs actifs utilisés : ', (SELECT COUNT(*) FROM temp_users)),
                CONCAT('Messages ciblés : ', (SELECT COUNT(*) FROM temp_posts)),
                CONCAT('Réactions générées : ', ROW_COUNT());
SEEDING_EOF
        )
    }
    seeding_exit_code=$?

    # Vérifier le statut de l'opération
    (exit $seeding_exit_code); check_status "Peuplement de la base de données avec des réactions de test." "$seeding_output"
    
    # N'afficher la jolie sortie que si l'opération a réussi
    if [ $seeding_exit_code -eq 0 ]; then
        echo -e "${GREEN}"
        echo "            .-\"\"\"-."
        echo "           /       \\"
        echo "           \\.---. ./"
        echo "           ( 🎲 🎲 )    DATABASE SEEDING"
        echo "    _..oooO--(_)--Oooo.._"
        echo "    \`--. .--. .--. .--'\`"
        echo "       TEST DATA LOADED"
        echo -e "${NC}"
        
        echo "┌──────────────────────────────────────────────────┐"
        echo "│ 📊 RÉSUMÉ DU PEUPLEMENT DE LA BASE DE DONNÉES      │"
        echo "├──────────────────────────────────────────────────┤"
        echo "$seeding_output" | while IFS=$'\t' read -r users posts reactions; do
            printf "│ %-48s │\n" "$users"
            printf "│ %-48s │\n" "$posts"
            printf "│ %-48s │\n" "$reactions"
        done
        echo "└──────────────────────────────────────────────────┘"
    fi
else
    echo -e "${GREEN}ℹ️  La table des réactions contient déjà ${REACTIONS_COUNT} réaction(s). Peuplement ignoré.${NC}"
fi

    # ==============================================================================
    # 27. RÉINITIALISATION DES FLAGS DE NOTIFICATION (POUR DEBUG)
    # ==============================================================================
    echo ""
    echo -e "───[ 27. RÉINITIALISATION DES FLAGS DE NOTIFICATION (DEBUG) ]────────"
    echo -e "${YELLOW}ℹ️  Remise à zéro de tous les flags 'reaction_notified' pour forcer l'envoi d'un email de test.${NC}"
    echo -e "${YELLOW}   Cela permet de tester les corrections UTF-8 sur les emojis et les caractères accentués.${NC}"
    sleep 0.1
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
    # 28. GÉNÉRATION DE FAUSSES NOTIFICATIONS (DEBUG CLOCHE)
    # ==============================================================================
    echo ""
    echo -e "───[ 28. GÉNÉRATION DE FAUSSES NOTIFICATIONS (DEBUG) ]────────"
    echo -e "${YELLOW}ℹ️  Cette étape peut générer de fausses notifications 'cloche' pour tester leur affichage.${NC}"
    sleep 0.1

    echo -e "${YELLOW}   Voulez-vous générer de fausses notifications 'cloche' ? (y/n)${NC}"
    read -r user_choice_notif

    if [[ "$user_choice_notif" =~ ^[Yy]([Ee][Ss])?$ ]]; then
        echo ""
        REACTION_NOTIF_TYPE_ID=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT notification_type_id FROM phpbb_notification_types WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction' LIMIT 1;")

        if [ -z "$REACTION_NOTIF_TYPE_ID" ]; then
            echo -e "${RED}❌ ERREUR : Impossible de trouver l'ID du type de notification 'bastien59960.reactions.notification.type.reaction'. Étape ignorée.${NC}"
        else
            echo -e "${GREEN}   Type de notification trouvé (ID: $REACTION_NOTIF_TYPE_ID).${NC}"

            # Sélectionner jusqu'à 5 réactions aléatoires pour générer des notifications
            REACTIONS_TO_NOTIFY=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" --default-character-set=utf8mb4 -sN <<'GET_REACTIONS_EOF'
                SELECT 
                    r.post_id,
                    r.topic_id,
                    p.poster_id,      -- ID de l'auteur du post (destinataire de la notif)
                    r.user_id,        -- ID de l'utilisateur qui réagit
                    u.username,       -- Nom de l'utilisateur qui réagit
                    r.reaction_emoji
                FROM phpbb_post_reactions r
                JOIN phpbb_posts p ON r.post_id = p.post_id
                JOIN phpbb_users u ON r.user_id = u.user_id
                WHERE p.poster_id != r.user_id -- Exclure les auto-réactions
                ORDER BY RAND()
                LIMIT 5;
GET_REACTIONS_EOF
            )

            if [ -z "$REACTIONS_TO_NOTIFY" ]; then
                echo -e "${YELLOW}   Aucune réaction disponible pour générer des notifications.${NC}"
            else
                echo -e "${GREEN}   Génération de fausses notifications pour les réactions suivantes :${NC}"
                
                notification_count=0
                # Lire la sortie ligne par ligne
                while IFS=$'\t' read -r post_id topic_id poster_id reacter_id reacter_name reaction_emoji; do
                    # Échapper les apostrophes dans le nom d'utilisateur pour la requête SQL
                    reacter_name_escaped=$(echo "$reacter_name" | sed "s/'/''/g")
                    
                    # CORRECTION : Calculer la longueur en BYTES, pas en caractères
                    # Pour bash, on utilise un sous-shell avec printf qui gère UTF-8
                    reacter_name_bytes=$(printf '%s' "$reacter_name" | wc -c)
                    emoji_bytes=$(printf '%s' "$reaction_emoji" | wc -c)

                    # Construire la chaîne sérialisée avec les longueurs en bytes
                    notification_data_serialized="a:3:{s:10:\"reacter_id\";i:${reacter_id};s:12:\"reacter_name\";s:${reacter_name_bytes}:\"${reacter_name_escaped}\";s:14:\"reaction_emoji\";s:${emoji_bytes}:\"${reaction_emoji}\";}"

                    # NOUVEAU CODE (sérialisé direct - compatible phpBB) :
                    # Échapper les caractères spéciaux pour MySQL (' et \)
                    notification_data_escaped=$(printf '%s' "$notification_data_serialized" | sed "s/\\\\/\\\\\\\\/g; s/'/\\\\'/g")

                    # La requête insère la chaîne sérialisée et échappée directement.
                    insert_sql="INSERT INTO phpbb_notifications (notification_type_id, item_id, item_parent_id, user_id, notification_read, notification_time, notification_data) VALUES (${REACTION_NOTIF_TYPE_ID}, ${post_id}, ${topic_id}, ${poster_id}, 0, UNIX_TIMESTAMP(), '${notification_data_escaped}');"
                    
                    # Exécuter la requête
                    MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" --default-character-set=utf8mb4 -e "$insert_sql"
                    
                    if [ $? -eq 0 ]; then
                        echo -e "      ✅ Notif pour le post #${post_id} (auteur #${poster_id}) : ${reaction_emoji} par ${reacter_name}"
                        notification_count=$((notification_count + 1))
                    else
                        echo -e "      ❌ Échec de la création de la notif pour le post #${post_id}"
                    fi
                done <<< "$REACTIONS_TO_NOTIFY"

                echo -e "${GREEN}   ${notification_count} fausse(s) notification(s) générée(s) avec succès.${NC}"
            fi
        fi
    else
        echo -e "${YELLOW}ℹ️  Génération de fausses notifications ignorée par l'utilisateur.${NC}"
    fi

    # ==============================================================================
    # 29. TEST DE L'EXÉCUTION DU CRON
    # ==============================================================================
    echo -e "───[ 29. TEST FINAL DU CRON ]───────────────────────────────────"
    echo -e "${YELLOW}ℹ️  Tentative d'exécution de toutes les tâches cron pour vérifier que le système est fonctionnel.${NC}"
    echo -e "${YELLOW}   Les réactions restaurées devraient maintenant être traitées.${NC}"
    sleep 0.1

    output=$($PHP_CLI "$FORUM_ROOT/bin/phpbbcli.php" cron:run -vvv 2>&1)
    check_status "Exécution de toutes les tâches cron prêtes." "$output"

    # ==============================================================================
    # PAUSE STRATÉGIQUE POUR ÉVITER UNE RACE CONDITION
    # ==============================================================================
    echo -e "${YELLOW}ℹ️  Pause de 1 seconde pour laisser le temps à la base de données de se synchroniser...${NC}"
    sleep 1
    # ==============================================================================
    # 30. VÉRIFICATION POST-CRON (LA PREUVE)
    # ==============================================================================
    echo -e "───[ 30. VÉRIFICATION POST-CRON (LA PREUVE) ]───────────────────"
    echo -e "${YELLOW}ℹ️  Vérification de l'état des réactions dans la base de données après l'exécution du cron.${NC}"
    sleep 0.1

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
    # 31. VALIDATION FINALE DU TRAITEMENT CRON
    # ==============================================================================
    echo ""
    echo -e "───[ 31. VALIDATION FINALE DU TRAITEMENT CRON ]─────────────────"
    echo -e "${YELLOW}ℹ️  Vérification qu'il ne reste aucune réaction éligible non traitée.${NC}"
    sleep 0.1

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
        echo ""
        echo -e "${YELLOW}            .-''-."
        echo -e "           /  (  )  \\"
        echo -e "          |   o  o   |"
        echo -e "          |  .._..   |"
        echo -e "           \\      /  -- BUGS FIXED"
        echo -e "            \`-..-'\`"
    fi
else
    echo -e "\n${WHITE_ON_RED}❌ ERREUR : La tâche cron '$CRON_TASK_NAME' est ABSENTE de la liste !${NC}\n"
    has_error=1
fi

# ==============================================================================
# 32. CORRECTION FINALE ET DÉFINITIVE DES PERMISSIONS
# ==============================================================================
echo ""
echo -e "───[ 32. CORRECTION FINALE DES PERMISSIONS ]────────────────────"
echo -e "${YELLOW}ℹ️  Application des permissions correctes en toute fin de script pour garantir l'accès au forum.${NC}"

WEB_USER="www-data"
WEB_GROUP="www-data"

sudo chown -R "$WEB_USER":"$WEB_GROUP" "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload"
check_status "Propriétaire des répertoires critiques mis à jour."

sudo find "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload" -type d -exec chmod 0777 {} \;
sudo find "$FORUM_ROOT/cache" "$FORUM_ROOT/store" "$FORUM_ROOT/files" "$FORUM_ROOT/images/avatars/upload" -type f -exec chmod 0666 {} \;
check_status "Permissions de lecture/écriture (777/666) appliquées."

# ==============================================================================
# 33. DIAGNOSTIC FINAL (APRÈS TOUTES LES OPÉRATIONS)
# ==============================================================================
echo ""
echo -e "───[ 33. DIAGNOSTIC FINAL ]────────────────────"
echo -e "${YELLOW}ℹ️  État final des notifications et des types de notifications après toutes les opérations...${NC}"
sleep 0.1
 
 # Créer un script PHP temporaire pour le diagnostic
 PHP_DIAG_SCRIPT=$(mktemp)
 cat > "$PHP_DIAG_SCRIPT" <<'PHP_DIAG_EOF'
<?php

function draw_table(array $headers, array $rows) {
    $widths = [];
    foreach ($headers as $key => $header) {
        $widths[$key] = mb_strlen($header);
    }
    foreach ($rows as $row) {
        foreach ($row as $key => $cell) {
            $widths[$key] = max($widths[$key], mb_strlen($cell));
        }
    }

    $separator = '+';
    $header_line = '|';
    foreach ($headers as $key => $header) {
        $separator .= str_repeat('-', $widths[$key] + 2) . '+';
        $header_line .= ' ' . str_pad($header, $widths[$key]) . ' |';
    }

    echo $separator . "\n";
    echo $header_line . "\n";
    echo $separator . "\n";

    if (empty($rows)) {
        echo '| ' . str_pad('Aucune donnée', mb_strlen($separator) - 5) . " |\n";
    } else {
        foreach ($rows as $row) {
            $row_line = '|';
            foreach ($row as $key => $cell) {
                $row_line .= ' ' . str_pad($cell, $widths[$key]) . ' |';
            }
            echo $row_line . "\n";
        }
    }
    echo $separator . "\n";
}

$db_user = getenv('DB_USER');
$db_name = getenv('DB_NAME');
$db_pass = getenv('MYSQL_PASSWORD');
$db_host = 'localhost';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit(1);
}

// 1. Vérifier les types de notifications
echo "\n📊 Types de notifications de réaction\n";
$stmt = $pdo->query("SELECT * FROM phpbb_notification_types WHERE notification_type_name LIKE '%reaction%'");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
$type_rows = [];
foreach ($types as $type) {
    $type_rows[] = [
        'id' => $type['notification_type_id'],
        'name' => $type['notification_type_name'],
        'enabled' => $type['notification_type_enabled'] ? 'Oui' : 'Non',
    ];
}
draw_table(['id' => 'ID', 'name' => 'Nom', 'enabled' => 'Activé'], $type_rows);

// 3. Vérifier le charset de la colonne notification_data
echo "\n⚙️  Vérification du format de la colonne 'notification_data'\n";
$stmt = $pdo->prepare("
    SELECT 
        COLUMN_NAME,
        CHARACTER_SET_NAME,
        COLLATION_NAME,
        COLUMN_TYPE
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = :db_name
      AND TABLE_NAME = 'phpbb_notifications' 
      AND COLUMN_NAME = 'notification_data'
");
$stmt->execute(['db_name' => $db_name]);
$column_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
draw_table(
    [
        'COLUMN_NAME' => 'Colonne',
        'CHARACTER_SET_NAME' => 'Charset',
        'COLLATION_NAME' => 'Collation',
        'COLUMN_TYPE' => 'Type'
    ],
    $column_info
);

// Vérifier si le charset est correct et afficher une erreur si ce n'est pas le cas
if (isset($column_info[0]) && $column_info[0]['CHARACTER_SET_NAME'] !== 'utf8mb4') {
    echo "\n\033[1;41;37m⚠️  ATTENTION : La colonne 'notification_data' n'est PAS en utf8mb4 ! \033[0m";
    echo "\n\033[1;33m   La migration n'a pas fonctionné correctement. Les emojis risquent de ne pas être stockés.\033[0m\n";
} else if (isset($column_info[0])) {
    echo "\n\033[0;32m✅ Le format de la colonne est correct (utf8mb4).\033[0m\n";
}

// 2. Vérifier et décoder les 10 dernières notifications
echo "\n🔔 Analyse détaillée des 10 dernières notifications 'cloche'\n";
$stmt = $pdo->query("
    SELECT * FROM phpbb_notifications 
    WHERE notification_type_id = (SELECT notification_type_id FROM phpbb_notification_types WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction' LIMIT 1)
    ORDER BY notification_time DESC 
    LIMIT 10
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notif_rows = [];
foreach ($notifications as $notif) {
    // CORRECTION : Le script insère maintenant du PHP sérialisé direct.
    // Le diagnostic tente donc de désérialiser directement, comme le ferait phpBB.
    $raw_data = $notif['notification_data'];
    $data = @unserialize($raw_data);
    
    // Extraire les valeurs
    if ($data === false || !is_array($data)) {
        $reacter_id = 'ERREUR';
        $reacter_name = 'DECODAGE';
        $reaction_emoji = '!!';

        // Afficher un diagnostic en cas d'échec
        echo "\n=== DEBUG NOTIFICATION #{$notif['notification_id']} ===\n";
        echo "❌ Échec de la désérialisation.\n";
        echo "   Données brutes (100 premiers caractères): " . substr($raw_data, 0, 100) . "\n";
        echo "===============================\n\n";
    } else {
        $reacter_id = $data['reacter_id'] ?? 'N/A';
        $reacter_name = $data['reacter_name'] ?? 'N/A';
        $reaction_emoji = $data['reaction_emoji'] ?? 'N/A';
    }

    $notif_rows[] = [
        'notif_id' => $notif['notification_id'],
        'dest_id' => $notif['user_id'],
        'post_id' => $notif['item_id'],
        'read' => $notif['notification_read'] ? 'Oui' : 'Non',
        'time' => date('Y-m-d H:i:s', $notif['notification_time']),
        'reacter_id' => $reacter_id,
        'reacter_name' => $reacter_name,
        'emoji' => $reaction_emoji,
        'data' => substr($notif['notification_data'], 0, 20) . '...',
    ];
}
draw_table(
    [
        'notif_id' => 'Notif ID',
        'dest_id' => 'Dest. ID',
        'post_id' => 'Post ID',
        'read' => 'Lue',
        'time' => 'Heure',
        'reacter_id' => 'Réact. ID',
        'reacter_name' => 'Réact. Nom',
        'emoji' => 'Emoji',
        'data' => 'Data (Base64)'
    ],
    $notif_rows
);

PHP_DIAG_EOF
 
 # Exporter les variables et exécuter le script PHP
 export DB_USER DB_NAME MYSQL_PASSWORD
 final_diag_output=$($PHP_CLI "$PHP_DIAG_SCRIPT" 2>&1)
 
 # Nettoyer le script temporaire
 rm -f "$PHP_DIAG_SCRIPT"
 
 # Vérifier et afficher le résultat
 check_status "Diagnostic final détaillé des notifications." "$final_diag_output"
 if [ $? -eq 0 ]; then
     echo "$final_diag_output"
 fi

# ==============================================================================
# 34. NETTOYAGE OPTIONNEL DES NOTIFICATIONS (POST-DIAGNOSTIC)
# ==============================================================================
echo ""
echo -e "───[ 34. NETTOYAGE OPTIONNEL DES NOTIFICATIONS ]────────"
echo -e "${YELLOW}ℹ️  Cette étape peut résoudre des erreurs si des données de notification sont corrompues.${NC}"
echo ""

# Boucle pour s'assurer d'obtenir une réponse valide (y/n)
while true; do
    read -p "Voulez-vous nettoyer les notifications de l'extension Reactions ? (y/n) " -n 1 -r REPLY
    echo "" # Saut de ligne après la saisie
    case $REPLY in
        [Yy]* )
            echo "Lancement de la commande de purge des notifications..."
            # CORRECTION : Remplacement de la commande CLI par une requête SQL directe.
            # C'est plus simple et évite les problèmes de chemin ou de service non trouvé.
            
            purge_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" <<'MANUAL_PURGE_EOF'
-- Récupérer les IDs des types de notification de l'extension
SET @type_ids := (
    SELECT GROUP_CONCAT(notification_type_id) 
    FROM phpbb_notification_types
    WHERE notification_type_name LIKE 'bastien59960.reactions.notification.type.%'
);

-- Supprimer les notifications correspondantes si des types ont été trouvés
DELETE FROM phpbb_notifications 
WHERE FIND_IN_SET(notification_type_id, @type_ids);

SELECT CONCAT(ROW_COUNT(), ' notification(s) de réaction supprimée(s).') AS result;
MANUAL_PURGE_EOF
)
            check_status "Nettoyage manuel des notifications de l'extension Reactions." "$purge_output"
            break
            ;;
        [Nn]* )
            echo "ℹ️  Nettoyage des notifications ignoré."
            break
            ;;
        * )
            echo "Veuillez répondre par 'y' (oui) ou 'n' (non)."
            ;;
    esac
done