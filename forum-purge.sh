#!/bin/bash
# forum-purge.sh - Script de maintenance complet pour le forum phpBB (Goth & Space Invader Edition)

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
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ SUCCÈS : $1${NC}"
    else
        echo -e "${WHITE_ON_RED}❌ ERREUR : $1${NC}"
        exit 1
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
sleep 1

# ==============================================================================
# 1️⃣ PURGE CACHE (AVANT)
# ==============================================================================
echo "───[ 1️⃣  PURGE DU CACHE (AVANT) ]────────────────────────────────"
sleep 1
php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge
check_status "Cache initial purgé."

# ==============================================================================
# 2️⃣ DÉSACTIVATION DE L'EXTENSION
# ==============================================================================
echo "───[ 2️⃣  DÉSACTIVATION DE L'EXTENSION (bastien59960/reactions) ]────────────"
sleep 1
php "$FORUM_ROOT/bin/phpbbcli.php" extension:disable bastien59960/reactions
check_status "Extension désactivée."

# ==============================================================================
# 3️⃣ SUPPRESSION FICHIER cron.lock
# ==============================================================================
echo "───[ 3️⃣  SUPPRESSION DU FICHIER cron.lock ]──────────────────────"
sleep 1
CRON_LOCK_FILE="$FORUM_ROOT/cache/cron.lock"
if [ -f "$CRON_LOCK_FILE" ]; then
    rm -f "$CRON_LOCK_FILE"
    check_status "Fichier cron.lock supprimé."
else
    echo -e "${GREEN}ℹ️  Aucun cron.lock trouvé (déjà absent).${NC}"
fi

# ==============================================================================
# 4️⃣ SQL RESET – UN SEUL PROMPT
# ==============================================================================
echo "───[ 4️⃣  RÉINITIALISATION SQL (UN SEUL PROMPT) ]──────────────────"
echo -e "⚠️  Le script va maintenant demander ${YELLOW}UNE SEULE FOIS${NC} le mot de passe MySQL..."
sleep 1

mysql -u "$DB_USER" -p "$DB_NAME" <<EOF
UPDATE phpbb_post_reactions SET reaction_notified = 0;
UPDATE phpbb_config SET config_value = 0 WHERE config_name = 'cron_lock';
EOF

check_status "Requêtes SQL exécutées : reaction_notified + cron_lock."

# ==============================================================================
# 5️⃣ RÉACTIVATION EXTENSION
# ==============================================================================
echo "───[ 5️⃣  RÉACTIVATION DE L'EXTENSION (bastien59960/reactions) ]─────────────"
sleep 1
php "$FORUM_ROOT/bin/phpbbcli.php" extension:enable bastien59960/reactions
check_status "Extension réactivée."

# ==============================================================================
# 6️⃣ PURGE CACHE (APRÈS)
# ==============================================================================
echo "───[ 6️⃣  PURGE DU CACHE (APRÈS) - reconstruction services ]──────"
sleep 1
php "$FORUM_ROOT/bin/phpbbcli.php" cache:purge
check_status "Cache purgé et container reconstruit."

# ==============================================================================
# 7️⃣ TEST FINAL DU CRON
# ==============================================================================
echo "───[ 7️⃣  TEST FINAL DU CRON ]──────────────────────────────────"
sleep 1
php "$FORUM_ROOT/bin/phpbbcli.php" cron:run
check_status "Cron exécuté."


# ==============================================================================
# 8️⃣ CORRECTION DES PERMISSIONS DU CACHE (CRITIQUE)
# ==============================================================================
echo "───[ 8️⃣  RÉTABLISSEMENT DES PERMISSIONS (CRITIQUE) ]────────────"
sleep 1

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
# ... (le reste de votre script ici) ...


# ==============================================================================
# 🔍 CHECK FINAL EXTENSION STATUS (Version corrigée avec l'astérisque)
# ==============================================================================
echo ""
echo "───[ 🔍  VÉRIFICATION FINALE DU STATUT DE L'EXTENSION ]──────────────────────────────"
sleep 1

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
