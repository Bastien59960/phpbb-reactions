<?php
/**
 * Module UCP : Configuration des notifications de réactions
 * 
 * Permet à l'utilisateur d'activer/désactiver :
 * - Les notifications internes (cloche)
 * - Le cron d'envoi d'e-mails de réactions
 */

namespace bastien59960\reactions\ucp;

class reactions_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $template, $user, $request, $config, $phpbb_container;

		$user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');

		$controller = $phpbb_container->get('bastien59960.reactions.controller.ucp_reactions');
		$controller->handle($id, $mode);
	}
}
