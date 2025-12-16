<?php
/**
 * Fichier : release_1_0_0.php
 * Chemin : bastien59960/reactions/migrations/release_1_0_0.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Migration initiale pour la version 1.0.0 de l'extension Reactions.
 * Cette migration est responsable de la création de la table principale
 * `post_reactions` nécessaire au fonctionnement de l'extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

if (!defined('IN_PHPBB')) {
    exit;
}

class release_1_0_0 extends \phpbb\db\migration\migration
{
    /**
     * Dépendances de cette migration.
     * @return array
     */
    public function depends_on()
    {
        return ['\phpbb\db\migration\data\v310\alpha1'];
    }

    /**
     * Applique les changements de la base de données.
     * @return array
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'post_reactions' => [
                    'COLUMNS' => [
                        'reaction_id'       => ['UINT', null, 'auto_increment'],
                        'post_id'           => ['UINT', 0],
                        'topic_id'          => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'reaction_emoji'    => ['VCHAR:255', ''],
                        'reaction_time'     => ['TIMESTAMP', 0],
                        'reaction_notified' => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => [
                        'post_id' => ['INDEX', 'post_id'],
                        'user_id' => ['INDEX', 'user_id'],
                        'topic_id' => ['INDEX', 'topic_id'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Applique les modifications de données.
     * @return array
     */
    public function update_data()
    {
        return [
            // Assure que la connexion à la base de données utilise utf8mb4
            // et que la colonne des emojis est correctement configurée.
            ['custom', [[$this, 'prepare_database_for_emojis']]],
        ];
    }

    public function prepare_database_for_emojis()
    {
        // Force la connexion en utf8mb4 pour cette session, crucial pour les insertions d'emojis.
        $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name} MODIFY `reaction_emoji` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL";
        $this->sql_query($sql);
    }
}