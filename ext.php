<?php
namespace bastien59\reactions;

use phpbb\db\driver\driver_interface;

class ext extends \phpbb\extension\base
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /**
     * Constructor with dependency injection for the database service
     */
    public function __construct(\phpbb\db\driver\driver_interface $db)
    {
        $this->db = $db;
    }

    /**
     * This method is called when the extension is installed
     */
    public function enable_step($old_state)
    {
        switch ($old_state)
        {
            case '':
                $this->create_database_table();
                return 'table_created';
            case 'table_created':
                return false;
        }
    }

    /**
     * Crée la table phpbb_post_reactions si elle n'existe pas
     * avec gestion des erreurs
     */
    protected function create_database_table()
    {
        $table_sql = "CREATE TABLE IF NOT EXISTS phpbb_post_reactions (
            reaction_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id INT UNSIGNED NOT NULL,
            topic_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            reaction_unicode VARCHAR(10) NOT NULL,
            reaction_time DATETIME NOT NULL,
            PRIMARY KEY (reaction_id),
            INDEX (post_id),
            INDEX (topic_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $this->db->sql_query($table_sql);
        } catch (\Exception $e) {
            trigger_error('Failed to create phpbb_post_reactions table: ' . $e->getMessage(), E_USER_ERROR);
        }
    }
}
