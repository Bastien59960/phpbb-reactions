<?php

/**
 * @package bastien59960\reactions\controller
 * @copyright (c) 2024 bastien59960
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

<?php
namespace bastien59960\reactions\controller;

class helper
{
    protected $db;
    protected $user;
    protected $template;
    protected $language;
    protected $reactions_table;
    protected $posts_table;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        $reactions_table,
        $posts_table
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->language = $language;
        $this->reactions_table = $reactions_table;
        $this->posts_table = $posts_table;
    }
    
    /**
     * Récupère le HTML complet du bloc de réactions pour un message donné.
     * 
     * @param int $post_id ID du message
     * @return string HTML rendu du bloc de réactions
     */
    public function get_reactions_html_for_post($post_id)
    {
        $post_id = (int) $post_id;
        
        // 1. Récupération des réactions agrégées
        $sql = 'SELECT reaction_emoji, COUNT(reaction_emoji) as reaction_count
                FROM ' . $this->reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                ORDER BY reaction_count DESC';
        $result = $this->db->sql_query($sql);
        
        $reactions_data = [];
        while ($row = $this->db->sql_fetchrow($result))
        {
            $reactions_data[] = $row;
        }
        $this->db->sql_freeresult($result);

        // 2. Récupération des réactions de l'utilisateur actuel
        $user_reacted_emojis = [];
        $user_id = $this->user->data['user_id'];
        $is_logged_in = ($user_id != ANONYMOUS);
        
        if ($is_logged_in)
        {
            $sql = 'SELECT reaction_emoji
                    FROM ' . $this->reactions_table . '
                    WHERE post_id = ' . $post_id . '
                    AND user_id = ' . $user_id;
            $result = $this->db->sql_query($sql);
            while ($row = $this->db->sql_fetchrow($result))
            {
                $user_reacted_emojis[] = $row['reaction_emoji'];
            }
            $this->db->sql_freeresult($result);
        }
        
        // 3. Construction manuelle du HTML (SANS template pour AJAX)
        // C'est la méthode la plus fiable pour AJAX dans phpBB
        $html = '<div class="post-reactions">';
        
        foreach ($reactions_data as $reaction)
        {
            $user_reacted = in_array($reaction['reaction_emoji'], $user_reacted_emojis);
            $active_class = $user_reacted ? ' active' : '';
            $display_style = ($reaction['reaction_count'] == 0) ? ' style="display:none;"' : '';
            
            $html .= sprintf(
                '<span class="reaction%s" data-emoji="%s" data-count="%d"%s>%s <span class="count">%d</span></span>',
                $active_class,
                htmlspecialchars($reaction['reaction_emoji'], ENT_QUOTES, 'UTF-8'),
                (int)$reaction['reaction_count'],
                $display_style,
                $reaction['reaction_emoji'], // emoji brut (déjà UTF-8)
                (int)$reaction['reaction_count']
            );
        }
        
        // Bouton "plus" (si utilisateur connecté)
        if ($is_logged_in) {
            $html .= '<span class="reaction-more" title="Ajouter une réaction">+</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
