<?php

namespace bastien59960\reactions\controller;

/**
 * Service d'aide pour les réactions.
 * Contient la logique de rendu HTML et d'accès aux données.
 */
class helper
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;
    
    /** @var \phpbb\user */
    protected $user;
    
    /** @var \phpbb\template\template */
    protected $template;
    
    /** @var \phpbb\language\language */
    protected $language;

    /** @var string Table des réactions */
    protected $reactions_table;
    
    /** @var string Table des posts */
    protected $posts_table;

    // Assurez-vous que l'ordre des arguments correspond EXACTEMENT au services.yml
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        $reactions_table, // Nom de la table des réactions (string)
        $posts_table      // Nom de la table des posts (string)
    ) {
        $this->db               = $db;
        $this->user             = $user;
        $this->template         = $template;
        $this->language         = $language;
        $this->reactions_table  = $reactions_table;
        $this->posts_table      = $posts_table;
    }
    
    // Ajoutez ici toutes les méthodes utilitaires que votre contrôleur AJAX appelle
    // Par exemple : public function get_reactions_html_for_post($post_id) { ... }
}
