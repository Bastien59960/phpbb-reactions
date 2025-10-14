<?php
/**
 * Migration : release_1_0_4
 * Ajoute les nouvelles options de configuration pour l'interface des réactions.
 */

namespace bastien59960\reactions\migrations;

class release_1_0_4 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\bastien59960\reactions\migrations\release_1_0_3',
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['bastien59960_reactions_post_emoji_size', 24]],
			['config.add', ['bastien59960_reactions_picker_width', 320]],
			['config.add', ['bastien59960_reactions_picker_height', 280]],
			['config.add', ['bastien59960_reactions_picker_show_categories', 1]],
			['config.add', ['bastien59960_reactions_picker_show_search', 1]],
			['config.add', ['bastien59960_reactions_picker_use_json', 1]],
			['config.add', ['bastien59960_reactions_picker_emoji_size', 24]],
			['config.add', ['bastien59960_reactions_sync_interval', 5000]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bastien59960_reactions_post_emoji_size']],
			['config.remove', ['bastien59960_reactions_picker_width']],
			['config.remove', ['bastien59960_reactions_picker_height']],
			['config.remove', ['bastien59960_reactions_picker_show_categories']],
			['config.remove', ['bastien59960_reactions_picker_show_search']],
			['config.remove', ['bastien59960_reactions_picker_use_json']],
			['config.remove', ['bastien59960_reactions_picker_emoji_size']],
			['config.remove', ['bastien59960_reactions_sync_interval']],
		];
	}
}
