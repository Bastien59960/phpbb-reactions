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

# ==============================================================================
# DÉBUT DU SCRIPT
# ==============================================================================

clear
echo -e "╔══════════════════════════════════════════════════════════════╗"
echo -e "║   🔬  TEST DE REQUÊTES SQL DE MIGRATION                      ║"
echo -e "╚══════════════════════════════════════════════════════════════╝"
echo -e "🚀 Lancement du script de test SQL.\n"

# ==============================================================================
# 1. DEMANDE DU MOT DE PASSE MYSQL
# ==============================================================================
echo -e "🔑 Veuillez entrer le mot de passe MySQL pour l'utilisateur ${YELLOW}$DB_USER${NC} :"
read -s MYSQL_PASSWORD
echo ""

# ==============================================================================
# 2. VÉRIFICATION DE LA CONNEXION MYSQL
# ==============================================================================
echo -e "───[ 1. VÉRIFICATION DE LA CONNEXION MYSQL ]────────────────────────"
mysql_test_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -e "SELECT 1;" 2>&1)
if echo "$mysql_test_output" | grep -q "Access denied"; then
    echo -e "${WHITE_ON_RED}❌ ERREUR : Connexion refusée. Mot de passe incorrect.${NC}"
    exit 1
else
    echo -e "${GREEN}✅ Connexion à la base de données établie.${NC}"
fi

# ==============================================================================
# 3. EXÉCUTION DES REQUÊTES DE TEST
# ==============================================================================
echo -e "\n───[ 2. EXÉCUTION DES REQUÊTES SQL DE TEST ]──────────────────────"
echo -e "${YELLOW}ℹ️  Exécution du bloc de requêtes défini dans le script...${NC}"

sql_output=$(MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$DB_USER" "$DB_NAME" -t <<'SQL_TEST_EOF'

-- ########################################################################## --
-- ##                                                                      ## --
-- ##    ⬇️   COPIEZ-COLLEZ VOS REQUÊTES SQL DE TEST CI-DESSOUS   ⬇️     ## --
-- ##                                                                      ## --
-- ########################################################################## --

SELECT 'Exemple de requête : comptage des utilisateurs' AS 'INFO';
SELECT COUNT(*) FROM phpbb_users;

-- Vous pouvez ajouter ici des ALTER TABLE, des INSERT, des SELECT, etc.
-- Par exemple, pour tester une nouvelle colonne :
-- ALTER TABLE phpbb_users ADD COLUMN IF NOT EXISTS user_test_col INT(11) DEFAULT 0;
-- SELECT user_id, username, user_test_col FROM phpbb_users LIMIT 5;


SQL_TEST_EOF
)

check_status "Exécution des requêtes SQL de test." "$sql_output"

echo -e "\n${YELLOW}--- RÉSULTAT DES REQUÊTES ---${NC}"
echo "$sql_output"
echo -e "${YELLOW}----------------------------${NC}"

echo -e "\n${GREEN}🎉 Script de test terminé.${NC}"