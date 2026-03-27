<?php
namespace bastien59960\reactions\controller;

use phpbb\db\driver\driver_interface;
use phpbb\template\template;
use phpbb\request\request;
use phpbb\user;

class ucp_reactions
{
    private const REACTION_TYPE = 'bastien59960.reactions.notification.type.reaction';
    private const DIGEST_TYPE = 'bastien59960.reactions.notification.type.reaction_email_digest';
    private const BOARD_METHOD = 'notification.method.board';
    private const EMAIL_METHOD = 'notification.method.email';

    protected $u_action;
    /** @var driver_interface */
    protected $db;
    /** @var template */
    protected $template;
    /** @var request */
    protected $request;
    /** @var user */
    protected $user;
    /** @var string */
    protected $table_prefix;
    
    public function __construct(driver_interface $db, request $request, template $template, user $user, $table_prefix)
    {
        $this->db = $db;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->table_prefix = $table_prefix;
    }
    
    public function set_page_url($u_action)
    {
        $this->u_action = $u_action;
    }

    public function handle($id, $mode)
    {
        add_form_key('bastien59960/reactions');

        // Logique de traitement du formulaire UCP
        if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('bastien59960/reactions'))
            {
                trigger_error('FORM_INVALID');
            }

            // Traitement de la sauvegarde
            $user_reactions_notify = $this->request->variable('user_reactions_notify', 0);
            $user_reactions_cron_email = $this->request->variable('user_reactions_cron_email', 0);
            
            // Sauvegarde en base
            $sql = 'UPDATE ' . USERS_TABLE . '
                SET user_reactions_notify = ' . (int) $user_reactions_notify . ',
                    user_reactions_cron_email = ' . (int) $user_reactions_cron_email . '
                WHERE user_id = ' . (int) $this->user->data['user_id'];
            $this->db->sql_query($sql);

            $user_id = (int) $this->user->data['user_id'];
            $this->sync_global_subscription($user_id, self::REACTION_TYPE, self::BOARD_METHOD, (bool) $user_reactions_notify);
            $this->sync_global_subscription($user_id, self::DIGEST_TYPE, self::EMAIL_METHOD, (bool) $user_reactions_cron_email);

            $this->user->data['user_reactions_notify'] = (int) $user_reactions_notify;
            $this->user->data['user_reactions_cron_email'] = (int) $user_reactions_cron_email;
            
            trigger_error($this->user->lang['UCP_REACTIONS_SAVED'] . adm_back_link($this->u_action));
        }

        // Affichage du template
        $this->template->assign_vars([
            'USER_REACTIONS_NOTIFY'      => $this->user->data['user_reactions_notify'],
            'USER_REACTIONS_CRON_EMAIL'  => $this->user->data['user_reactions_cron_email'],
            'U_ACTION'                   => $this->u_action,
        ]);
    }

    private function sync_global_subscription($user_id, $item_type, $method, $enabled)
    {
        $user_id = (int) $user_id;
        $notify = $enabled ? 1 : 0;
        $table = $this->table_prefix . 'user_notifications';

        $sql = 'UPDATE ' . $table . "
            SET notify = " . (int) $notify . "
            WHERE item_type = '" . $this->db->sql_escape($item_type) . "'
                AND item_id = 0
                AND user_id = " . $user_id . "
                AND method = '" . $this->db->sql_escape($method) . "'";
        $this->db->sql_query($sql);

        if (!$this->db->sql_affectedrows())
        {
            $sql = 'INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', [
                'item_type' => $item_type,
                'item_id'   => 0,
                'user_id'   => $user_id,
                'method'    => $method,
                'notify'    => $notify,
            ]);
            $this->db->sql_query($sql);
        }
    }
}
