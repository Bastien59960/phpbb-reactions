<?php
/**
 * @package    bastien59960/reactions
 * @version    1.0.3
 * @author     Bastien (bastien59960) <bastien@debucquoi.com>
 * @copyright  (c) 2026 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : migrations/release_1_0_3.php
 * Rôle : Supprime le module UCP séparé des réactions.
 */

namespace bastien59960\reactions\migrations;

if (!defined('IN_PHPBB'))
{
	exit;
}

class release_1_0_3 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\bastien59960\reactions\migrations\release_1_0_1'];
	}

	public function effectively_installed()
	{
		return !$this->has_separate_ucp_module();
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'remove_separate_ucp_module']]],
		];
	}

	public function revert_data()
	{
		return [];
	}

	public function remove_separate_ucp_module()
	{
		$sql = 'DELETE FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'ucp'
				AND (
					module_langname = 'UCP_REACTIONS_SETTINGS'
					OR module_basename LIKE '%bastien59960%reactions%ucp%'
				)";
		$this->db->sql_query($sql);

		return true;
	}

	protected function has_separate_ucp_module()
	{
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'ucp'
				AND (
					module_langname = 'UCP_REACTIONS_SETTINGS'
					OR module_basename LIKE '%bastien59960%reactions%ucp%'
				)";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}
}
