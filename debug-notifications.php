<?php
/**
 * Script de débogage pour vérifier les notifications en base de données
 */

define('IN_PHPBB', true);
$phpbb_root_path = '/home/bastien/www/forum/';
$phpEx = 'php';

require($phpbb_root_path . 'common.' . $phpEx);

// Démarrer la session
$user->session_begin();
$auth->acl($user->data);
$user->setup();

echo "=== DEBUG DES NOTIFICATIONS ===\n\n";

// Récupérer quelques notifications
$sql = "SELECT * FROM phpbb_notifications
        WHERE notification_type_id = (
            SELECT notification_type_id
            FROM phpbb_notification_types
            WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'
            LIMIT 1
        )
        ORDER BY notification_time DESC
        LIMIT 3";
$result = $db->sql_query($sql);

$count = 0;
while ($row = $db->sql_fetchrow($result)) {
    $count++;
    echo "--- Notification #" . $row['notification_id'] . " ---\n";
    echo "User ID: " . $row['user_id'] . "\n";
    echo "Item ID: " . $row['item_id'] . "\n";
    echo "Time: " . $row['notification_time'] . " (" . date('Y-m-d H:i:s', $row['notification_time']) . ")\n";
    echo "Read: " . ($row['notification_read'] ? 'Yes' : 'No') . "\n";

    // Désérialiser les données
    $data = unserialize($row['notification_data']);
    echo "Data (raw): " . $row['notification_data'] . "\n";
    echo "Data (unserialized): " . print_r($data, true) . "\n";

    // Tester le service de notification
    try {
        $notification_service = $phpbb_container->get('bastien59960.reactions.notification.type.reaction');
        echo "Service loaded: YES\n";
        echo "Service get_type(): " . $notification_service->get_type() . "\n";

        // Charger la notification
        $notification_service->set_notification_data($data);
        $notification_service->set_notification_manager($notification_manager);

        // Tester get_title_for_user
        $title_data = $notification_service->get_title_for_user($row['user_id']);
        echo "Title data: " . print_r($title_data, true) . "\n";

        // Tester si la langue est chargée
        if (isset($title_data[0])) {
            $lang_key = $title_data[0];
            $params = $title_data[1] ?? [];
            echo "Lang key: " . $lang_key . "\n";
            echo "Params: " . print_r($params, true) . "\n";

            // Essayer de formater avec vsprintf
            if (isset($user->lang[$lang_key])) {
                $formatted = vsprintf($user->lang[$lang_key], $params);
                echo "Formatted title: " . $formatted . "\n";
            } else {
                echo "ERROR: Language key '$lang_key' not found in user->lang\n";
                echo "Available keys starting with NOTIFICATION: ";
                foreach ($user->lang as $key => $value) {
                    if (strpos($key, 'NOTIFICATION') === 0) {
                        echo $key . " ";
                    }
                }
                echo "\n";
            }
        }

    } catch (Exception $e) {
        echo "ERROR loading service: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

$db->sql_freeresult($result);

if ($count == 0) {
    echo "No notifications found.\n";
}
