<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
    /**
    * Check if the migration is effectively installed
    *
    * @return bool True if this migration is installed, False if this migration is not installed
    */
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_max_per_post']);
    }

    /**
    * Assign migration file dependencies for this migration
    *
    * @return array Array of migration files
    */
    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    /**
    * Add the configuration options used by this extension
    *
    * @return array Array of configuration options
    */
    public function update_data()
    {
        return array(
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('bastien59960_reactions_enabled', 1)),
        );
    }

    /**
    * Remove the configuration options used by this extension
    *
    * @return array Array of configuration options
    */
    public function revert_data()
    {
        return array(
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
        );
    }
}