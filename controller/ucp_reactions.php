<?php
namespace bastien59960\reactions\controller;

class ucp_reactions
{
    protected $u_action;

    public function set_page_url($u_action)
    {
        $this->u_action = $u_action;
    }

    public function handle($id, $mode)
    {
        global $template, $user, $request, $config;

        // Logique de traitement du formulaire UCP
        if ($request->is_set_post('submit'))
        {
            // Traitement de la sauvegarde
            $user_reactions_notify = $request->variable('user_reactions_notify', 0);
            $user_reactions_cron_email = $request->variable('user_reactions_cron_email', 0);
            
            // Sauvegarde en base...
            
            trigger_error($user->lang['UCP_REACTIONS_SAVED'] . adm_back_link($this->u_action));
        }

        // Affichage du template
        $template->assign_vars([
            'USER_REACTIONS_NOTIFY'      => $user->data['user_reactions_notify'],
            'USER_REACTIONS_CRON_EMAIL'  => $user->data['user_reactions_cron_email'],
            'U_ACTION'                   => $this->u_action,
        ]);
    }
}