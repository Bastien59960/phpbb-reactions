<?php
/**
* Post Reactions extension for phpBB.
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $controller;
    protected $user;
    protected $template;
    protected $auth;
    protected $reaction_url;
    protected $assets_manager;

    public function __construct(
        \bastien59960\reactions\controller\main $controller,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\auth\auth $auth,
        \Symfony\Component\Routing\Generator\UrlGeneratorInterface $reaction_url,
        \phpbb\assets\manager $assets_manager
    ) {
        $this->controller = $controller;
        $this->user = $user;
        $this->template = $template;
        $this->auth = $auth;
        $this->reaction_url = $reaction_url;
        $this->assets_manager = $assets_manager;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.viewtopic_modify_post_row' => 'add_reactions_to_post',
            'core.viewtopic_assign_template_vars_before' => 'add_reactions_global_vars',
        ];
    }

    public function add_reactions_to_post($event)
    {
        // Vérifier les permissions
        $forum_id = $event['row']['forum_id'];
        if (!$this->auth->acl_get('f_read', $forum_id)) {
            return;
        }

        $post_id = $event['row']['post_id'];
        $reactions_html = $this->controller->get_reactions($post_id);
        
        $event['post_row'] = array_merge($event['post_row'], [
            'REACTIONS' => $reactions_html,
        ]);
    }

    public function add_reactions_global_vars($event)
    {
        $forum_id = $event['forum_id'];
        
        // Vérifier si l'utilisateur peut voir et utiliser les réactions
        $can_see_reactions = $this->auth->acl_get('f_read', $forum_id);
        $can_use_reactions = $this->user->data['is_registered'] && 
                            $this->auth->acl_get('f_use_reactions', $forum_id);

        $this->template->assign_vars([
            'S_CAN_SEE_REACTIONS'   => $can_see_reactions,
            'S_CAN_USE_REACTIONS'   => $can_use_reactions,
            'U_REACTIONS_HANDLE'    => $this->reaction_url->generate('bastien59960_reactions_handle'),
        ]);

        // Ajouter le JS seulement si nécessaire
        if ($can_use_reactions) {
            $this->assets_manager->add_script('reactions.js', [
                'force_minify' => false,
                'path_name' => 'bastien59960.reactions'
            ]);
        }
    }
}
