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
    // C'est la méthode la plus fiable pour inclure les assets
    $this->template->set_ext_data('bastien59960/reactions', [
        'js' => [
            'reactions.js',
        ],
        'css' => [
            'reactions.css',
        ],
    ]);
}

    public function add_post_reactions($event)
    {
        $post_id = (int) $event['post_row']['POST_ID'];
        $user_id = (int) $this->user->data['user_id'];

        if ($post_id === 0) {
            return;
        }

        // --- AJOUT TEST : injecte des réactions fictives ---
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
        $event['post_row']['reaction_picker_row'] = [
            'REACTION_UNICODE' => '😎',
        ];
        // --- FIN AJOUT TEST ---
    }
}
