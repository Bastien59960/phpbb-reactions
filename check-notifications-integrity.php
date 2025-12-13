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

// Analyser les arguments de la ligne de commande pour l'option --fix
$options = getopt('', ['fix']);
$fix_mode = isset($options['fix']);

/**
 * Gère les erreurs et termine le script.
 * @param string $message Le message d'erreur.
 * @param \PDOException|null $exception L'exception capturée.
 */
function handle_error($message, \PDOException $exception = null) {
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

// --- Lecture des informations de connexion depuis les variables d'environnement ---
$dbhost = getenv('DB_HOST');
$dbuser = getenv('DB_USER');
$dbpasswd = getenv('MYSQL_PASSWORD');
$dbname = getenv('DB_NAME');
$table_prefix = getenv('TABLE_PREFIX');

// Vérifier si les variables de config sont chargées
if (!$dbhost || !$dbname || !$dbuser || !$table_prefix) {
    handle_error("Les variables d'environnement de la base de données (DB_HOST, DB_NAME, etc.) ne sont pas définies.");
}

// Connexion directe à la base de données via PDO
try {
    $dsn = "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpasswd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    handle_error("Échec de la connexion à la base de données.", $e);
}


// =============================================================================
// 1. VÉRIFICATION DES PRÉFÉRENCES UTILISATEUR
// =============================================================================

echo "=================================================================\n";
echo "=== DÉBUT DE LA VÉRIFICATION DES PRÉFÉRENCES DE NOTIFICATION ===\n";
echo "=================================================================\n\n";

$reaction_notification_type = 'bastien59960.reactions.notification.type.reaction';
$digest_notification_type = 'bastien59960.reactions.notification.type.reaction_email_digest';

try {
    // Constantes phpBB USER_IGNORE = 2, ANONYMOUS = 1
    $sql = "SELECT user_id, username
            FROM {$table_prefix}users
            WHERE user_type <> 2
            AND user_id <> 1
            ORDER BY user_id";
    $stmt = $pdo->query($sql);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
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
        try {
            $sql = "SELECT notify
                    FROM {$table_prefix}user_notifications
                    WHERE user_id = :user_id
                    AND item_type = :item_type
                    AND method = :method
                    AND item_id = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id, 'item_type' => $pref['type'], 'method' => $pref['method']]);
            $notification_setting = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            handle_error("Erreur lors de la vérification de la préférence '{$pref['type']}' pour l'utilisateur ID {$user_id}.", $e);
        }
        $method_short_name = str_replace('notification.method.', '', $pref['method']);

        if (!$notification_setting) {
            echo "    " . COLOR_RED . "[ERREUR] Préférence manquante pour la méthode '" . $method_short_name . "'" . COLOR_NC . "\n";
            $has_error = true;

            // Si le mode --fix est activé, on insère la préférence manquante
            if ($fix_mode) {
                try {
                    $sql_insert = "INSERT INTO {$table_prefix}user_notifications (item_type, item_id, user_id, method, notify)
                                   VALUES (:item_type, 0, :user_id, :method, 1)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([
                        'item_type' => $pref['type'],
                        'user_id'   => $user_id,
                        'method'    => $pref['method'],
                    ]);
                    echo "    " . COLOR_GREEN . "[FIX] Préférence pour '" . $method_short_name . "' créée et activée." . COLOR_NC . "\n";
                } catch (\PDOException $e) {
                    echo "    " . COLOR_RED . "[ERREUR FIX] Impossible de créer la préférence manquante : " . $e->getMessage() . COLOR_NC . "\n";
                }
            }

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

try {
    $sql = "SELECT DISTINCT un.user_id
            FROM {$table_prefix}user_notifications AS un
            LEFT JOIN {$table_prefix}users AS u ON un.user_id = u.user_id
            WHERE u.user_id IS NULL";
    $stmt = $pdo->query($sql);
    $orphan_user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $orphan_user_ids = array_map('intval', $orphan_user_ids);
} catch (\PDOException $e) {
    handle_error('Impossible de vérifier les notifications orphelines.', $e);
}

if (empty($orphan_user_ids)) {
    echo COLOR_GREEN . "[OK] Aucune préférence de notification orpheline n'a été trouvée." . COLOR_NC . "\n";
} else {
    echo COLOR_RED . "[ERREUR] " . count($orphan_user_ids) . " ID d'utilisateur(s) orphelin(s) trouvé(s) :" . COLOR_NC . "\n\n";
    foreach ($orphan_user_ids as $user_id) {
        echo "    - ID utilisateur orphelin : " . $user_id . "\n";
    }

    // Construire la clause IN pour la requête de suppression
    $in_clause = implode(',', array_fill(0, count($orphan_user_ids), '?'));
    $delete_query = "DELETE FROM {$table_prefix}user_notifications WHERE user_id IN ({$in_clause});";

    if ($fix_mode) {
        echo "\n" . COLOR_YELLOW . "Mode --fix activé. Suppression des notifications orphelines..." . COLOR_NC . "\n";
        try {
            $stmt = $pdo->prepare($delete_query);
            $stmt->execute($orphan_user_ids);
            $deleted_count = $stmt->rowCount();
            echo COLOR_GREEN . "    [OK] " . $deleted_count . " préférence(s) de notification orpheline(s) supprimée(s)." . COLOR_NC . "\n";
        } catch (\PDOException $e) {
            handle_error('Impossible de supprimer les notifications orphelines.', $e);
        }
    } else {
        // Afficher la requête suggérée avec les IDs pour un copier-coller facile
        $ids_string = implode(', ', $orphan_user_ids);
        $readable_delete_query = "DELETE FROM {$table_prefix}user_notifications WHERE user_id IN ({$ids_string});";
        echo "\n" . COLOR_YELLOW . "     Requête de suppression suggérée (ou utilisez l'option --fix pour une suppression automatique) :\n     " . $readable_delete_query . COLOR_NC . "\n";
    }
}

echo "\n=================================================================\n";
echo "=== FIN DE LA VÉRIFICATION DES NOTIFICATIONS ORPHELINES      ===\n";
echo "=================================================================\n\n";