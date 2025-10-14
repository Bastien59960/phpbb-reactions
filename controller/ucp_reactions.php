<?php
/**
 * Fichier : controller/ucp_reactions.php â€” bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * RÃ´le :
 * Ce contrÃ´leur gÃ¨re la logique mÃ©tier de la page de prÃ©fÃ©rences des rÃ©actions
 * dans le Panneau de ContrÃ´le Utilisateur (UCP). Il est responsable de :
 *   - Afficher le formulaire avec les prÃ©fÃ©rences actuelles de l'utilisateur.
 *   - Traiter la soumission du formulaire et mettre Ã  jour la base de donnÃ©es.
 *
 * Informations reÃ§ues :
 * - Via le formulaire POST : les nouvelles valeurs pour `user_reactions_notify` et `user_reactions_email`.
 *
 * Il est appelÃ© par le module `ucp/reactions_module.php`.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use phpbb\user;
use phpbb\request\request;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

/**
 * ContrÃ´leur pour la page de prÃ©fÃ©rences des rÃ©actions dans l'UCP.
 */
class ucp_reactions
{
	/** @var \phpbb\user Instance de l'utilisateur courant. */
	protected $user;
	/** @var \phpbb\db\driver\driver_interface Instance de la base de donnÃ©es. */
	protected $db;
	/** @var \phpbb\request\request Instance de la requÃªte HTTP. */
	protected $request;
	/** @var \phpbb\template\template Instance du moteur de templates. */
	protected $template;
	/** @var string PrÃ©fixe des tables de la base de donnÃ©es. */
	protected $table_prefix;
	/** @var string URL de base pour l'action du formulaire. */
	public $u_action;

	/**
	 * Constructeur du contrÃ´leur.
	 *
	 * @param user             $user         Service utilisateur de phpBB.
	 * @param driver_interface $db           Service de base de donnÃ©es de phpBB.
	 * @param request          $request      Service de requÃªte de phpBB.
	 * @param template         $template     Service de template de phpBB.
	 * @param string           $table_prefix PrÃ©fixe des tables.
	 */
	public function __construct(user $user, driver_interface $db, request $request, template $template, $table_prefix)
	{
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * MÃ©thode principale qui gÃ¨re l'affichage et le traitement de la page.
	 *
	 * @param string $id   Identifiant du module.
	 * @param string $mode Mode du module.
	 */
	public function handle($id, $mode)
	{
		// Charger le fichier de langue spÃ©cifique Ã  ce module UCP
		$this->user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');

		$user_id = (int) $this->user->data['user_id'];

		// RÃ©cupÃ©rer les prÃ©fÃ©rences actuelles de l'utilisateur.
		// L'objet $this->user->data contient dÃ©jÃ  ces informations, pas besoin de requÃªte SQL.
		$current_notify = (bool) ($this->user->data['user_reactions_notify'] ?? 1);
		$current_email = (bool) ($this->user->data['user_reactions_email'] ?? 1);

		// VÃ©rifier si le formulaire a Ã©tÃ© soumis.
		$submit = $this->request->is_set_post('submit');

		if ($submit)
		{
			// RÃ©cupÃ©rer les nouvelles valeurs depuis le formulaire.
			$new_notify = $this->request->variable('user_reactions_notify', 0);
			$new_email = $this->request->variable('user_reactions_email', 0);

			// Mettre Ã  jour la base de donnÃ©es.
			$sql = 'UPDATE ' . $this->table_prefix . 'users
					SET user_reactions_notify = ' . (int) $new_notify . ',
					    user_reactions_email = ' . (int) $new_email . '
					WHERE user_id = ' . $user_id;
			$this->db->sql_query($sql);

			// Afficher un message de succÃ¨s et un lien de retour.
			trigger_error($this->user->lang('UCP_REACTIONS_SAVED') . adm_back_link($this->u_action));
		}

		// Assigner les variables au template pour l'affichage.
		$this->template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'UCP_REACTIONS_NOTIFY'	=> $current_notify,
			'UCP_REACTIONS_EMAIL'	=> $current_email, // Pour le rÃ©sumÃ© par e-mail
			'S_NOTIFY_CHECKED'      => ($current_notify) ? ' checked="checked"' : '',
			'S_EMAIL_CHECKED'       => ($current_email) ? ' checked="checked"' : '',
		));

		// DÃ©finir le titre de la page et le fichier de template Ã  utiliser.
		page_header($this->user->lang('UCP_REACTIONS_TITLE'));
		$this->template->set_filenames(array('body' => '@bastien59960_reactions/ucp_reactions.html'));
		page_footer();
	}
}
