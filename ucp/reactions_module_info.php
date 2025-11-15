<?php
/**
 * Fichier : reactions_module_info.php
 * Chemin : bastien59960/reactions/ucp/reactions_module_info.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est le **manifeste** du module UCP. Il est lu par phpBB lors de
 * l'installation/activation de l'extension pour savoir comment enregistrer le
 * module "Préférences des réactions" dans le panneau utilisateur. Il définit
 * le nom, le mode, la catégorie et le fichier contrôleur à appeler.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\ucp;

class reactions_module_info
{
	public function module()
	{
		return [
			'filename'	=> '\bastien59960\reactions\ucp\reactions_module',
			'title'		=> 'UCP_REACTIONS_TITLE', // Clé de langue pour le titre principal du module
			'modes'		=> [
				// CORRECTION : Le mode doit être 'settings' pour correspondre à la migration release_1_0_0.php
				'settings'	=> [
					'title'	=> 'UCP_REACTIONS_SETTINGS', // Clé de langue pour le sous-menu
					'auth'	=> 'ext_bastien59960/reactions', // Permission requise
					'cat'	=> ['UCP_PREFS'], // Catégorie parente dans l'UCP (Préférences)
				],
			],
		];
	}
}