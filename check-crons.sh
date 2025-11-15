#!/bin/bash
# ==============================================================================
# Fichier : check-crons.sh
# Rôle : Script de diagnostic avancé pour les tâches cron de l'extension Reactions.
# Auteur : Bastien (bastien59960)
# ==============================================================================

# --- Configuration ---
FORUM_ROOT="/home/bastien/www/forum"
CRON_NOTIFICATION_NAME="bastien59960.reactions.notification"
CRON_TEST_NAME="bastien59960.reactions.test"

# --- Couleurs ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# --- Fonctions utilitaires ---
print_header() {
    echo -e "\n═══════════════════════════════════════════════════════════════"
    echo -e " $1"
    echo -e "═══════════════════════════════════════════════════════════════"
}

check() {
    local description=$1
    shift
    local command_output=$("$@")
    local exit_code=$?

    if [ $exit_code -eq 0 ]; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} $description"
        return 0
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} $description"
        echo -e "     ${YELLOW}Sortie:${NC}\n$command_output"
        return 1
    fi
}

check_grep() {
    local description=$1
    local pattern=$2
    local file=$3

    if grep -q "$pattern" "$file"; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} $description"
        return 0
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} $description"
        return 1
    fi
}

# --- Début du script ---
clear
print_header "🔍 DIAGNOSTIC AVANCÉ DES TÂCHES CRON"

has_error=0

# 1. Vérification des fichiers et de leur syntaxe
print_header "1. VÉRIFICATION DES FICHIERS"
check "Fichier 'notification_task.php' existe" test -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php" || has_error=1
check "Fichier 'test_task.php' existe" test -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php" || has_error=1
check "Syntaxe PHP de 'notification_task.php' est valide" php -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php" || has_error=1
check "Syntaxe PHP de 'test_task.php' est valide" php -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php" || has_error=1

# 2. Vérification de la configuration des services
print_header "2. VÉRIFICATION DE services.yml"
SERVICES_FILE="$FORUM_ROOT/ext/bastien59960/reactions/config/services.yml"
check "Fichier 'services.yml' existe" test -f "$SERVICES_FILE" || has_error=1
if [ -f "$SERVICES_FILE" ]; then
    # Vérifier la déclaration du service ET son tag
    if grep -q "cron.task.bastien59960.reactions.notification:" "$SERVICES_FILE" && grep -A 2 "cron.task.bastien59960.reactions.notification:" "$SERVICES_FILE" | grep -q "name: cron.task"; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} Le service '$CRON_NOTIFICATION_NAME' est bien déclaré avec le tag 'cron.task'."
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} La déclaration du service '$CRON_NOTIFICATION_NAME' ou son tag 'cron.task' est manquant ou incorrect."
        has_error=1
    fi
    if grep -q "cron.task.bastien59960.reactions.test:" "$SERVICES_FILE" && grep -A 2 "cron.task.bastien59960.reactions.test:" "$SERVICES_FILE" | grep -q "name: cron.task"; then
        echo -e "  ${GREEN}✅ SUCCÈS :${NC} Le service '$CRON_TEST_NAME' est bien déclaré avec le tag 'cron.task'."
    else
        echo -e "  ${RED}❌ ÉCHEC  :${NC} La déclaration du service '$CRON_TEST_NAME' ou son tag 'cron.task' est manquant ou incorrect."
        has_error=1
    fi
fi

# 3. Vérification des fichiers de langue
print_header "3. VÉRIFICATION DES FICHIERS DE LANGUE"
LANG_FILE_FR="$FORUM_ROOT/ext/bastien59960/reactions/language/fr/common.php"
check "Fichier de langue 'fr/common.php' existe" test -f "$LANG_FILE_FR" || has_error=1
if [ -f "$LANG_FILE_FR" ]; then
    check_grep "Clé 'TASK_BASTIEN59960_REACTIONS_NOTIFICATION' présente" "TASK_BASTIEN59960_REACTIONS_NOTIFICATION" "$LANG_FILE_FR" || has_error=1
    check_grep "Clé 'TASK_BASTIEN59960_REACTIONS_TEST' présente" "TASK_BASTIEN59960_REACTIONS_TEST" "$LANG_FILE_FR" || has_error=1
fi

