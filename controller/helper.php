<?php
/**
 * Fichier : controller/helper.php — bastien59960/reactions/controller/helper.php
 *
 * Service d'aide (Helper) pour la gestion et le rendu des réactions.
 *
 * Ce fichier encapsule la logique de récupération des données de réaction et
 * la génération du code HTML nécessaire pour la mise à jour AJAX côté client.
 *
 * Points clés de la logique métier :
 *   - Récupération des réactions depuis la base de données
 *   - Vérification de l'état des réactions de l'utilisateur actuel
 *   - Construction manuelle du HTML pour éviter les problèmes de templates AJAX
 *   - Support complet des emojis Unicode (utf8mb4)
 *   - Génération de HTML sécurisé (échappement XSS)
 *
 * Ce helper est utilisé par le contrôleur AJAX pour retourner le HTML mis à jour
 * après ajout ou suppression d'une réaction, évitant ainsi un rechargement de page.
 *
 * POURQUOI UN HELPER ?
 * - Centralise la logique de rendu des réactions (évite la duplication de code)
 * - Sépare les responsabilités : le contrôleur gère la sécurité/validation, 
 *   le helper gère le rendu
 * - Facilite les tests unitaires et la maintenance
 * - Permet de réutiliser la logique dans d'autres contextes (ex: pre-render serveur)
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

/**
 * Classe Helper pour la gestion du rendu des réactions
 * 
 * Gère la génération du HTML des blocs de réactions pour les réponses AJAX.
 * Évite l'utilisation du système de templates phpBB qui est complexe en contexte AJAX.
 */
