<?php

/**
 * @package bastien59960\reactions\controller
 * @copyright (c) 2024 bastien59960
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace bastien59960\reactions\controller;

/**
 * Service d'aide (Helper) pour la gestion et le rendu des réactions.
 *
 * Ce service encapsule la logique de récupération des données de réaction et
 * la génération du code HTML nécessaire pour la mise à jour AJAX côté client.
 */
class helper
{
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données. */
    protected $db;
    
    /** @var \phpbb\user Objet utilisateur (données de l'utilisateur courant). */
    protected $user;
    
    /** @var \phpbb\template\template Moteur de template de phpBB. */
    protected $template;
    
    /** @var \phpbb\language\language Service de langue. */
    protected $language;

    /** @var string Nom de la table des réactions. */
    protected $reactions_table;
    
    /** @var string Nom de la table des posts. */
    protected $posts_table;

    /**
     * Constructeur.
     *
     * Les arguments doivent correspondre à ceux définis dans config/services.yml
     * pour le service 'bastien59960.reactions.helper'.
     *
     * @param \phpbb\db\driver\driver_interface $db              Connexion DB.
     * @param \phpbb\user $user                                  Objet utilisateur.
     * @param \phpbb\template\template $template                 Moteur de template.
     * @param \phpbb\language\language $language                 Service de langue.
     * @param string $reactions_table                            Nom de la table des réactions.
     * @param string $posts_table                                Nom de la table des posts.
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        $reactions_table,
        $posts_table
    ) {
        $this->db              = $db;
        $this->user            = $user;
        $this->template        = $template;
        $this->language        = $language;
        $this->reactions_table = $reactions_table;
        $this->posts_table     = $posts_table;
    }
    
    // =============================================================================
    // MÉTHODES PUBLIQUES
    // =============================================================================

    /**
     * Récupère le HTML complet du bloc de réactions pour un message donné.
     *
     * Cette méthode récupère toutes les réactions pour un message, vérifie si
     * l'utilisateur actuel a réagi, assigne les variables au template et retourne
     * le HTML rendu.
     *
     * @param int $post_id ID du message.
     * @return string HTML rendu du bloc de réactions.
     */
    public function get_reactions_html_for_post($post_id)
    {
        $post_id = (int) $post_id;
        
        // 1. Récupération des réactions agrégées (compte par emoji)
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

        // 2. Récupération des réactions spécifiques de l'utilisateur actuel
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
        
        // 3. Réinitialisation du bloc de template 'reactions_loop'
        // C'est crucial pour ne pas mélanger le rendu entre plusieurs appels AJAX
        $this->template->destroy_block_vars('reactions_loop');

        // 4. Boucle pour le template
        foreach ($reactions_data as $reaction)
        {
            // La variable globale POST_ID est souvent utilisée, mais pour AJAX, 
            // on assigne les variables au bloc pour s'assurer que c'est isolé.
            $this->template->assign_block_vars('reactions_loop', [
                'POST_ID'        => $post_id, // Redondant mais utile pour le template
                'EMOJI'          => $reaction['reaction_emoji'],
                'COUNT'          => $reaction['reaction_count'],
                'USER_REACTED'   => in_array($reaction['reaction_emoji'], $user_reacted_emojis),
            ]);
        }
        
        // 5. Variables pour le conteneur principal (si non déjà assignées par le listener)
        $this->template->assign_vars([
            'S_REACTIONS_POST_ID'    => $post_id,
            'S_REACTIONS_USER_LOGGED_IN' => $is_logged_in,
            // Autres variables globales pour le bloc de réactions si nécessaire
        ]);
        
        // 6. Rendu du template
        // Utilisation du chemin simplifié pour contourner le problème de recherche de template dans le contexte AJAX
        return $this->template->assign_display('reactions_block_for_ajax', 'reactions_ajax_block', false);
    }
}
