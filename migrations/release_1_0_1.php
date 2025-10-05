<?php
/**
 * Migration de configuration pour l'extension Reactions
 * 
 * Cette migration ajoute les options de configuration nécessaires
 * au fonctionnement de l'extension Reactions.
 * 
 * Options de configuration ajoutées :
 * - bastien59960_reactions_max_per_post : Nombre maximum de types de réactions par message (défaut: 20)
 * - bastien59960_reactions_max_per_user : Nombre maximum de réactions par utilisateur sur un message (défaut: 10)
 * - bastien59960_reactions_enabled : Activation/désactivation de l'extension (défaut: 1)
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration de configuration pour l'extension Reactions
 * 
 * Ajoute les options de configuration nécessaires au fonctionnement
 * de l'extension et définit les valeurs par défaut.
 */
class release_1_0_1 extends \phpbb\db\migration\migration
{
    // =============================================================================
    // MÉTHODES REQUISES PAR LE SYSTÈME DE MIGRATIONS
    // =============================================================================
    
    /**
     * Vérifier si la migration est déjà installée
     * 
     * Cette méthode vérifie si les options de configuration existent déjà.
     * Elle est utilisée par le système de migrations pour éviter les doublons.
     * 
     * @return bool True si les options existent, False sinon
     */
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_max_per_post']);
    }

    /**
     * Définir les dépendances de cette migration
     * 
     * Cette méthode indique quelles migrations doivent être installées
     * avant cette migration. Ici, on dépend de la migration 1.0.0.
     * 
     * @return array Liste des migrations dépendantes
     */
    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    /**
     * Ajouter les options de configuration
     * 
     * Cette méthode ajoute les options de configuration nécessaires
     * au fonctionnement de l'extension avec leurs valeurs par défaut.
     * 
     * @return array Liste des opérations de configuration
     */
    public function update_data()
    {
        return array(
            array('config.add', array('bastien59960_reactions_max_per_post', 20)), // Max 20 types de réactions par message
            array('config.add', array('bastien59960_reactions_max_per_user', 10)), // Max 10 réactions par utilisateur
            array('config.add', array('bastien59960_reactions_enabled', 1)),       // Extension activée par défaut
        );
    }

    /**
     * Supprimer les options de configuration
     * 
     * Cette méthode supprime les options de configuration lors de
     * la désinstallation de l'extension.
     * 
     * @return array Liste des opérations de suppression
     */
    public function revert_data()
    {
        return array(
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
        );
    }
}