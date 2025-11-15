<?php
/**
 * Fichier : ucp/main_module.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est le **point d'entrée principal** pour le module UCP.
 * Il est responsable de :
 *   1. Déclarer la catégorie et le module à phpBB.
 *   2. Charger le fichier de langue pour que le titre du module s'affiche correctement dans le menu.
 *   3. Définir les modes disponibles pour ce module.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\ucp;

class main_module
{
	/**
	 * Méthode principale qui définit la structure du module.
	 *
	 * @param string $id   Identifiant du module.
	 * @param string $mode Mode du module.
	 */
	public function main($id, $mode)
	{
		global $user;

		// Charger le fichier de langue principal de l'extension.
		// C'est cette ligne qui permet de traduire 'UCP_REACTIONS_TITLE' dans le menu.
		$user->add_lang_ext('bastien59960/reactions', 'common');

		// Définir le nom du template et le titre de la page.
		$this->tpl_name = 'ucp_reactions';
		$this->page_title = 'UCP_REACTIONS_TITLE';

		// Définir les modes disponibles. Le nom 'settings' DOIT correspondre à celui de ucp/reactions_module_info.php
		$this->modes = [
			'settings'	=> ['title' => 'UCP_REACTIONS_SETTINGS', 'auth' => 'ext_bastien59960/reactions', 'cat' => ['UCP_PREFS']],
		];

		// Le reste de la logique est géré par le fichier `ucp/reactions_module.php`
	}
}