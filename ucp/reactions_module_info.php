<?php
/**
* @package module_install
*/

namespace bastien59960\reactions\ucp;

class reactions_module_info
{
    public function module()
    {
        return array(
            'filename'	=> '\bastien59960\reactions\ucp\reactions_module',
            'title'		=> 'UCP_REACTIONS',  // Replace with actual lang key if different
            'modes'		=> array(
                'reactions'	=> array(  // Replace 'reactions' with the actual mode name from the migration
                    'title'	=> 'UCP_REACTIONS_SETTINGS',  // Replace with actual lang key
                    'auth'	=> 'ext_bastien59960/reactions',
                    'cat'	=> array('UCP_USER'),  // Or a custom category like 'UCP_REACTIONS'
                ),
            ),
        );
    }
}