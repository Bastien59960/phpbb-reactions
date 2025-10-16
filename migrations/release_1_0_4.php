<?php
/**
 * Fichier : release_1_0_4.php
 * Chemin : bastien59960/reactions/migrations/release_1_0_4.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier de migration est exécuté lors de la mise à jour de l'extension
 * vers la version 1.0.4. Il ajoute des options de configuration supplémentaires
 * pour l'interface utilisateur, comme la taille des emojis et les paramètres
 * du sélecteur (picker).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_4 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_post_emoji_size'])
            && isset($this->config['bastien59960_reactions_picker_width'])
            && isset($this->config['bastien59960_reactions_picker_height'])
            && isset($this->config['bastien59960_reactions_picker_emoji_size'])
            && isset($this->config['bastien59960_reactions_picker_show_categories'])
            && isset($this->config['bastien59960_reactions_picker_show_search'])
            && isset($this->config['bastien59960_reactions_picker_use_json'])
            && isset($this->config['bastien59960_reactions_sync_interval']);
    }

    static public function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_0'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['bastien59960_reactions_post_emoji_size', 24]],
            ['config.add', ['bastien59960_reactions_picker_width', 320]],
            ['config.add', ['bastien59960_reactions_picker_height', 280]],
            ['config.add', ['bastien59960_reactions_picker_emoji_size', 24]],
            ['config.add', ['bastien59960_reactions_picker_show_categories', 1]],
            ['config.add', ['bastien59960_reactions_picker_show_search', 1]],
            ['config.add', ['bastien59960_reactions_picker_use_json', 1]],
            ['config.add', ['bastien59960_reactions_sync_interval', 5000]],
        ];
    }
}