class helper
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données */
    protected $db;
    
    /** @var \phpbb\user Utilisateur actuel */
    protected $user;
    
    /** @var \phpbb\template\template Moteur de template (non utilisé mais injecté pour compatibilité future) */
    protected $template;
    
    /** @var \phpbb\language\language Gestionnaire de langues */
    protected $language;

    /** @var string Nom de la table des réactions */
    protected $reactions_table;
    
    /** @var string Nom de la table des messages */
    protected $posts_table;

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur du helper de réactions
     * 
     * Initialise les services nécessaires pour générer le HTML des réactions.
     * Les arguments correspondent exactement à ceux définis dans config/services.yml.
     * 
     * @param \phpbb\db\driver\driver_interface $db Connexion à la base de données
     * @param \phpbb\user $user Utilisateur actuel
     * @param \phpbb\template\template $template Moteur de template phpBB
     * @param \phpbb\language\language $language Gestionnaire de langues
     * @param string $reactions_table Nom de la table des réactions (depuis parameters.yml)
     * @param string $posts_table Nom de la table des messages (depuis parameters.yml)
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
    // MÉTHODE PRINCIPALE DE RENDU
    // =============================================================================
    
    /**
     * Génère le HTML complet du bloc de réactions pour un message donné
     * 
     * Cette méthode est appelée par le contrôleur AJAX après ajout ou suppression
     * d'une réaction. Elle reconstruit le HTML complet du bloc de réactions pour
     * permettre une mise à jour dynamique côté client sans rechargement de page.
     * 
     * POURQUOI CONSTRUIRE LE HTML MANUELLEMENT ?
     * - Le système de templates phpBB est conçu pour le rendu de pages complètes
     * - En contexte AJAX, il est très difficile de "capturer" le HTML d'un template
     * - La construction manuelle garantit un HTML cohérent et performant
     * - Évite les problèmes de cache et de contexte de template
     * 
     * PROCESSUS EN 3 ÉTAPES :
     * 1. Récupération des réactions agrégées (compteurs par emoji)
     * 2. Récupération des réactions de l'utilisateur actuel (pour l'état "active")
     * 3. Construction du HTML avec échappement de sécurité
     * 
     * @param int $post_id ID du message pour lequel générer le HTML
     * @return string HTML complet du bloc de réactions, prêt à injecter côté client
     */
    public function get_reactions_html_for_post($post_id)
    {
        // =====================================================================
        // ÉTAPE 1 : RÉCUPÉRATION DES RÉACTIONS AGRÉGÉES
        // =====================================================================
        
        // Conversion en entier pour sécurité SQL (évite injection)
        $post_id = (int) $post_id;
        
        // Requête SQL pour récupérer toutes les réactions de ce message
        // GROUP BY : regroupe les réactions identiques
        // COUNT : compte combien d'utilisateurs ont utilisé chaque emoji
        // ORDER BY : trie par popularité (emoji le plus utilisé en premier)
        $sql = 'SELECT reaction_emoji, COUNT(reaction_emoji) as reaction_count
                FROM ' . $this->reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                ORDER BY reaction_count DESC';
        $result = $this->db->sql_query($sql);
        
        // Construction du tableau de données
        // Structure : [['reaction_emoji' => '👍', 'reaction_count' => 5], ...]
        $reactions_data = [];
        while ($row = $this->db->sql_fetchrow($result))
        {
            $reactions_data[] = $row;
        }
        $this->db->sql_freeresult($result); // Libération de la mémoire

        // =====================================================================
        // ÉTAPE 2 : VÉRIFICATION DES RÉACTIONS DE L'UTILISATEUR ACTUEL
        // =====================================================================
        
        // Pour afficher visuellement si l'utilisateur a réagi (classe CSS "active")
        $user_reacted_emojis = [];
        $user_id = $this->user->data['user_id'];
        
        // Vérification si l'utilisateur est connecté
        // ANONYMOUS = constante phpBB pour les utilisateurs non connectés
        $is_logged_in = ($user_id != ANONYMOUS);
        
        if ($is_logged_in)
        {
            // Récupération de toutes les réactions de cet utilisateur sur ce message
            $sql = 'SELECT reaction_emoji
                    FROM ' . $this->reactions_table . '
                    WHERE post_id = ' . $post_id . '
                    AND user_id = ' . $user_id;
            $result = $this->db->sql_query($sql);
            
            // Stockage dans un tableau pour vérification rapide avec in_array()
            while ($row = $this->db->sql_fetchrow($result))
            {
                $user_reacted_emojis[] = $row['reaction_emoji'];
            }
            $this->db->sql_freeresult($result);
        }
        
        // =====================================================================
        // ÉTAPE 3 : CONSTRUCTION MANUELLE DU HTML
        // =====================================================================
        
        // Conteneur principal des réactions
        $html = '<div class="post-reactions">';
        
        // Boucle sur chaque emoji ayant des réactions
        foreach ($reactions_data as $reaction)
        {
            // Vérification si l'utilisateur actuel a utilisé cet emoji
            $user_reacted = in_array($reaction['reaction_emoji'], $user_reacted_emojis);
            
            // Classe CSS "active" si l'utilisateur a réagi (affichage différent)
            $active_class = $user_reacted ? ' active' : '';
            
            // Masquage si compteur à zéro (ne devrait pas arriver mais sécurité)
            $display_style = ($reaction['reaction_count'] == 0) ? ' style="display:none;"' : '';
            
            // Construction du HTML de la réaction avec sprintf pour lisibilité
            // SÉCURITÉ : htmlspecialchars() pour data-emoji évite injection XSS
            // L'emoji brut est affiché tel quel (déjà encodé UTF-8 par la DB)
            $html .= sprintf(
                '<span class="reaction%s" data-emoji="%s" data-count="%d"%s>%s <span class="count">%d</span></span>',
                $active_class,                                                    // Classe "active" ou vide
                htmlspecialchars($reaction['reaction_emoji'], ENT_QUOTES, 'UTF-8'), // Emoji échappé pour attribut HTML
                (int)$reaction['reaction_count'],                                 // Compteur (attribut data)
                $display_style,                                                   // Style inline si caché
                $reaction['reaction_emoji'],                                      // Emoji affiché (contenu visible)
                (int)$reaction['reaction_count']                                  // Compteur affiché
            );
        }
        
        // =====================================================================
        // AJOUT DU BOUTON "PLUS" (OUVRE LE PICKER D'EMOJIS)
        // =====================================================================
        
        // Le bouton "+" n'est affiché que pour les utilisateurs connectés
        // Il déclenche l'ouverture de la palette d'emojis (géré par reactions.js)
        if ($is_logged_in) {
            $html .= '<span class="reaction-more" title="Ajouter une réaction">+</span>';
        }
        
        // Fermeture du conteneur
        $html .= '</div>';
        
        // Retour du HTML complet prêt à être injecté dans la réponse AJAX
        return $html;
    }
}
