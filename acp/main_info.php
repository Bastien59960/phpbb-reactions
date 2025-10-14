<?php
/**
 * Fichier : acp/main_info.php — bastien59960/reactions/acp/main_info.php
 *
 * Fichier d'information du module ACP pour l'extension Reactions.
 *
 * Ce fichier définit la structure et les informations du module d'administration (ACP) permettant de gérer les paramètres de l'extension Reactions dans le panneau d'administration phpBB.
 *
 * Points clés :
 *   - Déclaration du module ACP et de ses sous-menus
 *   - Intégration avec le système de modules de phpBB
 *
 * Ce fichier est utilisé par phpBB pour afficher et organiser les pages de configuration de l'extension dans l'ACP.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\acp;

class main_info
{
    public function __construct()
    {
        global $phpbb_container;
        
        if (isset($phpbb_container)) {
            $user = $phpbb_container->get('user');
            $user->add_lang_ext('bastien59960/reactions', 'acp/common');
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
                    'title' => 'ACP_REACTIONS_IMPORT',
                    'auth'  => 'ext_bastien59960/reactions && acl_a_board',
                    'cat'   => ['ACP_REACTIONS_TITLE']
                ],
            ],
        ];
    }
}
