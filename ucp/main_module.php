<?php
/**
 * Fichier : ucp/main_module.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Point d'entrée principal du module UCP "Préférences des réactions"
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\ucp;

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;
    
    /**
     * Constructeur.
     * Chargement des fichiers de langue ici pour qu'ils soient disponibles
     * pour le menu de navigation de l'UCP.
     */
    public function __construct()
    {
        global $user;
        // CORRECTION CRITIQUE : Charger les langues ici, AVANT l'appel de main().
        $user->add_lang_ext('bastien59960/reactions', 'common');
    }

    public function main($id, $mode)
    {
        global $template, $user, $request, $config, $phpbb_container;

        $this->tpl_name = 'ucp_reactions';
        $this->page_title = 'UCP_REACTIONS_TITLE';

        // Vérifier que le contrôleur existe
        if (!$phpbb_container->has('bastien59960.reactions.controller.ucp_reactions'))
        {
            trigger_error('UCP_REACTIONS_CONTROLLER_NOT_FOUND', E_USER_WARNING);
        }

        // Récupérer le contrôleur
        $controller = $phpbb_container->get('bastien59960.reactions.controller.ucp_reactions');
        
        // Passer l'URL d'action
        $controller->set_page_url($this->u_action);

        // Déléguer le traitement
        $controller->handle($id, $mode);
    }
}