<?php
namespace bastien59960\reactions\acp;

class main_info
{
    public function module()
    {
        return [
            'filename'  => '\bastien59960\reactions\acp\main_module',
            'title'     => 'ACP_REACTIONS_TITLE',
            'modes'     => [
                'settings' => ['title' => 'ACP_REACTIONS_SETTINGS', 'cat' => ['ACP_CAT_DOT_MODS']],
            ],
        ];
    }
}
