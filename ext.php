<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions;

/**
* Extension base class.
*/
class ext extends \phpbb\extension\base
{
    /**
    * Check whether the extension can be enabled.
    * The current phpBB version should meet or exceed
    * the minimum version required by this extension.
    *
    * Requires phpBB 3.3.0 due to updated extension meta-data.
    *
    * @return bool
    */
    public function is_enableable()
    {
        $config = $this->container->get('config');
        return phpbb_version_compare($config['version'], '3.3.0', '>=');
    }

    /**
    * Return the current version of the extension.
    *
    * This should match the version used in the migration class.
    *
    * @return string
    */
    public function get_version()
    {
        return '1.0.0';
    }
}
