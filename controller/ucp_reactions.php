<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/controller/ucp_reactions.php
 * Auteur : Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce contrôleur gère la logique métier de la page de préférences des réactions
 * dans le Panneau de Contrôle Utilisateur (UCP). Il est responsable de :
 * - Afficher le formulaire avec les préférences actuelles de l'utilisateur.
 * - Traiter la soumission du formulaire et mettre à jour la base de données.
 *
 * Il est appelé par le module UCP correspondant.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use phpbb\user;
use phpbb\request\request;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;
use phpbb\controller\helper as controller_helper;

/**
 * Contrôleur pour la page de préférences des réactions dans l'UCP.
 */
class ucp_reactions
{
	/** @var \phpbb\user Instance de l'utilisateur courant. */
	protected $user;
	/** @var \phpbb\db\driver\driver_interface Instance de la base de données. */
	protected $db;
	/** @var \phpbb\request\request Instance de la requête HTTP. */
	protected $request;
	/** @var \phpbb\template\template Instance du moteur de templates. */
	protected $template;
	/** @var string Préfixe des tables de la base de données. */
	protected $table_prefix;
	/** @var string URL de base pour l'action du formulaire. */
	public $u_action;

	/**
	 * Constructeur du contrôleur.
	 *
	 * @param user             $user         Service utilisateur de phpBB.
	 * @param driver_interface $db           Service de base de données de phpBB.
	 * @param request          $request      Service de requête de phpBB.
	 * @param template         $template     Service de template de phpBB.
	 * @param string           $table_prefix Préfixe des tables.
	 * @param controller_helper $controller_helper Helper de contrôleur.
	 */
	public function __construct(
		user $user,                         // 1. @user
		driver_interface $db,               // 2. @dbal.conn
		request $request,                   // 3. @request
		template $template,                 // 4. @template
		$table_prefix,                      // 5. %core.table_prefix%
		controller_helper $controller_helper // 6. @controller.helper
	) {
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->table_prefix = $table_prefix;
		// La propriété u_action est définie par le module, mais on a besoin du helper pour la construire.
		// On peut le stocker si nécessaire, ou simplement l'accepter pour la compatibilité.
		// $this->controller_helper = $controller_helper;
	}

	/**
	 * Définit l'URL de base pour l'action du formulaire.
	 *
	 * @param string $u_action L'URL de l'action.
	 */
	public function set_u_action($u_action)
	{
		$this->u_action = $u_action;
	}

	/**
	 * Méthode principale qui gère l'affichage et le traitement de la page.
	 *
	 * @param string $id   Identifiant du module.
	 * @param string $mode Mode du module.
	 */
	public function handle($id, $mode)
	{
		$user_id = (int) $this->user->data['user_id'];

		// Récupérer les préférences actuelles de l'utilisateur.
		// L'objet $this->user->data contient déjà ces informations, pas besoin de requête SQL.
		$current_notify = (bool) ($this->user->data['user_reactions_notify'] ?? 1);
		$current_email = (bool) ($this->user->data['user_reactions_cron_email'] ?? 1);

		// Vérifier si le formulaire a été soumis.
		$submit = $this->request->is_set_post('submit');

		if ($submit)
		{
			// Récupérer les nouvelles valeurs depuis le formulaire.
			$new_notify = $this->request->variable('ucp_reactions_notify', 0);
			$new_email = $this->request->variable('ucp_reactions_cron_email', 0);

			// Mettre à jour la base de données.
			$sql = 'UPDATE ' . $this->table_prefix . 'users
					SET user_reactions_notify = ' . (int) $new_notify . ',
					    user_reactions_cron_email = ' . (int) $new_email . '
					WHERE user_id = ' . $user_id;
			$this->db->sql_query($sql);

			// Afficher un message de succès et un lien de retour.
			trigger_error($this->user->lang('UCP_REACTIONS_SAVED') . adm_back_link($this->u_action));
		}

		// Assigner les variables au template pour l'affichage.
		$this->template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'UCP_REACTIONS_NOTIFY'	=> $current_notify,
			'UCP_REACTIONS_CRON_EMAIL'	=> $current_email, // Pour le résumé par e-mail
			'S_NOTIFY_CHECKED'      => ($current_notify) ? ' checked="checked"' : '',
			'S_EMAIL_CHECKED'       => ($current_email) ? ' checked="checked"' : '',
		));

		// Définir le titre de la page et le fichier de template à utiliser.
		page_header($this->user->lang('UCP_REACTIONS_TITLE'));
		$this->template->set_filenames(array('body' => '@bastien59960_reactions/ucp_reactions.html'));
		page_footer();
	}
}
