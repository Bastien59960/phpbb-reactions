<?php
/**
 * ============================================================================
 * Type de notification : Reaction
 * ============================================================================
 *
 * Gère les notifications lorsqu’un utilisateur réagit à un message avec un emoji.
 * Affiche une alerte dans le centre de notifications de phpBB (icône de cloche).
 *
 * Ce fichier :
 * - Déclare le type de notification "reaction"
 * - Insère automatiquement ce type dans la table phpbb_notification_types s’il n’existe pas
 * - Définit le comportement d’affichage et d’enregistrement des notifications
 *
 * © 2025 Bastien59960 — Licence GPL v2
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
	exit;
}

use phpbb\notification\type\base;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\template\template;
use phpbb\controller\helper;
use phpbb\user_loader;
use phpbb\request\request_interface;
use phpbb\language\language;

class reaction extends base
{
	/** @var driver_interface */
	protected $db;

	/** @var language */
	protected $language;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/** @var request_interface */
	protected $request;

	/** @var template */
	protected $template;

	/** @var user_loader */
	protected $user_loader;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $notifications_table;

	/**
	 * Constructeur : injection des dépendances phpBB.
	 *
	 * L’ordre doit correspondre à celui du parent `phpbb\notification\type\base`.
	 */
	public function __construct(
		driver_interface $db,
		language $language,
		user $user,
		auth $auth,
		config $config,
		user_loader $user_loader,
		helper $helper,
		request_interface $request,
		template $template,
		$phpbb_root_path,
		$php_ext,
		$notifications_table
	) {
		parent::__construct(
			$db,
			$language,
			$user,
			$auth,
			$phpbb_root_path,
			$php_ext,
			$notifications_table
		);

		$this->db = $db;
		$this->language = $language;
		$this->user = $user;
		$this->auth = $auth;
		$this->config = $config;
		$this->user_loader = $user_loader;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->notifications_table = $notifications_table;

		// ================================================================
		// SECTION DEBUG (active uniquement si DEBUG activé dans phpBB)
		// ================================================================
		if (defined('DEBUG') && DEBUG) {
			error_log('[Reactions Notification] Constructeur OK - DB driver: ' . get_class($db) . ', Table: ' . $notifications_table);
		}

		// Vérifie que le type "reaction" existe dans phpbb_notification_types.
		// S’il n’existe pas, on l’ajoute automatiquement.
		$type_name = 'notification.type.' . $this->get_type();
		$types_table = 'phpbb_notification_types';

		$col_check_sql = 'SHOW COLUMNS FROM ' . $types_table . " LIKE 'notification_type_name'";
		$result = $this->db->sql_query($col_check_sql);
		$col_exists = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($col_exists) {
			$sql = 'SELECT notification_type_id
					FROM ' . $types_table . '
					WHERE notification_type_name = \'' . $this->db->sql_escape($type_name) . '\'
					LIMIT 1';
			$result = $this->db->sql_query($sql);
			$exists = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if (!$exists) {
				$proto_data = [
					'notification_type_name' => $type_name,
				];
				$this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $proto_data));

				if (defined('DEBUG') && DEBUG) {
					error_log('[Reactions Notification] Prototype ajouté dans ' . $types_table . ' pour : ' . $type_name);
				}
			}
		} elseif (defined('DEBUG') && DEBUG) {
			error_log('[Reactions Notification] Colonne notification_type_name manquante dans ' . $types_table);
		}
	}

	// =========================================================================
	// SECTION : Méthodes d’identification du type
	// =========================================================================

	/** Nom unique du type de notification */
	public function get_type()
	{
		return 'reaction';
	}

	/** Active ou non ce type de notification */
	public function is_available()
	{
		return true;
	}

	// =========================================================================
	// SECTION : Gestion des éléments notifiés
	// =========================================================================

	public static function get_item_id($data)
	{
		return (int) ($data['post_id'] ?? 0);
	}

	public static function get_item_parent_id($data)
	{
		return (int) ($data['topic_id'] ?? 0);
	}

	public static function get_item_author_id($data)
	{
		return (int) ($data['post_author'] ?? 0);
	}

	// =========================================================================
	// SECTION : Liens et affichage
	// =========================================================================

	public function get_url()
	{
		$post_id = $this->get_item_id($this->data ?? []);
		if (!$post_id) {
			return '';
		}

		return append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'p=' . $post_id) . '#p' . $post_id;
	}

	public static function get_item_url($data)
	{
		global $phpbb_root_path, $phpEx;
		$post_id = self::get_item_id($data);
		return append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $post_id) . '#p' . $post_id;
	}

	public function get_title()
	{
		// Doit correspondre à une clé de langue dans language/*/reactions.php
		// Exemple : 'NOTIFICATION_TYPE_REACTION' => '%s a réagi à votre message avec %s'
		return 'NOTIFICATION_TYPE_REACTION';
	}

	public function get_language_file()
	{
		return 'notification/reaction_notification';
	}

	// =========================================================================
	// SECTION : Détermination des destinataires
	// =========================================================================

	public function find_users_for_notification($type_data, $options = [])
	{
		$users = [];
		$post_author = (int) ($type_data['post_author'] ?? 0);
		$reacter = (int) ($type_data['reacter'] ?? 0);

		// Notifie l’auteur du message si ce n’est pas lui qui a réagi
		if ($post_author && $post_author !== $reacter) {
			$users[] = $post_author;
		}

		return $users;
	}

	public function users_to_query()
	{
		return [];
	}

	// =========================================================================
	// SECTION : Email (désactivé ici)
	// =========================================================================

	public function get_email_template()
	{
		return false;
	}

	public function get_email_template_variables()
	{
		return [
			'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
			'EMOJI'            => $this->data['emoji'] ?? '',
			'POST_ID'          => $this->get_item_id($this->data ?? []),
		];
	}

	// =========================================================================
	// SECTION : Rendu utilisateur
	// =========================================================================

	public function get_title_for_user($user_id, $lang)
	{
		return [
			$this->get_title(),
			[
				$this->data['reacter_username'] ?? '',
				$this->data['emoji'] ?? '',
			],
		];
	}

	public function get_render_data($user_id)
	{
		return [
			'emoji'             => $this->data['emoji'] ?? '',
			'reacter_username'  => $this->data['reacter_username'] ?? '',
			'post_id'           => $this->get_item_id($this->data ?? []),
		];
	}

	// =========================================================================
	// SECTION : Enregistrement en base
	// =========================================================================

	/**
	 * Déclare les colonnes personnalisées pour la table des notifications.
	 * Ici, on stocke l’emoji associé à la réaction.
	 */
	public function get_insert_sql()
	{
		return [
			'reaction_emoji' => ['VCHAR_UNI', 10],
		];
	}

	/**
	 * Construit les données d’insertion pour chaque notification.
	 */
	public function create_insert_array($type_data, $pre_create_data = [])
	{
		$insert_array = parent::create_insert_array($type_data, $pre_create_data);
		$insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
		return $insert_array;
	}
}
