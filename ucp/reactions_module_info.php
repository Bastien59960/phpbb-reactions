<?php
namespace bastien59960\reactions\ucp;

class reactions_module_info
{
    public function module()
    {
        return [
            'filename'  => '\bastien59960\reactions\ucp\main_module', // CORRECTION : main_module et non reactions_module
            'title'     => 'UCP_REACTIONS_TITLE',
            'modes'     => [
                'settings'  => [
                    'title' => 'UCP_REACTIONS_SETTINGS',
                    'auth'  => 'ext_bastien59960/reactions',
                    'cat'   => ['UCP_PREFS'],
                ],
            ],
        ];
    }
}