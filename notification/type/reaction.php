<?php
/**
 * ============================================================================
 * Notification Type: Reaction
 * ============================================================================
 *
 * Fournit la logique de notification pour les réactions (icône de cloche).
 *
 * - Implémente toutes les méthodes requises par phpbb\notification\type\type_interface.
 * - Identifie le type de notification (get_type).
 * - Détermine quels utilisateurs doivent être notifiés (find_users_for_notification).
 * - Définit les variables pour le rendu ou les e-mails (facultatif).
 *
 * Correction :
 * - Signature conforme à type_interface (notamment find_users_for_notification).
 * - Ajout des méthodes statiques obligatoires.
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
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;

class reaction extends base
{
	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var template */
	protected $template;

	/** @var config */
	protected $config;

	/** @var request_interface */
	protected $request;

	/**
	 * Constructeur — injection des dépendances phpBB.
	 */
	public function __construct(
		user $user,
		auth $auth,
		template $template,
		config $config,
		request_interface $request
	) {
		$this->user = $user;
		$this->auth = $auth;
		$this->template = $template;
		$this->config = $config;
		$this->request = $request;
	}

	/**
	 * Identifiant unique de ce type de notification.
	 */
	public function get_type()
	{
		return 'reaction';
	}

	/**
	 * Indique si ce type de notification est disponible.
	 */
	public function is_available()
	{
		return true;
	}

	/**
	 * Retourne l'ID de l'élément concerné (ici, le post_id).
	 */
	public static function get_item_id($data)
	{
		return (int) ($data['post_id'] ?? 0);
	}

	/**
	 * Retourne l'ID du parent de l'élément (topic_id).
	 */
	public static function get_item_parent_id($data)
	{
		return (int) ($data['topic_id'] ?? 0);
	}

	/**
	 * Retourne l'ID de l'auteur de l'élément.
	 */
	public static function get_item_author_id($data)
	{
		return (int) ($data['post_author'] ?? 0);
	}

	/**
	 * Retourne l'URL associée à la notification (redirige vers le message).
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
	 * Retourne le titre de la notification (clé de langue).
	 */
	public function get_title()
	{
		// Exemple de clé à définir dans language/*/reactions.php :
		// $lang = ['NOTIFICATION_TYPE_REACTION' => '%s a réagi à votre message avec %s'];
		return 'NOTIFICATION_TYPE_REACTION';
	}

	/**
	 * Détermine les utilisateurs à notifier.
	 *
	 * @param array $type_data Données du message ou de la réaction.
	 * @param array $options   Options additionnelles.
	 * @return array           Liste des IDs utilisateurs à notifier.
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
	 * Aucune requête utilisateur additionnelle nécessaire ici.
	 */
	public function users_to_query()
	{
		return [];
	}

	/**
	 * Désactive les e-mails pour ce type de notification.
	 */
	public function get_email_template()
	{
		return false;
	}

	/**
	 * Variables disponibles dans un éventuel e-mail (non utilisé ici).
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
	 * Titre traduit de la notification, avec variables injectées.
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
	 * Données à afficher dans la liste des notifications de l’utilisateur.
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
	 * Nom du fichier de langue utilisé par ce type.
	 */
	public function get_language_file()
	{
		return 'bastien59960/reactions';
	}
}
