<?php
/**
 * @package    bastien59960/reactions
 * @version    1.0.2
 * @author     Bastien (bastien59960) <bastien@debucquoi.com>
 * @copyright  (c) 2026 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : migrations/release_1_0_2.php
 * Rôle : Ajout colonne reaction_email_sent à phpbb3_post_reactions.
 *
 * Valeurs de reaction_email_sent :
 *   0 = email pas encore envoyé (pending, cron pas encore passé)
 *   1 = email effectivement expédié
 *   2 = email ignoré (pas d'email, préférence désactivée, self-réaction, etc.)
 *   3 = email échoué (exception ou send() === false)
 */

namespace bastien59960\reactions\migrations;

if (!defined('IN_PHPBB'))
{
	exit;
}

class release_1_0_2 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\bastien59960\reactions\migrations\release_1_0_1'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists(
			$this->table_prefix . 'post_reactions',
			'reaction_email_sent'
		);
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'post_reactions' => [
					'reaction_email_sent' => ['BOOL', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'post_reactions' => [
					'reaction_email_sent',
				],
			],
		];
	}
}
