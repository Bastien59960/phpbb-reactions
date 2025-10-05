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
 * - Ordre des dépendances du constructeur aligné sur base::__construct (db, language, user, auth...).
 * - Ajout de language et notifications_table (7e arg requis par parent, évite "too few arguments").
 * - Appel parent::__construct avec 7 args exacts (db en 1er).
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
use phpbb\language\language;  // AJOUT : Import pour language (requis par base)

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

	/** @var language AJOUT : Propriété pour language (gérée par parent) */
	protected $language;

	/** @var string AJOUT : Propriété pour table notifications (gérée par parent) */
	protected $notifications_table;

	protected $phpbb_root_path;
	protected $php_ext;

	/**
	 * Constructeur — injection des dépendances phpBB alignée sur base::__construct.
	 */
	public function __construct(
		driver_interface $db,                    // 1er : db (requis par parent en premier)
		language $language,                      // 2e : language (requis par parent)
		user $user,                              // 3e : user
		auth $auth,                              // 4e : auth
		config $config,                          // 5e : config (extra pour enfant)
		user_loader $user_loader,                // 6e : user_loader (extra)
		helper $helper,                          // 7e : helper (extra)
		request_interface $request,              // 8e : request (extra)
		template $template,                      // 9e : template (extra)
		$phpbb_root_path,                        // 10e : root_path
		$php_ext,                                // 11e : php_ext
		$notifications_table                     // AJOUT : 12e : notifications_table (7e pour parent)
	) {
		// CORRECTION : Appel parent::__construct avec l'ordre EXACT des 7 args attendus par base
		// (db, language, user, auth, root_path, php_ext, notifications_table)
		parent::__construct(
			$db,
			$language,
			$user,
			$auth,
			$phpbb_root_path,
			$php_ext,
			$notifications_table
		);

		// On assigne les propriétés nécessaires pour notre classe (cette partie était déjà bonne)
		$this->user = $user;
		$this->auth = $auth;
		$this->db = $db;
		$this->language = $language;             // AJOUT : Assignation pour language
		$this->config = $config;
		$this->user_loader = $user_loader;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->notifications_table = $notifications_table;  // AJOUT : Assignation pour table

		// AJOUT : Debug basique (retirez en prod)
		if (defined('DEBUG') && DEBUG) {
			error_log('[Reactions Notification] Constructeur OK - DB driver: ' . get_class($db) . ', Table: ' . $notifications_table);
		}

		// AJOUT : Vérification basique pour éviter des crashes futurs (optionnel)
		if (!$db instanceof \phpbb\db\driver\driver_interface) {
			throw new \InvalidArgumentException('DB driver invalide injecté dans Reaction notification.');
		}
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
	 * (Méthode requise par type_interface — évite l’erreur fatale 500)
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
		return 'bastien59960/reactions/notification/reaction_notification';
	}
}