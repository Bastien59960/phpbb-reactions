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
            // Étape 1: S'assurer que la table et la connexion sont prêtes pour les emojis.
            ['custom', [[$this, 'prepare_database_for_emojis']]],
            // Étape 2: Importer les données de l'ancienne extension (si elle existe).
            ['custom', [[$this, 'import_from_old_extension']]],
        ];
    }

    /**
     * Prépare la base de données pour les emojis.
     * Force la connexion en utf8mb4 et s'assure que la colonne est correctement configurée.
     */
    public function prepare_database_for_emojis()
    {
        // Force la connexion en utf8mb4 pour cette session, crucial pour les insertions d'emojis.
        $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name} MODIFY `reaction_emoji` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL";
        $this->sql_query($sql);
    }

    /**
     * Importe les réactions depuis l'ancienne extension "crizzo/postreactions".
     * Cette logique est déplacée ici pour s'exécuter dans la même session que prepare_database_for_emojis.
     */
    public function import_from_old_extension()
    {
        // Vérifier si l'ancienne table existe
        if ($this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions_pro'))
        {
            $sql = 'SELECT post_id, topic_id, user_id, reaction, added
                    FROM ' . $this->table_prefix . 'post_reactions_pro';
            $result = $this->db->sql_query($sql);

            $reactions_to_insert = [];
            while ($row = $this->db->sql_fetchrow($result))
            {
                // Convertir l'ancien format de réaction en emoji si nécessaire
                $emoji = $this->convert_to_emoji($row['reaction']);

                $reactions_to_insert[] = [
                    'post_id'           => (int) $row['post_id'],
                    'topic_id'          => (int) $row['topic_id'],
                    'user_id'           => (int) $row['user_id'],
                    'reaction_emoji'    => $emoji,
                    'reaction_time'     => (int) $row['added'],
                    'reaction_notified' => 0, // Marquer comme non notifié pour le moment
                ];
            }
            $this->db->sql_freeresult($result);

            if (!empty($reactions_to_insert))
            {
                // La connexion est déjà en utf8mb4 grâce à prepare_database_for_emojis()
                $this->db->sql_multi_insert($this->table_prefix . 'post_reactions', $reactions_to_insert);
            }
        }
    }

    /**
     * Convertit les anciennes réactions textuelles en emojis.
     * @param string $reaction_text
     * @return string
     */
    private function convert_to_emoji($reaction_text)
    {
        // Ceci est un exemple, vous devrez l'adapter à la logique de votre ancienne extension.
        // Si l'ancienne extension stockait déjà des emojis, cette fonction peut simplement retourner le texte.
        $map = [
            'like' => '👍',
            'love' => '❤️',
            'haha' => '😂',
            'wow'  => '😮',
            'sad'  => '😢',
            'angry'=> '😡',
        ];

        return $map[strtolower($reaction_text)] ?? $reaction_text;
    }
}