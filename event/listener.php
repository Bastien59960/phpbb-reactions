<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $root_path;
    protected $php_ext;
    protected $user;
    protected $template;
    protected $reaction_url;

    public function __construct($root_path, $php_ext, \phpbb\user $user, \phpbb\template\template $template, $reaction_url)
    {
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
        $this->user = $user;
        $this->template = $template;
        $this->reaction_url = $reaction_url;
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.viewtopic_modify_template_vars' => 'add_reactions_script',
        );
    }

    public function add_reactions_script($event)
    {
        // On n'inclut le script que si l'utilisateur est connecté.
        if (!$this->user->data['is_registered']) {
            return;
        }

        // On passe la variable de template pour l'URL du contrôleur.
        $this->template->assign_vars(array(
            'U_REACTIONS_HANDLE' => $this->reaction_url,
        ));

        // On inclut le fichier JavaScript.
        $this->template->set_custom_cache('reactions_cache_js', true);
        $this->template->set_template_cache('reactions_cache_js', '<script src="' . $this->root_path . 'ext/bastien59960/reactions/assets/js/reactions.js' . '"></script>');
    }
}
