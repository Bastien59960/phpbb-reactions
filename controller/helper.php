<?php
/**
 * Fichier : helper.php
 * Chemin : bastien59960/reactions/controller/helper.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Cette classe de service (helper) centralise la logique de génération du rendu
 * HTML pour le bloc des réactions. Son rôle est de prendre un `post_id`, de
 * récupérer l'état actuel des réactions depuis la base de données, et de
 * construire le code HTML correspondant. Ce code est ensuite utilisé par le
 * contrôleur AJAX et le listener d'événements pour mettre à jour l'affichage.
 *
 * @output string Le bloc HTML complet des réactions pour un message.
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
namespace bastien59960\reactions\controller;

use phpbb\db\driver\driver_interface;
use phpbb\user;
use phpbb\template\template;
use phpbb\language\language;
use phpbb\controller\helper as controller_helper;

/**
 * Helper class for Reactions extension
 */
class helper
{
    /** @var driver_interface */
    protected $db;

    /** @var user */
    protected $user;

    /** @var template */
    protected $template;

    /** @var language */
    protected $language;

    /** @var controller_helper */
    protected $controller_helper;

    /** @var string */
    protected $table_post_reactions;

    /** @var string */
    protected $table_posts;

    /**
     * Constructeur - CORRECTION: suppression des type hints string
     */
    public function __construct(
        driver_interface $db,
        user $user,
        template $template,
        language $language,
        controller_helper $controller_helper,
        $table_post_reactions  // ✅ SANS type hint string
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->language = $language;
        $this->controller_helper = $controller_helper;
        $this->table_post_reactions = $table_post_reactions;
    }

    /**
     * Génère le HTML complet du bloc de réactions pour un message donné
     *
     * @param int $post_id ID du message
     * @return string HTML prêt à injecter côté client (AJAX)
     */
    public function get_reactions_html_for_post($post_id)
    {
        $post_id = (int) $post_id;

        $sql = 'SELECT r.reaction_emoji, r.user_id, u.username
                FROM ' . $this->table_post_reactions . ' r
                LEFT JOIN ' . USERS_TABLE . ' u ON u.user_id = r.user_id
                WHERE r.post_id = ' . $post_id . '
                ORDER BY r.reaction_time ASC';
        $result = $this->db->sql_query($sql);

        $aggregated = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $emoji = (string) $row['reaction_emoji'];
            if ($emoji === '') {
                continue;
            }

            if (!isset($aggregated[$emoji])) {
                $aggregated[$emoji] = [
                    'count' => 0,
                    'users' => [],
                    'user_reacted' => false,
                ];
            }

            $aggregated[$emoji]['count']++;
            $aggregated[$emoji]['users'][] = [
                'user_id'  => (int) $row['user_id'],
                'username' => $row['username'] ?? '',
            ];
        }
        $this->db->sql_freeresult($result);

        $user_id = (int) $this->user->data['user_id'];
        $is_logged_in = ($user_id !== ANONYMOUS);
        if ($is_logged_in) {
            foreach ($aggregated as $emoji => &$data) {
                foreach ($data['users'] as $user_info) {
                    if ($user_info['user_id'] === $user_id) {
                        $data['user_reacted'] = true;
                        break;
                    }
                }
            }
            unset($data);
        }

        $reactions = [];
        foreach ($aggregated as $emoji => $data) {
            if ($data['count'] <= 0) {
                continue;
            }

            $reactions[] = [
                'emoji'        => $emoji,
                'count'        => (int) $data['count'],
                'users'        => $data['users'],
                'user_reacted' => !empty($data['user_reacted']),
            ];
        }

        usort($reactions, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $html = '<div class="post-reactions">';

        foreach ($reactions as $reaction) {
            $classes = ['reaction'];
            if ($reaction['user_reacted']) {
                $classes[] = 'active';
            }
            if (!$is_logged_in) {
                $classes[] = 'reaction-readonly';
            }

            $class_attr = implode(' ', $classes);
            $emoji_attr = htmlspecialchars($reaction['emoji'], ENT_QUOTES, 'UTF-8');
            $users_json = htmlspecialchars(json_encode($reaction['users'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

            $html .= sprintf(
                '<span class="%s" data-emoji="%s" data-count="%d" data-users="%s">%s <span class="count">%d</span></span>',
                $class_attr,
                $emoji_attr,
                (int) $reaction['count'],
                $users_json,
                $reaction['emoji'],
                (int) $reaction['count']
            );
        }

        if ($is_logged_in) {
            $tooltip = htmlspecialchars($this->language->lang('REACTIONS_ADD_TOOLTIP'), ENT_QUOTES, 'UTF-8');
            $html .= '<span class="reaction-more" role="button" title="' . $tooltip . '" aria-label="' . $tooltip . '">&#128077;</span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Génère une URL via le contrôleur phpBB ou fallback append_sid
     *
     * @param string $route
     * @param array $params
     * @return string URL générée
     */
    public function route($route, array $params = [])
    {
        if (isset($this->controller_helper) && is_object($this->controller_helper)) {
            return $this->controller_helper->route($route, $params);
        }
        if (!empty($params) && isset($params['p'])) {
            return append_sid('viewtopic.php', 'p=' . (int) $params['p'] . '#p' . (int) $params['p']);
        }
        return append_sid('index.php', '');
    }
}