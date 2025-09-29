<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions');
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'post_reactions' => array(
                    'COLUMNS' => array(
                        'reaction_id'      => array('UINT', null, 'auto_increment'),
                        'post_id'          => array('UINT', 0),
                        'user_id'          => array('UINT', 0),
                        'reaction_emoji'   => array('VCHAR:20', ''), // Sera modifié par la suite
                        'reaction_time'    => array('TIMESTAMP', 0, 'DEFAULT CURRENT_TIMESTAMP'),
                        // NOUVELLE COLONNE : Par défaut à 0 (false). Passera à 1 (true) une fois la notif envoyée.
                        'reaction_notified'=> array('BOOL', 0),
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        // NOUVEL INDEX : Pour chercher rapidement les notifications à envoyer.
                        'post_notified_idx' => array('INDEX', array('post_id', 'reaction_notified')),
                        'user_idx'         => array('INDEX', 'user_id'),
                    ),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
        );
    }

    public function update_data()
    {
        return array(
            ['custom', [[$this, 'set_utf8mb4_bin']]],
        );
    }

    public function set_utf8mb4_bin()
    {
        $table_name = $this->table_prefix . 'post_reactions';
        // Cette instruction est idempotente et sûre à ré-exécuter
        $sql = "ALTER TABLE {$table_name}
                MODIFY `reaction_emoji` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL";
        $this->db->sql_query($sql);
    }
}
