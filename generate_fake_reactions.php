<?php
/**
 * Script : generate_fake_reactions.php
 * Rôle : Génère 50 fausses réactions aléatoires réparties entre les utilisateurs pour tester le Cron
 * Usage : php generate_fake_reactions.php
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : __DIR__ . '/../../../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require($phpbb_root_path . 'common.' . $phpEx);

// Initialisation de la session (nécessaire pour l'accès DB)
$user->session_begin();
$auth->acl($user->data);
$user->setup();

echo "\n--- 🧪 GÉNÉRATEUR MASSIF DE RÉACTIONS (x50) ---\n";

// 1. Récupérer les utilisateurs (Humains uniquement, pas de bots ni d'invités)
$sql = 'SELECT user_id, username, user_email 
        FROM ' . USERS_TABLE . ' 
        WHERE user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ') 
        AND user_id <> ' . ANONYMOUS;
$result = $db->sql_query($sql);
$users = [];
while ($row = $db->sql_fetchrow($result)) {
    $users[] = $row;
}
$db->sql_freeresult($result);

if (count($users) < 2) {
    die("❌ Pas assez d'utilisateurs pour simuler des interactions (il faut au moins 2 humains).\n");
}

// 2. Récupérer les 100 derniers posts
$sql = 'SELECT post_id, poster_id, post_subject 
        FROM ' . POSTS_TABLE . ' 
        ORDER BY post_id DESC';
$result = $db->sql_query_limit($sql, 100);
$posts = [];
while ($row = $db->sql_fetchrow($result)) {
    $posts[] = $row;
}
$db->sql_freeresult($result);

if (empty($posts)) {
    die("❌ Aucun post trouvé sur le forum.\n");
}

// Liste d'emojis variés
$emojis = ['👍', '❤️', '😂', '😮', '😢', '😡', '🎉', '👀', '🚀', '🔥', '🧪', '🤖'];
$table_reactions = $table_prefix . 'post_reactions';
$count_inserted = 0;
$target = 50;

echo "ℹ️  Génération en cours...\n";

// 3. Boucle de génération
for ($i = 0; $i < $target; $i++) {
    // Choisir un post au hasard
    $post = $posts[array_rand($posts)];
    
    // Choisir un réacteur au hasard (qui n'est PAS l'auteur du post)
    do {
        $reactor = $users[array_rand($users)];
    } while ($reactor['user_id'] == $post['poster_id']);

    // Vérifier si la réaction existe déjà
    $sql_check = "SELECT 1 FROM $table_reactions 
                  WHERE post_id = " . (int)$post['post_id'] . " 
                  AND user_id = " . (int)$reactor['user_id'];
    $res = $db->sql_query($sql_check);
    $exists = $db->sql_fetchrow($res);
    $db->sql_freeresult($res);

    if (!$exists) {
        // Insertion
        $emoji = $emojis[array_rand($emojis)];
        $sql_ary = [
            'post_id'           => $post['post_id'],
            'user_id'           => $reactor['user_id'],
            'reaction_emoji'    => $emoji,
            'reaction_time'     => time() - rand(60, 3600), // Il y a entre 1 min et 1h
            'reaction_notified' => 0, // 0 = Non notifié (Cible du Cron)
        ];
        
        $sql = 'INSERT INTO ' . $table_reactions . ' ' . $db->sql_build_array('INSERT', $sql_ary);
        $db->sql_query($sql);
        
        // Activer l'email pour l'auteur du post (pour être sûr que le cron envoie quelque chose)
        $sql_pref = 'UPDATE ' . USERS_TABLE . ' SET user_reactions_cron_email = 1 WHERE user_id = ' . (int)$post['poster_id'];
        $db->sql_query($sql_pref);

        $count_inserted++;
        echo "  [+] " . $reactor['username'] . " " . $emoji . " -> Post #" . $post['post_id'] . "\n";
    }
}

// 4. Reset du timer cron pour exécution immédiate
$config->set('bastien59960_reactions_cron_last_run', 0);

echo "\n✅ Terminé ! $count_inserted nouvelles réactions insérées.\n";
echo "⏱  Timer Cron réinitialisé à 0.\n";
echo "👉 Vous pouvez lancer : php bin/phpbbcli.php cron:run\n";