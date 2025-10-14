<?php
/**
 * Fichier : helper.php — bastien59960/reactions/controller/helper.php
 * Fichier : helper.php
 * Chemin : bastien59960/reactions/controller/helper.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/controller/helper.php
 *
 * Classe Helper principale de l'extension Reactions pour phpBB.
 * Rôle :
 * Cette classe fournit des méthodes utilitaires centralisées pour l'extension.
 * Son rôle principal est de générer le bloc HTML des réactions pour un message
 * donné. Ce HTML est ensuite renvoyé par le contrôleur AJAX pour mettre à jour
 * l'affichage sans recharger la page.
 *
 * Cette classe centralise des méthodes utilitaires utilisées par différents
 * composants de l'extension (contrôleurs, listeners, templates, etc.).
 * Elle sert d'abstraction pour faciliter les appels récurrents à la base
 * de données, aux templates, aux routes ou à la traduction.
 * Informations reçues :
 * - `get_reactions_html_for_post($post_id)` : Reçoit l'ID d'un message.
 *
 * Structure du fichier :
 *  - Déclaration du namespace et imports
 *  - Définition des propriétés et injection des dépendances
 *  - Méthodes utilitaires (chaque méthode est documentée individuellement)
 * Elle est injectée comme service dans d'autres composants (notamment le
 * contrôleur AJAX) pour éviter la duplication de code.
 *
 * Bonnes pratiques respectées :
 *  - Aucune logique métier dupliquée ailleurs
 *  - Utilisation correcte du controller_helper pour générer des routes
 *  - Préparation pour extension future des fonctionnalités
 *
 * @package bastien59960
eactions
 * @author  bastien
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

    public function __construct(
        driver_interface $db,
        user $user,
        template $template,
        language $language,
        controller_helper $controller_helper,
        string $table_post_reactions,
        string $table_posts
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->language = $language;
        $this->controller_helper = $controller_helper;
        $this->table_post_reactions = $table_post_reactions;
        $this->table_posts = $table_posts;
    }

        // =============================================================================
    // MÉTHODE : Génération du HTML des réactions pour un post
    // =============================================================================
    /**
     * Génère le HTML complet du bloc de réactions pour un message donné
     *
     * @param int $post_id ID du message
     * @return string HTML prêt à injecter côté client (AJAX)
     */
    public function get_reactions_html_for_post($post_id)
    {
        $post_id = (int) $post_id;
        $sql = 'SELECT reaction_emoji, COUNT(reaction_emoji) as reaction_count
                FROM ' . $this->table_post_reactions . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                ORDER BY reaction_count DESC';
        $result = $this->db->sql_query($sql);
        $reactions_data = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions_data[] = $row;
        }
        $this->db->sql_freeresult($result);

        $user_reacted_emojis = [];
        $user_id = $this->user->data['user_id'];
        $is_logged_in = ($user_id != ANONYMOUS);
        if ($is_logged_in) {
            $sql = 'SELECT reaction_emoji
                    FROM ' . $this->table_post_reactions . '
                    WHERE post_id = ' . $post_id . '
                    AND user_id = ' . $user_id;
            $result = $this->db->sql_query($sql);
            while ($row = $this->db->sql_fetchrow($result)) {
                $user_reacted_emojis[] = $row['reaction_emoji'];
            }
            $this->db->sql_freeresult($result);
        }

        $html = '<div class="post-reactions">';
        foreach ($reactions_data as $reaction) {
            $user_reacted = in_array($reaction['reaction_emoji'], $user_reacted_emojis);
            $active_class = $user_reacted ? ' active' : '';
            $display_style = ($reaction['reaction_count'] == 0) ? ' style="display:none;"' : '';
            $html .= sprintf(
                '<span class="reaction%s" data-emoji="%s" data-count="%d"%s>%s <span class="count">%d</span></span>',
                $active_class,
                htmlspecialchars($reaction['reaction_emoji'], ENT_QUOTES, 'UTF-8'),
                (int) $reaction['reaction_count'],
                $display_style,
                $reaction['reaction_emoji'],
                (int) $reaction['reaction_count']
            );
        }
        if ($is_logged_in) {
            $html .= '<span class="reaction-more" title="Ajouter une réaction">+</span>';
        }
        $html .= '</div>';
        return $html;
    }

    // =============================================================================
    // MÉTHODE : Génération d'URL (proxy vers controller_helper)
    // =============================================================================
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
