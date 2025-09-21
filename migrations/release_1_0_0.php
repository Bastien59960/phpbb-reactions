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
    /**
    * Check if the migration is effectively installed (if the table exists)
    *
    * @return bool True if this migration is installed, False if this migration is not installed
    */
    public function effectively_installed()
    {
        return isset($this->db_tools) && $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions');
    }

    /**
    * Assign migration file dependencies for this migration
    *
    * @return array Array of migration files
    */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v330');
    }

    /**
    * Add the table schemas used by this extension
    *
    * @return array Array of table schema
    */
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
                        'reaction_unicode' => array('VCHAR:10', ''),
                        'reaction_time'    => array('TIMESTAMP', 0),
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        'post_reaction_idx' => array('INDEX', array('post_id', 'user_id')),
                        'topic_idx'         => array('INDEX', 'topic_id'),
                        'user_idx'          => array('INDEX', 'user_id'),
                    ),
                ),
            ),
        );
    }

    /**
    * Drop the schemas used by this extension
    *
    * @return array Array of table schema
    */
    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
        );
    }
}
