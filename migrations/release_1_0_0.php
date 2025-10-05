<?php
/**
 * Migration de base pour l'extension Reactions
 * 
 * Cette migration crée la table principale des réactions et définit
 * la structure de base nécessaire au fonctionnement de l'extension.
 * 
 * Tables créées :
 * - phpbb_post_reactions : Stocke toutes les réactions aux messages
 * 
 * Champs de la table :
 * - reaction_id : ID unique de la réaction (clé primaire)
 * - post_id : ID du message réagi
 * - topic_id : ID du sujet (pour optimiser les requêtes)
 * - user_id : ID de l'utilisateur qui a réagi
 * - reaction_emoji : Emoji de la réaction (UTF8MB4)
 * - reaction_time : Timestamp de la réaction
 * - reaction_notified : Flag pour l'anti-spam des notifications
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration de base pour l'extension Reactions
 * 
 * Crée la table principale des réactions avec tous les champs nécessaires
 * pour gérer les réactions, les notifications et l'anti-spam.
 */
class release_1_0_0 extends \phpbb\db\migration\migration
{
    // =============================================================================
    // MÉTHODES REQUISES PAR LE SYSTÈME DE MIGRATIONS
    // =============================================================================
    
    /**
     * Vérifier si la migration est déjà installée
     * 
     * Cette méthode vérifie si la table des réactions existe déjà.
     * Elle est utilisée par le système de migrations pour éviter les doublons.
     * 
     * @return bool True si la table existe, False sinon
     */
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions');
    }

    /**
     * Définir les dépendances de cette migration
     * 
     * Cette méthode indique quelles migrations doivent être installées
     * avant cette migration. Ici, on dépend de phpBB 3.3.10.
     * 
     * @return array Liste des migrations dépendantes
     */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    /**
     * Définir le schéma de la base de données
     * 
     * Cette méthode définit la structure de la table des réactions.
     * Elle crée la table avec tous les champs et index nécessaires.
     * 
     * @return array Structure de la base de données
     */
    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'post_reactions' => array(
                    'COLUMNS' => array(
                        'reaction_id'      => array('UINT', null, 'auto_increment'), // ID unique de la réaction
                        'post_id'          => array('UINT', 0),                      // ID du message réagi
                        'topic_id'         => array('UINT', 0),                      // ID du sujet (optimisation)
                        'user_id'          => array('UINT', 0),                      // ID de l'utilisateur qui réagit
                        'reaction_emoji'   => array('VCHAR:191', ''),                 // Emoji de la réaction
                        'reaction_time'    => array('UINT:11', 0),                   // Timestamp de la réaction
                        'reaction_notified'=> array('BOOL', 0),                      // Flag anti-spam notifications
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        'post_id'           => array('INDEX', 'post_id'),            // Index pour les requêtes par message
                        'topic_id'          => array('INDEX', 'topic_id'),           // Index pour les requêtes par sujet
                        'user_id'           => array('INDEX', 'user_id'),            // Index pour les requêtes par utilisateur
                        'post_notified_idx' => array('INDEX', array('post_id', 'reaction_notified')), // Index composé pour l'anti-spam
                    ),
                ),
            ),
        );
    }

    /**
     * Définir le schéma de réversion
     * 
     * Cette méthode définit comment supprimer la table des réactions
     * lors de la désinstallation de l'extension.
     * 
     * @return array Structure de réversion
     */
    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
        );
    }
    
    /**
     * Définir les données à mettre à jour
     * 
     * Cette méthode définit les opérations de données à effectuer
     * lors de l'installation de la migration.
     * 
     * @return array Liste des opérations de données
     */
    public function update_data()
    {
        return array(
            ['custom', [[$this, 'set_utf8mb4_bin']]], // Configurer le charset UTF8MB4 pour les emojis
        );
    }

    /**
     * Configurer le charset UTF8MB4 pour les emojis
     * 
     * Cette méthode configure le champ reaction_emoji pour supporter
     * les emojis avec le charset UTF8MB4 et la collation utf8mb4_bin.
     * 
     * @return void
     */
    public function set_utf8mb4_bin()
    {
        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name}
        MODIFY `reaction_emoji` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";

        $this->db->sql_query($sql);
    }
}
