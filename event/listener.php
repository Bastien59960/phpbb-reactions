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
    
    /** @var \phpbb\request\request */
    protected $request;

    /**
     * Constructor
     *
     * @param \phpbb\template\template $template
     * @param \phpbb\path_helper $path_helper  
     * @param \phpbb\request\request $request
     * @param string $ext_path
     */
    public function __construct(\phpbb\template\template $template, \phpbb\path_helper $path_helper, \phpbb\request\request $request, $ext_path)
    {
        $this->template = $template;
        $this->path_helper = $path_helper;
        $this->request = $request;
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
            'core.viewtopic_cache_user_data' => 'load_language',
            'core.viewtopic_post_row_after' => 'display_reactions',
        ];
    }

    /**
     * Load language file
     *
     * @param \phpbb\event\data $event
     */
    public function load_language($event)
    {
        // Charger le fichier de langue de votre extension
        global $user;
        $user->add_lang_ext('bastien59960/reactions', 'common');
    }

    /**
     * Add assets to page
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // Vérifier si nous sommes sur une page qui a besoin des assets
        $page_name = $this->request->variable('page_name', '');
        
        if (strpos($this->request->server('REQUEST_URI'), 'viewtopic') !== false || 
            strpos($this->request->server('REQUEST_URI'), 'viewforum') !== false)
        {
            // Construire les chemins vers les assets
            $css_path = $this->path_helper->update_web_root_path($this->ext_path . 'styles/prosilver/theme/reactions.css');
            $js_path = $this->path_helper->update_web_root_path($this->ext_path . 'styles/prosilver/template/js/reactions.js');
            
            // Assigner les variables au template
            $this->template->assign_vars([
                'S_REACTIONS_ENABLED' => true,
                'REACTIONS_CSS_PATH' => $css_path,
                'REACTIONS_JS_PATH' => $js_path,
            ]);
        }
    }
    
    /**
     * Display reactions on posts
     *
     * @param \phpbb\event\data $event
     */
    public function display_reactions($event)
    {
        $post_row = $event['post_row'];
        $row = $event['row'];
        
        // Ici vous ajouteriez la logique pour récupérer et afficher les réactions
        // Exemple basique :
        
        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'POST_REACTIONS' => '', // Vos données de réactions ici
        ]);
        
        $event['post_row'] = $post_row;
    }
}
