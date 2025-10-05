<?php

namespace bastien59960\reactions\migrations;

class add_reaction_notification_type extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        $sql = 'SELECT notification_type_id
                FROM ' . $this->table_prefix . "notification_types
                WHERE notification_type_name = 'notification.reaction'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row !== false;
    }

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'insert_notification_type'))),
        );
    }

    public function insert_notification_type()
    {
        $sql = 'INSERT INTO ' . $this->table_prefix . "notification_types (notification_type_name, notification_type_enabled)
                VALUES ('notification.reaction', 1)";
        $this->db->sql_query($sql);
    }
}
