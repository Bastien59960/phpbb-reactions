<?php
/**
 * Migration pour corriger le titre du module ACP
 *
 * @package bastien59960/reactions
 * @license GPL-2.0-only
 */

namespace bastien59960\reactions\migrations;

class release_1_0_5 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\bastien59960\reactions\migrations\release_1_0_4'];
	}

	public function effectively_installed()
	{
		// Vérifier si le module a déjà le bon titre
		$sql = 'SELECT module_langname FROM ' . $this->table_prefix . "modules
				WHERE module_langname = 'ACP_REACTIONS_TITLE'
				AND module_class = 'acp'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'fix_acp_module_title']]],
		];
	}

	public function fix_acp_module_title()
	{
		// Mettre à jour le titre du module parent
		$sql = 'UPDATE ' . $this->table_prefix . "modules
				SET module_langname = 'ACP_REACTIONS_TITLE'
				WHERE module_langname = 'ACP_REACTIONS_SETTINGS'
				AND module_class = 'acp'
				AND parent_id = (
					SELECT module_id FROM (
						SELECT module_id FROM " . $this->table_prefix . "modules
						WHERE module_langname = 'ACP_CAT_DOT_MODS' AND module_class = 'acp'
					) AS parent_module
				)";
		$this->db->sql_query($sql);

		return true;
	}
}