# 4. Test d'instanciation via le conteneur
print_header "4. TEST D'INSTANCIATION VIA LE CONTENEUR"
PHP_SCRIPT_OUTPUT=$(php <<PHPTEST
<?php
define('IN_PHPBB', true);
\$phpbb_root_path = '$FORUM_ROOT/';
\$phpEx = 'php';
require_once(\$phpbb_root_path . 'common.' . \$phpEx);

if (!isset(\$phpbb_container)) { echo "ERREUR: Conteneur non chargé.\n"; exit(1); }

try {
    \$service = \$phpbb_container->get('cron.task.bastien59960.reactions.notification');
    echo "SUCCES: cron.task.bastien59960.reactions.notification instancié. Classe: " . get_class(\$service) . ". Nom: " . \$service->get_name() . "\n";
} catch (\\Exception \$e) {
    echo "ERREUR: Impossible d'instancier cron.task.bastien59960.reactions.notification: " . \$e->getMessage() . "\n";
}
try {
    \$service = \$phpbb_container->get('cron.task.bastien59960.reactions.test');
    echo "SUCCES: cron.task.bastien59960.reactions.test instancié. Classe: " . get_class(\$service) . ". Nom: " . \$service->get_name() . "\n";
} catch (\\Exception \$e) {
    echo "ERREUR: Impossible d'instancier cron.task.bastien59960.reactions.test: " . \$e->getMessage() . "\n";
}
PHPTEST
)

if echo "$PHP_SCRIPT_OUTPUT" | grep -q "ERREUR"; then
    echo -e "${RED}$PHP_SCRIPT_OUTPUT${NC}"
    has_error=1
else
    echo -e "${GREEN}$PHP_SCRIPT_OUTPUT${NC}"
fi

# 5. Vérification de la présence dans la liste des crons de phpBB
print_header "5. VÉRIFICATION DANS LA LISTE DES CRONS (phpbbcli)"
echo -e "  ${YELLOW}Note : Cette commande peut être lente, car elle charge tout le framework.${NC}"
CRON_LIST_OUTPUT=$(php "$FORUM_ROOT/bin/phpbbcli.php" cron:list)

if echo "$CRON_LIST_OUTPUT" | grep -q "$CRON_NOTIFICATION_NAME"; then
    echo -e "  ${GREEN}✅ SUCCÈS :${NC} La tâche '$CRON_NOTIFICATION_NAME' est bien enregistrée dans phpBB."
else
    echo -e "  ${RED}❌ ÉCHEC  :${NC} La tâche '$CRON_NOTIFICATION_NAME' est ${RED}ABSENTE${NC} de la liste des crons."
    has_error=1
fi

if echo "$CRON_LIST_OUTPUT" | grep -q "$CRON_TEST_NAME"; then
    echo -e "  ${GREEN}✅ SUCCÈS :${NC} La tâche '$CRON_TEST_NAME' est bien enregistrée dans phpBB."
else
    echo -e "  ${RED}❌ ÉCHEC  :${NC} La tâche '$CRON_TEST_NAME' est ${RED}ABSENTE${NC} de la liste des crons."
    has_error=1
fi

# 6. Résumé final
print_header "🏁 DIAGNOSTIC FINAL"
if [ $has_error -eq 0 ]; then
    echo -e "${GREEN}✅ Toutes les vérifications ont réussi ! Vos tâches cron semblent correctement configurées.${NC}"
    echo -e "   Si elles ne s'exécutent pas, le problème vient probablement de la configuration du cron système ou du trafic du forum."
else
    echo -e "${RED}❌ Des problèmes ont été détectés.${NC}"
    echo -e "   ${YELLOW}Pistes de correction :${NC}"
    echo -e "   1. Si une tâche est ${RED}ABSENTE${NC} de la liste, le problème vient souvent du cache. Essayez de purger le cache :"
    echo -e "      ${YELLOW}php $FORUM_ROOT/bin/phpbbcli.php cache:purge${NC}"
    echo -e "   2. Si la purge ne suffit pas, désactivez puis réactivez l'extension pour forcer la reconstruction des services."
    echo -e "   3. Vérifiez que le fichier ${YELLOW}config/services.yml${NC} ne contient pas d'erreur de syntaxe (indentation, etc.)."
    echo -e "   4. Assurez-vous que les noms des services et les clés de langue correspondent exactement à ce qui est attendu."
fi
