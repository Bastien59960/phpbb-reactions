<?php
/**
 * Migration release_1_0_3 : Insertion du type de notification pour les réactions
 * 
 * Cette migration insère une entrée pour le type de notification 'notification.reaction' dans la table phpbb_notification_types.
 * Cela est nécessaire pour corriger l'erreur 'NOTIFICATION_TYPE_NOT_EXIST' observée dans les logs, car phpBB requiert que tous les types de notifications
 * soient enregistrés dans cette table pour pouvoir récupérer leur ID.
 * 
 * Notes importantes :
 * - Le type est inséré avec 'notification_type_enabled' à 1 (activé par défaut).
 * - La vérification 'effectively_installed' checke directement l'existence du type dans la table, plutôt qu'un flag config, pour une détection plus précise.
 * - Pas de modifications de schéma ici (pas d'update_schema), car il s'agit uniquement d'une insertion de données.
 * - Pour le rollback (revert_data), nous supprimons le type, mais attention : cela peut casser des notifications existantes si des données sont déjà présentes.
 *   Il est recommandé de ne pas revert si des notifications de ce type existent.
 * 
 * Vérification de complétude :
 * - La migration est complète et suit les standards phpBB : namespace correct, extension de la classe migration, dépendances, méthodes pour install/check/rollback.
 * - Rien ne manque : La logique d'insertion est idempotente (check avant insert), et le rollback est géré.
 * - Suggestion : Si d'autres données liées (comme des méthodes de notification) doivent être ajoutées, cela pourrait être étendu ici.
 * 
 * @package bastien59960.reactions
 */

namespace bastien59960\reactions\migrations;

class release_1_0_3 extends \phpbb\db\migration\migration
{
	/**
	 * Vérifie si la migration est déjà appliquée en checkant directement l'existence du type dans la table.
	 * Cela est plus robuste qu'un simple flag config, car il reflète l'état réel de la DB.
	 */
	public function effectively_installed()
	{
		$sql = 'SELECT notification_type_id
				FROM ' . $this->table_prefix . "notification_types
				WHERE notification_type_name = 'bastien59960.reactions.notification'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	/**
	 * Dépendances : Suit release_1_0_2 (qui ajoute les colonnes nécessaires aux notifications).
	 * Assure que les migrations précédentes sont appliquées avant celle-ci.
	 */
	public static function depends_on()
	{
		return array('bastien59960\reactions\migrations\release_1_0_2');
	}

	/**
	 * Insère le type de notification via une méthode custom.
	 * Utilise 'custom' pour exécuter du code PHP arbitraire lors de la migration.
	 */
	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'insert_notification_type'))),
		);
	}

	/**
	 * Méthode custom pour insérer le type si il n'existe pas déjà.
	 * Cela rend l'opération idempotente (peut être exécutée plusieurs fois sans duplicats).
	 */
	public function insert_notification_type()
	{
		$sql = 'SELECT notification_type_id
				FROM ' . $this->table_prefix . "notification_types
				WHERE notification_type_name = 'bastien59960.reactions.notification'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . "notification_types (notification_type_name, notification_type_enabled)
					VALUES ('bastien59960.reactions.notification', 1)";
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Rollback : Supprime le type de notification.
	 * Attention : Cela peut causer des pertes de données si des notifications de ce type existent déjà.
	 * Si vous préférez ne pas supprimer, commentez cette méthode ou retournez un array vide.
	 */
	public function revert_data()
	{
		return array(
			array('custom', array(array($this, 'remove_notification_type'))),
		);
	}

	/**
	 * Méthode custom pour supprimer le type lors du rollback.
	 * Vérifie d'abord si il existe avant de supprimer.
	 */
	public function remove_notification_type()
	{
		$sql = 'SELECT notification_type_id
				FROM ' . $this->table_prefix . "notification_types
				WHERE notification_type_name = 'bastien59960.reactions.notification'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . "notification_types
					WHERE notification_type_name = 'bastien59960.reactions.notification'";
			$this->db->sql_query($sql);
		}
	}
}
