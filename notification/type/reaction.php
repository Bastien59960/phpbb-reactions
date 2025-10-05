<?php
/**
 * ============================================================================
 * Notification Type: Reaction
 * ============================================================================
 *
 * Gère la logique des notifications de réactions (icône de cloche).
 *
 * - Implémente toutes les méthodes requises par phpbb\notification\type\type_interface.
 * - Identifie le type de notification (get_type).
 * - Détermine les utilisateurs à notifier (find_users_for_notification).
 * - Définit les variables pour le rendu et les e-mails (facultatif).
 *
 * Correction :
 * - Ordre des dépendances du constructeur corrigé (user, auth, db, config...).
 * - Ajout de get_url() pour éviter l’erreur fatale 500.
 * - Signatures conformes à type_interface.
 *
 * © 2025 Bastien59960 — Licence GPL v2.
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

class reaction extends base
{
	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var driver_interface */
	protected $db;

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

	protected $phpbb_root_path;
	protected $php_ext;

	/**
	 * Constructeur — injection des dépendances phpBB dans le bon ordre.
	 */
	public function __construct(
		user $user,
		auth $auth,
		driver_interface $db,
		config $config,
		user_loader $user_loader,
		helper $helper,
		request_interface $request,
		template $template,
		$phpbb_root_path,
		$php_ext
	) {
		parent::__construct(
			$user,
			$auth,
			$db,
			$config,
			$user_loader,
			$helper,
			$request,
			$template,
			$phpbb_root_path,
			$php_ext
		);

		$this->user = $user;
		$this->auth = $auth;
		$this->db = $db;
		$this->config = $config;
		$this->user_loader = $user_loader;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Identifiant unique du type de notification.
	 */
	public function get_type()
	{
		return 'reaction';
	}

	/**
	 * Indique si le type de notification est activé.
	 */
	public function is_available()
	{
		return true;
	}

	/**
	 * Retourne l'ID de l'élément concerné (post_id).
	 */
	public static function get_item_id($data)
	{
		return (int) ($data['post_id'] ?? 0);
	}

	/**
	 * Retourne l'ID du parent (topic_id).
	 */
	public static function get_item_parent_id($data)
	{
		return (int) ($data['topic_id'] ?? 0);
	}

	/**
	 * Retourne l'ID de l'auteur du post concerné.
	 */
	public static function get_item_author_id($data)
	{
		return (int) ($data['post_author'] ?? 0);
	}

	/**
	 * Retourne l'URL complète vers le post concerné.
	 * (Méthode requise par type_interface — évite l'erreur 500)
	 */
	public function get_url()
	{
		$post_id = $this->get_item_id($this->data ?? []);
		if (!$post_id) {
			return '';
		}

		return append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'p=' . $post_id) . '#p' . $post_id;
	}

	/**
	 * Version statique — utilisée lors de la création de la notification.
	 */
	public static function get_item_url($data)
	{
		$post_id = self::get_item_id($data);
		if (!$post_id) {
			return '';
		}

		global $phpbb_root_path, $phpEx;
		return append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $post_id) . '#p' . $post_id;
	}

	/**
	 * Clé de langue du titre de notification.
	 */
	public function get_title()
	{
		// Exemple dans language/*/reactions.php :
		// 'NOTIFICATION_TYPE_REACTION' => '%s a réagi à votre message avec %s'
		return 'NOTIFICATION_TYPE_REACTION';
	}

	/**
	 * Détermine les utilisateurs à notifier.
	 */
	public function find_users_for_notification($type_data, $options = [])
	{
		$users = [];

		$post_author = (int) ($type_data['post_author'] ?? 0);
		$reacter = (int) ($type_data['reacter'] ?? 0);

		// Notifie l’auteur du post si ce n’est pas lui qui a réagi.
		if ($post_author && $post_author !== $reacter) {
			$users[] = $post_author;
		}

		return $users;
	}

	/**
	 * Aucune requête utilisateur additionnelle nécessaire.
	 */
	public function users_to_query()
	{
		return [];
	}

	/**
	 * Désactive l'envoi d'e-mails pour ce type.
	 */
	public function get_email_template()
	{
		return false;
	}

	/**
	 * Variables disponibles dans un e-mail (si jamais activé).
	 */
	public function get_email_template_variables()
	{
		return [
			'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
			'EMOJI'            => $this->data['emoji'] ?? '',
			'POST_ID'          => $this->get_item_id($this->data ?? []),
		];
	}

	/**
	 * Titre traduit de la notification avec variables injectées.
	 */
	public function get_title_for_user($user_id, $lang)
	{
		$lang_key = $this->get_title();
		$lang_vars = [
			$this->data['reacter_username'] ?? '',
			$this->data['emoji'] ?? '',
		];

		return [$lang_key, $lang_vars];
	}

	/**
	 * Données affichées dans la liste des notifications.
	 */
	public function get_render_data($user_id)
	{
		return [
			'emoji'             => $this->data['emoji'] ?? '',
			'reacter_username'  => $this->data['reacter_username'] ?? '',
			'post_id'           => $this->get_item_id($this->data ?? []),
		];
	}

	/**
	 * Nom du fichier de langue utilisé.
	 */
	public function get_language_file()
	{
		return 'bastien59960/reactions';
	}
}
