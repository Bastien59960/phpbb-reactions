<?php
/**
 * Fichier : reaction.php
 * Chemin : ext/bastien59960/reactions/notification/type/reaction.php
 * 
 * CORRECTION: Suppression des type hints string pour compatibilité phpBB 3.3
 */
namespace bastien59960\reactions\notification\type;

use phpbb\notification\type\base;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\template\template;
use bastien59960\reactions\controller\helper as reactions_helper;
use phpbb\user_loader;
use phpbb\request\request_interface;
use phpbb\language\language;

class reaction extends base
{
    protected $config;
    protected $reactions_helper;
    protected $template;
    protected $user_loader;
    protected $language;
    protected $notifications_table;

    /**
     * Constructeur - CORRECTION: suppression des type hints string
     */
    public function __construct(
        driver_interface $db,               // 1. @dbal.conn
        language $language,                 // 2. @language
        user $user,                         // 3. @user
        auth $auth,                         // 4. @auth
        $phpbb_root_path,                   // 5. %core.root_path% - ✅ SANS type hint
        $php_ext,                           // 6. %core.php_ext% - ✅ SANS type hint
        $notifications_table,               // 7. %tables.notifications% - ✅ SANS type hint
        config $config,                     // 8. @config
        user_loader $user_loader,           // 9. @user_loader
        reactions_helper $reactions_helper, // 10. @bastien59960.reactions.helper
        request_interface $request,         // 11. @request
        template $template                  // 12. @template
    ) {
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $notifications_table
        );

        $this->config = $config;
        $this->language = $language;
        $this->user_loader = $user_loader;
        $this->template = $template;
        $this->reactions_helper = $reactions_helper;

        try {
            $this->user->add_lang_ext('bastien59960/reactions', 'common');
        } catch (\Throwable $e) {
            if (defined('DEBUG')) {
                error_log('[Reactions Notification] Unable to load language packs: ' . $e->getMessage());
            }
        }
    }

    public function get_type()
    {
        return 'notification.type.reaction';
    }

    public function is_available()
    {
        return true;
    }

    public static function get_item_id($data) 
    {
        return (int) ($data['post_id'] ?? 0);
    }

    public static function get_item_parent_id($data) 
    {
        return (int) ($data['topic_id'] ?? 0);
    }

    public static function get_item_author_id($data) 
    {
        return (int) ($data['post_author'] ?? ($data['poster_id'] ?? 0));
    }

    public function get_url()
    {
        $post_id = self::get_item_id($this->notification_data);
        
        if (!$post_id) {
            return '';
        }

        return append_sid(
            "{$this->phpbb_root_path}viewtopic.{$this->php_ext}",
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    public static function get_item_url($data) 
    {
        global $phpbb_root_path, $phpEx;
        
        $post_id = self::get_item_id($data);
        
        if (!$post_id) {
            return ''; 
        }
        
        return append_sid(
            "{$phpbb_root_path}viewtopic.$phpEx", 
            'p=' . $post_id
        ) . '#p' . $post_id;
    }

    public function get_title()
    {
        return 'NOTIFICATION_TYPE_REACTION';
    }

    public function get_language_file()
    {
        return 'bastien59960/reactions/notification/reaction';
    }

    public static function get_item_type_name()
    {
        return 'NOTIFICATION_TYPE_REACTION_TITLE';
    }

    public static function get_item_type_description()
    {
        return 'NOTIFICATION_TYPE_REACTION_DESC';
    }

    public function find_users_for_notification($type_data, $options = array())
    {
        $users = array();

        $post_author = (int) ($type_data['post_author'] ?? ($type_data['poster_id'] ?? 0));
        $reacter = (int) ($type_data['reacter'] ?? ($type_data['reacter_id'] ?? 0));

        if ($post_author && $post_author !== $reacter) {
            $users[] = $post_author;
        }

        return $users;
    }

    public function users_to_query()
    {
        return [];
    }

    public function get_email_template()
    {
        return false;
    }

    public function get_email_template_variables()
    {
        return [
            'REACTOR_USERNAME' => $this->data['reacter_username'] ?? '',
            'EMOJI'            => $this->notification_data['emoji'] ?? '',
            'POST_ID'          => self::get_item_id($this->notification_data),
        ];
    }

    public function get_title_for_user($user_id, $lang = null)
    {
        return [
            $this->get_title(),
            [
                $this->notification_data['reacter_username'] ?? 'Quelqu\'un',
                $this->notification_data['emoji'] ?? '?',
            ],
        ];
    }

    public function get_render_data($user_id)
    {
        return [
            'emoji'            => $this->notification_data['emoji'] ?? '',
            'reacter_username' => $this->notification_data['reacter_username'] ?? '',
            'post_id'          => self::get_item_id($this->notification_data),
        ];
    }

    public function get_insert_sql() 
    {
        return [
            'reaction_emoji' => ['VCHAR_UNI', 191],
        ];
    }

    public function create_insert_array($type_data, $pre_create_data = array())
    {
        $insert_array = parent::create_insert_array($type_data, $pre_create_data);
        $insert_array['reaction_emoji'] = $type_data['emoji'] ?? '';
        
        return $insert_array;
    }
}