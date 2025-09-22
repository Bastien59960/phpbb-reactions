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
    protected $db;
    protected $user;
    protected $reactions_table;
    protected $posts_table;
    protected $template;
    protected $language;
    protected $helper;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        $reactions_table,
        $posts_table,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        \phpbb\controller\helper $helper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->reactions_table = $reactions_table;
        $this->posts_table = $posts_table;
        $this->template = $template;
        $this->language = $language;
        $this->helper = $helper;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.viewtopic_modify_post_row' => 'add_post_reactions',
            'core.page_header' => 'add_assets_to_page',
        ];
    }

    public function add_assets_to_page($event)
    {
        // Add JavaScript and CSS to the page
        $this->template->add_link('reactions-css', 'reactions.css', 'bastien59960/reactions');
        $this->template->add_script_url('reactions-js', $this->template->get_ext_path('bastien59960/reactions') . 'assets/javascript/reactions.js');
    }

public function add_post_reactions($event)
{
    $post_id = (int) $event['post_row']['POST_ID'];
    $user_id = (int) $this->user->data['user_id'];

    if ($post_id === 0) {
        return;
    }

    // --- AJOUT TEST : injecte des réactions fictives ---
    // Pour l'affichage, le template attend reaction_rows (tableau), et reaction_picker_row (emoji pour le picker)
    $event['post_row']['reaction_rows'] = [
        [
            'REACTION_UNICODE' => '👍',
            'REACTION_COUNT'   => 2,
        ],
        [
            'REACTION_UNICODE' => '❤️',
            'REACTION_COUNT'   => 1,
        ],
        [
            'REACTION_UNICODE' => '😂',
            'REACTION_COUNT'   => 3,
        ],
    ];
    // Le picker (bouton +) propose par exemple l'emoji 😎
    $event['post_row']['reaction_picker_row'] = [
        'REACTION_UNICODE' => '😎',
    ];
    // --- FIN AJOUT TEST ---
}
}
        // Fetch reactions from DB for the current post
        $sql = 'SELECT reaction_unicode, COUNT(reaction_id) as reaction_count FROM ' . $this->reactions_table . '
            WHERE post_id = ' . $this->db->sql_escape($post_id) . '
            GROUP BY reaction_unicode
            ORDER BY reaction_count DESC';
        $result = $this->db->sql_query($sql);
        
        $post_reactions = [];
        while ($row = $this->db->sql_fetchrow($result))
        {
            $post_reactions[$row['reaction_unicode']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);

        // Fetch user's reaction for this post
        $user_reaction = null;
        if ($user_id > 0)
        {
            $sql = 'SELECT reaction_unicode FROM ' . $this->reactions_table . '
                WHERE post_id = ' . $this->db->sql_escape($post_id) . '
                AND user_id = ' . $this->db->sql_escape($user_id);
            $result = $this->db->sql_query($sql);
            $user_reaction = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
        }

        // Assign data to the template
        $this->template->assign_block_vars('post_row', [
            'REACTIONS' => $post_reactions,
            'USER_REACTION' => $user_reaction ? $user_reaction['reaction_unicode'] : '',
        ]);

        $this->template->set_ext_data('bastien59960/reactions', [
            'REACTIONS_ACTION_URL' => $this->helper->route('bastien59960_reactions_main_handle'),
        ]);
    }
}
