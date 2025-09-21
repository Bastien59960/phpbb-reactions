<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $root_path;
	protected $php_ext;
	protected $user;
	protected $template;
	protected $reaction_url;
	protected $assets_manager;

	public function __construct(
		$root_path,
		$php_ext,
		\phpbb\user $user,
		\phpbb\template\template $template,
		$reaction_url,
		\phpbb\assets\manager $assets_manager
	) {
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->user = $user;
		$this->template = $template;
		$this->reaction_url = $reaction_url;
		$this->assets_manager = $assets_manager;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.page_header_after' => 'add_reactions_script_and_template_vars',
		];
	}

	public function add_reactions_script_and_template_vars($event)
	{
		// On n'inclut le script que si l'utilisateur est connecté et sur une page de sujet.
		if (!$this->user->data['is_registered'] || $this->template->get_template_name() !== 'viewtopic_body.html') {
			return;
		}

		// On passe la variable de template pour l'URL du contrôleur.
		$this->template->assign_vars([
			'U_REACTIONS_HANDLE' => $this->reaction_url,
		]);

		// On inclut le fichier JavaScript de manière correcte.
		$this->assets_manager->add_script('bastien59960\\reactions', 'reactions.js');
	}
}
