<?php
/**
 * Reactions Extension for phpBB 3.3
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\template\template */
    protected $template;
    
    /** @var \phpbb\path_helper */
    protected $path_helper;
    
    /** @var string */
    protected $ext_path;
    
    /**
     * Constructor
     *
     * @param \phpbb\template\template $template
     * @param \phpbb\path_helper $path_helper
     * @param string $ext_path
     */
    public function __construct(\phpbb\template\template $template, \phpbb\path_helper $path_helper, $ext_path)
    {
        $this->template = $template;
        $this->path_helper = $path_helper;
        $this->ext_path = $ext_path;
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.page_header' => 'add_assets_to_page',
            // Ajoutez d'autres événements selon vos besoins
        ];
    }

    /**
     * Add assets to page
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // Méthode corrigée pour ajouter les assets CSS et JS
        
        // Ajouter le CSS
        $this->template->assign_vars([
            'S_REACTIONS_CSS' => $this->path_helper->update_web_root_path($this->ext_path . 'styles/prosilver/theme/reactions.css'),
        ]);
        
        // Ajouter le JavaScript
        $this->template->assign_vars([
            'S_REACTIONS_JS' => $this->path_helper->update_web_root_path($this->ext_path . 'styles/prosilver/template/js/reactions.js'),
        ]);
        
        // Alternative pour ajouter directement dans le template
        // Vous devrez inclure ceci dans votre template overall_header_head_append.html :
        // <link href="{S_REACTIONS_CSS}" rel="stylesheet" type="text/css" media="screen" />
        // <script src="{S_REACTIONS_JS}"></script>
    }
}
