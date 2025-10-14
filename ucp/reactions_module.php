<?php
/**
 * Fichier : ucp/reactions_module.php â€” bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * RÃ´le :
 * Ce fichier est le **point d'entrÃ©e** du module "PrÃ©fÃ©rences des rÃ©actions"
 * dans le Panneau de ContrÃ´le Utilisateur (UCP). Son rÃ´le est de :
 *   1. DÃ©clarer le module Ã  phpBB pour qu'il apparaisse dans le menu de l'UCP.
 *   2. Charger les fichiers de langue nÃ©cessaires pour ce module.
 *   3. DÃ©lÃ©guer toute la logique mÃ©tier au contrÃ´leur dÃ©diÃ© (`controller/ucp_reactions.php`).
 *
 * Ce fichier ne contient volontairement aucune logique complexe.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\ucp;

/**
 * Classe du module UCP pour les rÃ©actions.
 * phpBB instancie cette classe lorsqu'un utilisateur accÃ¨de Ã  ce module.
 */
class reactions_module
{
	/** @var string URL de base pour l'action du formulaire. */
	public $u_action;

	/**
	 * MÃ©thode principale exÃ©cutÃ©e par phpBB.
	 *
	 * @param string $id   Identifiant du module (fourni par phpBB).
	 * @param string $mode Mode du module (fourni par phpBB).
	 */
	public function main($id, $mode)
	{
		global $template, $user, $request, $config, $phpbb_container;

		// Charger le fichier de langue spÃ©cifique Ã  ce module UCP
		$user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');

		// RÃ©cupÃ©rer le contrÃ´leur depuis le conteneur de services et lui passer la main.
		$controller = $phpbb_container->get('bastien59960.reactions.controller.ucp_reactions');
		$controller->handle($id, $mode);
	}
}
