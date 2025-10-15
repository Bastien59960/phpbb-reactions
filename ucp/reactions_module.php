<?php
/**
 * Fichier : ucp/reactions_module.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est le **point d'entrée** du module "Préférences des réactions"
 * dans le Panneau de Contrôle Utilisateur (UCP). Son rôle est de :
 *   1. Déclarer le module à phpBB pour qu'il apparaisse dans le menu de l'UCP.
 *   2. Charger les fichiers de langue nécessaires pour ce module.
 *   3. Déléguer toute la logique métier au contrôleur dédié (`controller/ucp_reactions.php`).
 *
 * Ce fichier ne contient volontairement aucune logique complexe.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\ucp;

/**
 * Classe du module UCP pour les réactions.
 * phpBB instancie cette classe lorsqu'un utilisateur accède à ce module.
 */
class reactions_module
{
	/** @var string URL de base pour l'action du formulaire. */
	public $u_action;

	/**
	 * Méthode principale exécutée par phpBB.
	 *
	 * @param string $id   Identifiant du module (fourni par phpBB).
	 * @param string $mode Mode du module (fourni par phpBB).
	 */
	public function main($id, $mode)
	{
		global $template, $user, $request, $config, $phpbb_container;

		// Charger le fichier de langue spécifique à ce module UCP
		$user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');

		// Récupérer le contrôleur depuis le conteneur de services et lui passer la main.
		$controller = $phpbb_container->get('bastien59960.reactions.controller.ucp_reactions');
		$controller->handle($id, $mode);
	}
}
