<?php
/**
 * Fichier : check-notifications-integrity.php
 * Chemin : bastien59960/reactions/check-notifications-integrity.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Script CLI pour vérifier l'intégrité des préférences de notification.
 * 1. Vérifie que chaque utilisateur a les bonnes préférences par défaut pour l'extension.
 * 2. Vérifie qu'il n'existe pas de préférences de notification pour des utilisateurs supprimés (orphelines).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

define('IN_PHPBB', true);
define('IN_CRON', true); // Évite les problèmes de session en mode CLI

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Inclure les fichiers de base de phpBB
require($phpbb_root_path . 'common.' . $phpEx);

// Initialiser la session utilisateur
$user->session_begin();
$auth->acl($user->data);
$user->setup();

/**
 * Gère les erreurs et termine le script.
 * @param string $message Le message d'erreur.
 * @param \Exception|null $exception L'exception capturée.
 */
function handle_error($message, \Exception $exception = null) {
    fwrite(STDERR, COLOR_RED . 'ERREUR FATALE: ' . $message . COLOR_NC . "\n");
    if ($exception) {
        fwrite(STDERR, 'Détails: ' . $exception->getMessage() . "\n");
    }
    exit(1);
}

// Constantes pour les couleurs dans la console
const COLOR_GREEN = "\033[0;32m";
const COLOR_YELLOW = "\033[1;33m";
const COLOR_RED = "\033[0;31m";
const COLOR_NC = "\033[0m"; // Pas de couleur


// =============================================================================
// 1. VÉRIFICATION DES PRÉFÉRENCES UTILISATEUR
// =============================================================================

echo "=================================================================\n";
echo "=== DÉBUT DE LA VÉRIFICATION DES PRÉFÉRENCES DE NOTIFICATION ===\n";
echo "=================================================================\n\n";

$reaction_notification_type = 'bastien59960.reactions.notification.type.reaction';
$digest_notification_type = 'bastien59960.reactions.notification.type.reaction_email_digest';

$sql = 'SELECT user_id, username
    FROM ' . USERS_TABLE . '
    WHERE user_type <> ' . USER_IGNORE . '
    AND user_id <> ' . ANONYMOUS . '
    ORDER BY user_id';

try {
    $result = $db->sql_query($sql);
    $all_users = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
} catch (\phpbb\db\driver\exception $e) {
    handle_error('Impossible de récupérer la liste des utilisateurs.', $e);
}

$total_users = count($all_users);
$errors_found = 0;
echo "Vérification des préférences pour " . $total_users . " utilisateurs...\n\n";

foreach ($all_users as $i => $user_data) {
    $user_id = (int) $user_data['user_id'];
    $username = $user_data['username'];

    echo "--> Utilisateur " . ($i + 1) . "/" . $total_users . ": " . $username . " (ID: " . $user_id . ")\n";

    $expected_prefs = [
        ['type' => $reaction_notification_type, 'method' => 'notification.method.board'],
        ['type' => $digest_notification_type, 'method' => 'notification.method.email'],
    ];

    $has_error = false;

    foreach ($expected_prefs as $pref) {
        $sql = 'SELECT notify
            FROM ' . USER_NOTIFICATIONS_TABLE . '
            WHERE user_id = ' . $user_id . '
                AND item_type = "' . $db->sql_escape($pref['type']) . '"
                AND method = "' . $db->sql_escape($pref['method']) . '"
                AND item_id = 0';

        try {
            $result_pref = $db->sql_query($sql);
            $notification_setting = $db->sql_fetchrow($result_pref);
            $db->sql_freeresult($result_pref);
        } catch (\phpbb\db\driver\exception $e) {
            handle_error("Erreur lors de la vérification de la préférence '{$pref['type']}' pour l'utilisateur ID {$user_id}.", $e);
        }
        $method_short_name = str_replace('notification.method.', '', $pref['method']);

        if (!$notification_setting) {
            echo "    " . COLOR_RED . "[ERREUR] Préférence manquante pour la méthode '" . $method_short_name . "'" . COLOR_NC . "\n";
            $has_error = true;
        } elseif ((int) $notification_setting['notify'] !== 1) {
            echo "    " . COLOR_RED . "[ERREUR] Préférence pour '" . $method_short_name . "' n'est pas activée (valeur : " . $notification_setting['notify'] . ")" . COLOR_NC . "\n";
            $has_error = true;
        } else {
            echo "    " . COLOR_GREEN . "[OK] Préférence pour la méthode '" . $method_short_name . "' est correcte." . COLOR_NC . "\n";
        }
    }

    if ($has_error) {
        $errors_found++;
    }
    echo "\n";
}

echo "=================================================================\n";
if ($errors_found > 0) {
    echo "=== VÉRIFICATION TERMINÉE : " . $errors_found . " utilisateur(s) avec des erreurs. ===\n";
} else {
    echo "=== VÉRIFICATION TERMINÉE : Toutes les préférences sont correctes ! ===\n";
}
echo "=================================================================\n\n";

// =============================================================================
// 2. VÉRIFICATION DES NOTIFICATIONS ORPHELINES
// =============================================================================

echo "=================================================================\n";
echo "=== DÉBUT VÉRIFICATION DES NOTIFICATIONS ORPHELINES          ===\n";
echo "=================================================================\n\n";

$sql = 'SELECT DISTINCT un.user_id
    FROM ' . USER_NOTIFICATIONS_TABLE . ' AS un
    LEFT JOIN ' . USERS_TABLE . ' AS u ON un.user_id = u.user_id
    WHERE u.user_id IS NULL';

try {
    $result = $db->sql_query($sql);
    $orphan_user_ids = array_map('intval', array_column($db->sql_fetchrowset($result), 'user_id'));
    $db->sql_freeresult($result);
} catch (\phpbb\db\driver\exception $e) {
    handle_error('Impossible de vérifier les notifications orphelines.', $e);
}

if (empty($orphan_user_ids)) {
    echo COLOR_GREEN . "[OK] Aucune préférence de notification orpheline n'a été trouvée." . COLOR_NC . "\n";
} else {
    echo COLOR_RED . "[ERREUR] " . count($orphan_user_ids) . " ID d'utilisateur(s) orphelin(s) trouvé(s) :" . COLOR_NC . "\n\n";
    foreach ($orphan_user_ids as $user_id) {
        echo "    - ID utilisateur orphelin : " . $user_id . "\n";
    }
    $delete_query = 'DELETE FROM ' . USER_NOTIFICATIONS_TABLE . ' WHERE ' . $db->sql_in_set('user_id', $orphan_user_ids) . ';';
    echo "\n" . COLOR_YELLOW . "     Requête de suppression suggérée :\n     " . $delete_query . COLOR_NC . "\n";
}

echo "\n=================================================================\n";
echo "=== FIN DE LA VÉRIFICATION DES NOTIFICATIONS ORPHELINES      ===\n";
echo "=================================================================\n\n";