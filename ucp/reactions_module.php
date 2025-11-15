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

	/** @var string Nom du template à utiliser */
	public $tpl_name;

	/** @var string Titre de la page */
	public $page_title;

	/**
	 * Méthode principale exécutée par phpBB.
	 *
	 * @param string $id   Identifiant du module (fourni par phpBB).
	 * @param string $mode Mode du module (fourni par phpBB).
	 */
	public function main($id, $mode)
	{
		global $template, $user, $request, $config, $phpbb_container;

		// CORRECTION : Charger TOUS les fichiers de langue nécessaires
		$user->add_lang_ext('bastien59960/reactions', 'common');
		$user->add_lang_ext('bastien59960/reactions', 'ucp_reactions');

		// Définir le template et le titre AVANT de passer au contrôleur
		$this->tpl_name = 'ucp_reactions';
		$this->page_title = 'UCP_REACTIONS_TITLE';

		// Vérifier que le contrôleur existe dans le conteneur
		if (!$phpbb_container->has('bastien59960.reactions.controller.ucp_reactions'))
		{
			trigger_error('UCP_REACTIONS_CONTROLLER_NOT_FOUND');
		}

		// Récupérer le contrôleur depuis le conteneur de services
		$controller = $phpbb_container->get('bastien59960.reactions.controller.ucp_reactions');
		
		// Passer l'URL d'action au contrôleur
		$controller->set_page_url($this->u_action);

		// Déléguer le traitement au contrôleur
		$controller->handle($id, $mode);
	}
}