<?php
/**
 * Script PHP pour générer des notifications de test avec le bon format de sérialisation
 * 
 * Ce script utilise serialize() de PHP pour garantir que le format est exactement
 * le même que celui utilisé par phpBB.
 * 
 * Usage: php generate-test-notifications.php
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Inclure le bootstrap de phpBB
require($phpbb_root_path . 'common.' . $phpEx);

// Démarrer la session
$user->session_begin();
$auth->acl($user->data);
$user->setup();

// Récupérer le nombre de notifications à générer depuis l'environnement
$count = (int)(getenv('DEBUG_NOTIF_COUNT') ?: 15);

// Récupérer l'ID du type de notification
$sql = "SELECT notification_type_id 
        FROM {$table_prefix}notification_types 
        WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction' 
        AND notification_type_enabled = 1 
        LIMIT 1";
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if (!$row) {
    die("ERREUR : Type de notification non trouvé\n");
}

$notification_type_id = (int)$row['notification_type_id'];
echo "Type de notification trouvé (ID: $notification_type_id)\n";

// Récupérer des réactions valides pour créer les notifications
$sql = "SELECT 
            r.post_id,
            r.topic_id,
            p.poster_id,
            p.forum_id,
            r.user_id as reacter_id,
            u.username as reacter_name,
            r.reaction_emoji
        FROM {$table_prefix}post_reactions r
        JOIN {$table_prefix}posts p ON r.post_id = p.post_id
        JOIN {$table_prefix}users u ON r.user_id = u.user_id
        WHERE p.poster_id != r.user_id
        AND r.reaction_emoji IS NOT NULL
        AND r.reaction_emoji != ''
        ORDER BY RAND()
        LIMIT $count";
$result = $db->sql_query($sql);
$reactions = [];
while ($row = $db->sql_fetchrow($result)) {
    $reactions[] = $row;
}
$db->sql_freeresult($result);

if (empty($reactions)) {
    die("ERREUR : Aucune réaction valide trouvée\n");
}

echo "Génération de " . count($reactions) . " notification(s)...\n";

// Créer les notifications avec serialize() de PHP
$inserted = 0;
foreach ($reactions as $reaction) {
    // Construire le tableau de données exactement comme dans controller/ajax.php
    $notification_data = [
        'post_id'        => (int)$reaction['post_id'],
        'topic_id'       => (int)$reaction['topic_id'],
        'forum_id'       => (int)$reaction['forum_id'],
        'poster_id'      => (int)$reaction['poster_id'],
        'reacter_id'     => (int)$reaction['reacter_id'],
        'reacter_name'   => $reaction['reacter_name'],
        'reaction_emoji' => $reaction['reaction_emoji'],
    ];
    
    // Sérialiser avec serialize() de PHP (format exact de phpBB)
    $serialized_data = serialize($notification_data);
    
    // Insérer la notification
    $sql_ary = [
        'notification_type_id' => $notification_type_id,
        'item_id'             => (int)$reaction['post_id'],
        'item_parent_id'      => (int)$reaction['topic_id'],
        'user_id'             => (int)$reaction['poster_id'],
        'notification_read'   => 0,
        'notification_time'   => time(),
        'notification_data'   => $serialized_data,
    ];
    
    $sql = 'INSERT INTO ' . $table_prefix . 'notifications ' . $db->sql_build_array('INSERT', $sql_ary);
    $db->sql_query($sql);
    $inserted++;
    
    echo "  ✅ Notif pour le post #{$reaction['post_id']} (auteur #{$reaction['poster_id']}) : {$reaction['reaction_emoji']} par {$reaction['reacter_name']}\n";
}

echo "\n✅ $inserted notification(s) créée(s) avec succès.\n";
echo "   Format de sérialisation : PHP serialize() (identique à phpBB)\n";

