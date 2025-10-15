<?php

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
