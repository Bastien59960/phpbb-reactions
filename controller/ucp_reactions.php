<?php
/**
 * Fichier : ucp_reactions.php
 * Chemin : bastien59960/reactions/controller/ucp_reactions.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/controller/ucp_reactions.php
 *
 * Rôle :
 * Ce contrôleur gère la logique de la page de préférences des réactions dans le
 * panneau de contrôle de l'utilisateur (UCP). Il permet à chaque utilisateur de
 * choisir s'il souhaite recevoir des notifications (cloche et/ou e-mail).
 *
 * Informations reçues :
 * - Via le formulaire POST : les nouvelles valeurs pour `user_reactions_notify` et `user_reactions_email`.
 *
 * Il est appelé par le module `ucp/reactions_module.php`.
 *
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use phpbb\user;
use phpbb\request\request;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class ucp_reactions
{
	protected $user;
	protected $db;
	protected $request;
	protected $template;
	protected $table_prefix;

	public function __construct(user $user, driver_interface $db, request $request, template $template, $table_prefix)
	{
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->table_prefix = $table_prefix;
	}

	public function handle($id, $mode)
	{
		$user_id = (int) $this->user->data['user_id'];

		// Valeurs actuelles depuis la table users
		$sql = 'SELECT user_reactions_notify, user_reactions_email
				FROM ' . $this->table_prefix . 'users
				WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$current_notify = (bool) $row['user_reactions_notify'];
		$current_email = (bool) $row['user_reactions_email'];

		$submit = $this->request->is_set_post('submit');

		if ($submit)
		{
			$new_notify = $this->request->variable('user_reactions_notify', 0);
			$new_email = $this->request->variable('user_reactions_email', 0);

			$sql = 'UPDATE ' . $this->table_prefix . 'users
					SET user_reactions_notify = ' . (int) $new_notify . ',
					    user_reactions_email = ' . (int) $new_email . '
					WHERE user_id = ' . $user_id;
			$this->db->sql_query($sql);

			trigger_error($this->user->lang('UCP_REACTIONS_SAVED') . adm_back_link($this->u_action));
		}

		// Assignation au template
		$this->template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'UCP_REACTIONS_NOTIFY'	=> $current_notify,
			'UCP_REACTIONS_EMAIL'	=> $current_email,
		));

		$this->user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');
		page_header($this->user->lang('UCP_REACTIONS_TITLE'));
		$this->template->set_filenames(array('body' => '@bastien59960_reactions/ucp_reactions.html'));
		page_footer();
	}
}
