<?php
namespace bastien59960\reactions\acp;

class main_module
{
    public $u_action;

    public function main($id, $mode)
    {
        global $config, $request, $template, $user;

        $this->tpl_name = 'acp_reactions_settings';
        $this->page_title = $user->lang('ACP_REACTIONS_SETTINGS');
        add_form_key('bastien59960_reactions');

        if ($request->is_set_post('submit')) {
            if (!check_form_key('bastien59960_reactions')) {
                trigger_error('FORM_INVALID');
            }

            $config->set('bastien59960_reactions_spam_time', $request->variable('spam_time', 0));
            // ... sauvegarder les autres réglages de la même manière ...

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        $template->assign_vars([
            'U_ACTION'              => $this->u_action,
            'REACTIONS_SPAM_TIME'   => $config['bastien59960_reactions_spam_time'],
        ]);
    }
}
