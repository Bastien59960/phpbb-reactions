<?php
/**
 * Reactions Extension for phpBB 3.3
 * ACP Module Information - Avec import
 */

namespace bastien59960\reactions\acp;

class main_info
{
    public function __construct()
    {
        global $phpbb_container;
        
        if (isset($phpbb_container)) {
            $language = $phpbb_container->get('language');
            $language->add_lang('acp/common', 'bastien59960/reactions');
        }
    }

    public function module()
    {
        // Forcer le chargement des langues pour le menu
        global $user;
        if (!isset($user->lang['ACP_REACTIONS_TITLE'])) {
            $user->add_lang_ext('bastien59960/reactions', 'acp/common');
        }
        
        return [
            'filename'  => '\bastien59960\reactions\acp\main_module',
            'title'     => 'ACP_REACTIONS_TITLE',
            'modes'     => [
                'settings' => [
                    'title' => 'ACP_REACTIONS_SETTINGS',
                    'auth'  => 'ext_bastien59960/reactions && acl_a_board',
                    'cat'   => ['ACP_REACTIONS_TITLE']
                ],
                'import' => [  // NOUVEAU
                    'title' => 'Importer anciennes réactions',
                    'auth'  => 'ext_bastien59960/reactions && acl_a_board',
                    'cat'   => ['ACP_REACTIONS_TITLE']
                ],
            ],
        ];
    }
}