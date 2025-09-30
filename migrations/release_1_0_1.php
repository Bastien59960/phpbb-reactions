<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_spam_time']);
    }

    static public function depends_on()
    {
        // Cette migration dépend de la création de la table principale
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    public function update_data()
    {
        return [
            // Ajoute les variables de configuration à la table phpbb_config
            ['config.add', ['bastien59960_reactions_spam_time', 45]],     // Fenêtre en minutes
            ['config.add', ['bastien59960_reactions_max_per_post', 20]],   // Max de réactions différentes par post
            ['config.add', ['bastien59960_reactions_max_per_user', 10]],  // Max de réactions par utilisateur par post

            // Ajout du module dans le PCA
        ['module.add', [
            'acp', // C'est un module ACP
            'ACP_CAT_DOT_MODS', // On le met dans l'onglet "Extensions"
            'ACP_REACTIONS_TITLE' // Le nom de notre catégorie de module
        ]],
        ['module.add', [
            'acp', // C'est un module ACP
            'ACP_REACTIONS_TITLE', // Le parent est la catégorie créée juste au-dessus
            [
                'module_basename'   => '\bastien59960\reactions\acp\reactions_module',
                'modes'             => ['settings'], // Le mode géré par notre contrôleur
            ]
        ]],
        ];
    }

    public function revert_data()
    {
        return [
            // Supprime les variables de configuration lors de la désactivation
            ['config.remove', ['bastien59960_reactions_spam_time']],
            ['config.remove', ['bastien59960_reactions_max_per_post']],
            ['config.remove', ['bastien59960_reactions_max_per_user']],
        ];
    }
}
