<?php
/**
* Post Reactions extension for phpBB - DEBUG VERSION
*/

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $controller;
    protected $user;
    protected $template;
    protected $auth;
    protected $helper;

    public function __construct(
        \bastien59960\reactions\controller\main $controller,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\auth\auth $auth,
        \phpbb\controller\helper $helper
    ) {
        $this->controller = $controller;
        $this->user = $user;
        $this->template = $template;
        $this->auth = $auth;
        $this->helper = $helper;
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
        // Debug simple : toujours ajouter du contenu
        $post_id = $event['row']['post_id'];
        
        // Test 1: Contenu HTML statique pour vérifier que l'injection fonctionne
        $debug_html = '<div class="reactions-debug" style="background: yellow; padding: 5px; margin: 5px 0;">
            DEBUG: Extension active pour post ' . $post_id . '
        </div>';
        
        // Test 2: Essayer d'appeler le contrôleur
        try {
            $reactions_html = $this->controller->get_reactions($post_id);
            if (empty($reactions_html)) {
                $reactions_html = '<div style="color: red;">Contrôleur appelé mais HTML vide</div>';
            }
        } catch (\Exception $e) {
            $reactions_html = '<div style="color: red;">ERREUR: ' . $e->getMessage() . '</div>';
        }
        
        $event['post_row'] = array_merge($event['post_row'], [
            'REACTIONS' => $debug_html . $reactions_html,
        ]);
    }

    public function add_reactions_global_vars($event)
    {
        // Variables globales simples pour test
        $this->template->assign_vars([
            'S_CAN_SEE_REACTIONS'   => true,
            'S_CAN_USE_REACTIONS'   => $this->user->data['is_registered'],
            'U_REACTIONS_HANDLE'    => '#', // URL temporaire
        ]);
    }
}
