<?php
/**
 * Fichier : release_1_0_2.php
 * Chemin : bastien59960/reactions/migrations/release_1_0_2.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Migration corrective pour l'enregistrement du module UCP.
 * Corrige l'erreur "Le module est inaccessible" en réenregistrant
 * correctement le module avec la bonne structure.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_2 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Cette migration dépend de la version 1.0.1
     */
    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_1');
    }

    /**
     * Vérifier si la migration est déjà installée
     */
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_version']) 
            && version_compare($this->config['bastien59960_reactions_version'], '1.0.2', '>=');
    }

    /**
     * Mise à jour des données : correction du module UCP
     */
    public function update_data()
    {
        return array(
            // Étape 1 : Supprimer tous les anciens modules UCP mal configurés
            array('custom', array(array($this, 'remove_old_ucp_modules'))),

            // Étape 2 : Ajouter le module UCP avec la bonne configuration
            array('module.add', array(
                'ucp',                                              // Type
                'UCP_PREFS',                                       // Catégorie parent
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\main_module',
                    'module_langname'   => 'UCP_REACTIONS_SETTINGS',
                    'module_mode'       => 'settings',
                    'module_auth'       => 'ext_bastien59960/reactions',
                )
            )),

            // Étape 3 : Mettre à jour la version
            array('config.add', array('bastien59960_reactions_version', '1.0.2')),
        );
    }

    /**
     * Réversion : suppression du module UCP
     */
    public function revert_data()
    {
        return array(
            // Supprimer le module UCP
            array('module.remove', array('ucp', 'UCP_PREFS', 'UCP_REACTIONS_SETTINGS')),

            // Supprimer la clé de version
            array('config.remove', array('bastien59960_reactions_version')),
        );
    }

    /**
     * Fonction personnalisée pour nettoyer les anciens modules UCP
     */
    public function remove_old_ucp_modules()
    {
        // Supprimer toutes les entrées de modules UCP liées à notre extension
        $sql = 'DELETE FROM ' . $this->table_prefix . "modules
                WHERE module_basename LIKE '%bastien59960%reactions%'
                  AND module_class = 'ucp'";
        $this->db->sql_query($sql);

        // CRITIQUE : Toujours retourner true pour une méthode de migration personnalisée.
        return true;
    }
}