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
                        'topic_id'         => array('UINT', 0),
                        'user_id'          => array('UINT', 0),
                        'reaction_emoji'   => array('VCHAR:20', ''),
                        'reaction_time'    => array('UINT:11', 0),
                        'reaction_notified'=> array('BOOL', 0),
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        'post_id'           => array('INDEX', 'post_id'),
                        'topic_id'          => array('INDEX', 'topic_id'),
                        'user_id'           => array('INDEX', 'user_id'),
                        'post_notified_idx' => array('INDEX', array('post_id', 'reaction_notified')),
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
        $sql = "ALTER TABLE {$table_name}
                MODIFY `reaction_emoji` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL";
        $this->db->sql_query($sql);
    }
}
