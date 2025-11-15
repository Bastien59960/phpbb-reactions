<?php
/**
 * Fichier : reactions_module.php
 * Chemin : bastien59960/reactions/ucp/reactions_module.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est le **point d'entrée d'exécution** pour le module UCP.
 * Lorsque l'utilisateur clique sur le lien "Préférences des réactions", phpBB
 * instancie cette classe. Son unique rôle est de récupérer le contrôleur
 * (`ucp_reactions`) depuis le conteneur de services et de lui déléguer
 * l'intégralité du traitement via sa méthode `handle()`.
 *
 * Il ne contient volontairement aucune logique métier.
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