<?php
/**
 * Migration v_1_0_2 : Ajout des colonnes pour notifications de réactions
 * 
 * Ajoute 'notification_type_name' et 'reaction_emoji' à phpbb_notifications.
 * Nécessaire pour supporter les notifications de réactions (type 'notification.reaction').
 * 
 * @package bastien59960.reactions
 */

namespace bastien59960\reactions\migrations;

class v_1_0_2 extends \phpbb\db\migration\migration
{
	/**
	 * Vérifie si la migration est déjà appliquée (via config flag).
	 */
	public function effectively_installed()
	{
		return isset($this->config['reactions_notifications_migration_done']);
	}

	/**
	 * Dépendances : Suit v_1_0_1 (comme les précédentes).
	 */
	public static function depends_on()
	{
		return array('bastien59960\reactions\migrations\v_1_0_1');
	}

	/**
	 * Ajoute les colonnes à phpbb_notifications.
	 */
	public function update_schema()
	{
		return array(
			'add_columns'   => array(
				$this->table_prefix . 'notifications' => array(
					'notification_type_name'    => array('VCHAR:255', ''),  // Standard pour type (fixe SQL [1054])
					'reaction_emoji'            => array('VCHAR_UNI:10', ''),  // Custom pour emoji
				),
			),
		);
	}

	/**
	 * Supprime les colonnes ajoutées (rollback ; drop seulement custom).
	 */
	public function revert_schema()
	{
		return array(
			'drop_columns'  => array(
				$this->table_prefix . 'notifications' => array(
					'reaction_emoji',  // Ne drop pas notification_type_name (standard)
				),
			),
		);
	}

	/**
	 * Ajoute un flag config pour marquer la migration comme faite.
	 */
	public function update_data()
	{
		return array(
			array('config.add', array('reactions_notifications_migration_done', 1)),
		);
	}

	/**
	 * Supprime le flag config (rollback).
	 */
	public function revert_data()
	{
		return array(
			array('config.remove', array('reactions_notifications_migration_done')),
		);
	}
}
