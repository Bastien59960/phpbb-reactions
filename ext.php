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
        return '1.0.1';
    }

    /**
     * Enable step for notifications
     */
    public function enable_step($old_state)
    {
        if ($old_state === false)
        {
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->enable_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::enable_step($old_state);
    }

    /**
     * Disable step for notifications
     */
    public function disable_step($old_state)
    {
        if ($old_state === false)
        {
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->disable_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::disable_step($old_state);
    }

    /**
     * Purge step for notifications
     */
    public function purge_step($old_state)
    {
        if ($old_state === false)
        {
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->purge_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::purge_step($old_state);
    }
}
