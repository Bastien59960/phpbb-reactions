<?php
/**
 * Fichier : ucp/reactions_module.php — bastien59960/reactions/ucp/reactions_module.php
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Module UCP (User Control Panel) pour la configuration des notifications de réactions.
 *
 * Ce fichier permet d'intégrer la gestion des préférences de notifications de réactions dans le panneau de contrôle utilisateur de phpBB.
 * Il délègue la logique métier au contrôleur UCP dédié.
 *
 * Points clés de la logique métier :
 *   - Chargement du contrôleur UCP des réactions
 *   - Passage des paramètres nécessaires (id, mode)
 *   - Intégration avec le système de langue et de templates phpBB
 *
 * Ce module est le point d'entrée pour l'utilisateur souhaitant configurer ses préférences de notifications de réactions dans le panneau utilisateur.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
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
