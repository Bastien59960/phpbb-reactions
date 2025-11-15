#!/bin/bash
# Script de diagnostic pour les tâches cron de l'extension Reactions

FORUM_ROOT="/home/bastien/www/forum"

echo "═══════════════════════════════════════════════════════════════"
echo "🔍 DIAGNOSTIC COMPLET DES TÂCHES CRON"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# 1. Vérifier que les fichiers de classe existent
echo "📁 Vérification des fichiers de classe..."
echo "─────────────────────────────────────────────────────────────"

if [ -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php" ]; then
    echo "✅ test_task.php existe"
else
    echo "❌ test_task.php MANQUANT"
fi

if [ -f "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php" ]; then
    echo "✅ notification_task.php existe"
else
    echo "❌ notification_task.php MANQUANT"
fi

echo ""

# 2. Vérifier la syntaxe PHP des fichiers
echo "🔧 Vérification de la syntaxe PHP..."
echo "─────────────────────────────────────────────────────────────"

php -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/test_task.php"
php -l "$FORUM_ROOT/ext/bastien59960/reactions/cron/notification_task.php"

echo ""

# 3. Vérifier le fichier services.yml
echo "📋 Vérification de services.yml..."
echo "─────────────────────────────────────────────────────────────"

echo "Recherche des déclarations de cron dans services.yml:"
grep -A 5 "cron.task.bastien59960" "$FORUM_ROOT/ext/bastien59960/reactions/config/services.yml" || echo "❌ Aucune déclaration trouvée!"

echo ""

# 4. Tester le chargement du conteneur de services
echo "🔌 Test du chargement du conteneur de services..."
echo "─────────────────────────────────────────────────────────────"

php "$FORUM_ROOT/bin/phpbbcli.php" debug:container --show-arguments bastien59960 2>&1

echo ""

# 5. Lister toutes les tâches cron disponibles
echo "📝 Liste de TOUTES les tâches cron enregistrées..."
echo "─────────────────────────────────────────────────────────────"

php "$FORUM_ROOT/bin/phpbbcli.php" cron:list -vvv 2>&1

echo ""

# 6. Vérifier les fichiers de langue
echo "🌍 Vérification des fichiers de langue..."
echo "─────────────────────────────────────────────────────────────"

if [ -f "$FORUM_ROOT/ext/bastien59960/reactions/language/fr/common.php" ]; then
    echo "✅ common.php existe"
    echo "Recherche des clés TASK_:"
    grep "TASK_BASTIEN" "$FORUM_ROOT/ext/bastien59960/reactions/language/fr/common.php"
else
    echo "❌ common.php MANQUANT"
fi

echo ""

# 7. Vérifier le cache
echo "💾 État du cache..."
echo "─────────────────────────────────────────────────────────────"

if [ -d "$FORUM_ROOT/cache/production" ]; then
    echo "Fichiers dans cache/production:"
    ls -lh "$FORUM_ROOT/cache/production" | head -10
else
    echo "⚠️  Dossier cache/production n'existe pas"
fi

echo ""

# 8. Tester manuellement l'instanciation des classes
echo "🧪 Test d'instanciation des classes cron..."
echo "─────────────────────────────────────────────────────────────"

php <<'PHPTEST'
<?php
define('IN_PHPBB', true);
$phpbb_root_path = '/home/bastien/www/forum/';
$phpEx = 'php';

require_once($phpbb_root_path . 'common.' . $phpEx);

echo "Container chargé\n";

try {
    $test_cron = $phpbb_container->get('cron.task.bastien59960.reactions.test');
    echo "✅ test_task instancié: " . get_class($test_cron) . "\n";
    echo "   Nom: " . $test_cron->get_name() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur test_task: " . $e->getMessage() . "\n";
}

try {
    $notif_cron = $phpbb_container->get('cron.task.bastien59960.reactions.notification');
    echo "✅ notification_task instancié: " . get_class($notif_cron) . "\n";
    echo "   Nom: " . $notif_cron->get_name() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur notification_task: " . $e->getMessage() . "\n";
}
PHPTEST

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅ DIAGNOSTIC TERMINÉ"
echo "═══════════════════════════════════════════════════════════════"
