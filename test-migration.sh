#!/bin/bash
# ==============================================================================
# Fichier : test-migration.sh
# Chemin : bastien59960/reactions/test-migration.sh
# Auteur : Bastien (bastien59960)
# Version : 1.0.0
# GitHub : https://github.com/bastien59960/reactions
#
# Rôle :
# Script de test ciblé pour exécuter et valider des requêtes SQL spécifiques
# (par exemple, celles d'une nouvelle migration) contre la base de données
# du forum. Conçu pour un débogage rapide et isolé.
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
PHP_ERROR_LOG="/var/log/php/debug.err" # Fichier pour les erreurs et les logs du script

# --- Couleurs ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
WHITE_ON_RED='\033[1;41;37m'
NC='\033[0m'

# ==============================================================================
# FONCTION DE VÉRIFICATION
# ==============================================================================

# Fonction de vérification de statut
check_status() {
    local exit_code=$?
    local step_description=$1
    local output=$2

    if [ $exit_code -ne 0 ]; then
        echo -e "${WHITE_ON_RED}❌ ERREUR lors de l'étape : $step_description${NC}"
        echo -e "${YELLOW}   Sortie complète de la commande :${NC}"
        echo "$output" | sed 's/^/   | /'
        exit $exit_code
    else
        echo -e "${GREEN}✅ SUCCÈS : $step_description${NC}"
    fi
}

# Fonction de logging
log_to_file() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] - $1" >> "$PHP_ERROR_LOG"
}

# ==============================================================================
# DÉBUT DU SCRIPT
# ==============================================================================

clear
echo -e "╔══════════════════════════════════════════════════════════════╗"
echo -e "║   🔬  TEST DE REQUÊTES SQL DE MIGRATION                      ║"
echo -e "╚══════════════════════════════════════════════════════════════╝"
echo -e "🚀 Lancement du script de test SQL.\n"


# ==============================================================================
# 1. INITIALISATION DU FICHIER DE LOG
# ==============================================================================
echo -e "───[ 1. INITIALISATION DU FICHIER DE LOG ]────────────────────────"
echo -e "${YELLOW}ℹ️  Initialisation du fichier de log : $PHP_ERROR_LOG${NC}"
echo -e "${YELLOW}   Cela peut nécessiter les droits sudo.${NC}"

if ! sudo mkdir -p "$(dirname "$PHP_ERROR_LOG")"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Impossible de créer le répertoire de log $(dirname "$PHP_ERROR_LOG").${NC}"
fi

if ! sudo touch "$PHP_ERROR_LOG" || ! sudo chown "$USER":"$(id -g -n "$USER")" "$PHP_ERROR_LOG"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Impossible de créer ou de définir les permissions pour le fichier de log.${NC}"
else
    > "$PHP_ERROR_LOG" # Vider le fichier
    log_to_file "SCRIPT START: Le script test-migration.sh a démarré."
    check_status "Initialisation et permissions du fichier de log."
fi

# ==============================================================================
# 2. DEMANDE DU MOT DE PASSE MYSQL
# ==============================================================================
echo -e "🔑 Veuillez entrer le mot de passe MySQL pour l'utilisateur ${YELLOW}$DB_USER${NC} :"
read -s MYSQL_PASSWORD
echo ""

# ==============================================================================
# 3. VÉRIFICATION DE LA CONNEXION MYSQL
# ==============================================================================
echo -e "───[ 3. VÉRIFICATION DE LA CONNEXION MYSQL ]────────────────────────"
log_to_file "Vérification de la connexion MySQL..."
mysql_test_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -e "SELECT 1;" 2>&1)
if echo "$mysql_test_output" | grep -q "Access denied"; then
    log_to_file "ERREUR: Connexion refusée. Mot de passe incorrect."
    echo -e "${WHITE_ON_RED}❌ ERREUR : Connexion refusée. Mot de passe incorrect.${NC}"
    exit 1
else
    log_to_file "Connexion à la base de données établie."
    echo -e "${GREEN}✅ Connexion à la base de données établie.${NC}"
fi

# ==============================================================================
# 4. EXÉCUTION DES REQUÊTES DE TEST (MIGRATION 1.0.3)
# ==============================================================================
echo -e "\n───[ 4. EXÉCUTION DES REQUÊTES SQL DE TEST (MIGRATION 1.0.3) ]─────"
echo -e "${YELLOW}ℹ️  Exécution du bloc de requêtes défini dans le script...${NC}"
log_to_file "Exécution des requêtes de test pour la migration 1.0.3."

sql_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t --default-character-set=utf8mb4 <<'SQL_TEST_EOF'

-- ########################################################################## --
-- ##                                                                      ## --
-- ##    TEST DE LA MIGRATION release_1_0_3.php (conversion utf8mb4)       ## --
-- ##                                                                      ## --
-- ########################################################################## --

-- ============================================================================
-- ÉTAPE 0 : DIAGNOSTIC AVANT MODIFICATION
-- ============================================================================
SELECT '--- DIAGNOSTIC AVANT MODIFICATION ---' AS 'INFO';
SELECT 
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    COLUMN_TYPE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'phpbb_notifications' 
  AND COLUMN_NAME = 'notification_data';

-- ============================================================================
-- ÉTAPE 1 : CONVERSION EN BLOB (pour préserver les données)
-- ============================================================================
SELECT '--- ÉTAPE 1 : Conversion en MEDIUMBLOB ---' AS 'INFO';
ALTER TABLE phpbb_notifications MODIFY notification_data MEDIUMBLOB;

-- ============================================================================
-- ÉTAPE 2 : CONVERSION EN MEDIUMTEXT utf8mb4 (cible de la migration)
-- ============================================================================
SELECT '--- ÉTAPE 2 : Conversion en MEDIUMTEXT utf8mb4 ---' AS 'INFO';
ALTER TABLE phpbb_notifications MODIFY notification_data MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

-- ============================================================================
-- ÉTAPE 3 : DIAGNOSTIC APRÈS MODIFICATION
-- ============================================================================
SELECT '--- DIAGNOSTIC APRÈS MODIFICATION ---' AS 'INFO';
SELECT 
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    COLUMN_TYPE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'phpbb_notifications' 
  AND COLUMN_NAME = 'notification_data';

SQL_TEST_EOF
)

check_status "Exécution des requêtes SQL de test." "$sql_output"

echo -e "\n${YELLOW}--- RÉSULTAT DES REQUÊTES ---${NC}"
log_to_file "Résultat des requêtes SQL :"
log_to_file "$sql_output"
echo "$sql_output"
echo -e "${YELLOW}----------------------------${NC}"

echo -e "\n${GREEN}🎉 Script de test terminé.${NC}"
log_to_file "SCRIPT END: Le script test-migration.sh s'est terminé."