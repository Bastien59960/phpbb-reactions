<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_1.php
 * --------------------------------------------------------------
 * Rôle :
 * Migration corrective et évolutive.
 * Cette migration utilise le conteneur de services pour interagir avec
 * le gestionnaire de notifications et la configuration, ce qui nécessite
 * d'hériter de `container_aware_migration`.
 * --------------------------------------------------------------
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration 1.0.1
 *
 * CORRECTION CRITIQUE :
 * La classe hérite de `\phpbb\db\migration\container_aware_migration`
 * pour que le conteneur de services ($this->container) soit injecté
 * automatiquement. Cela résout l'erreur "Undefined property: $container".
 */
class release_1_0_1 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Dépendances de cette migration.
     * Elle ne s'exécutera qu'après l'installation de la version 1.0.0.
     */
    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    /**
     * Applique les modifications de données.
     *
     * @return array
     */
    public function update_data()
    {
        // Cette section est un exemple de ce que la migration pourrait faire.
        // Adaptez-la en fonction des actions réelles que vous souhaitez effectuer.
        // Par exemple, ajouter une nouvelle configuration ou mettre à jour un service.
        // L'important est que `$this->container` est maintenant accessible ici.
        
        // Exemple d'utilisation du conteneur pour récupérer un service :
        // $config = $this->container->get('config');
        // $config->set('bastien59960_reactions_new_feature', true);

        return array(
            // Ajoutez ici les étapes de mise à jour des données
            // Exemple :
            // array('config.add', array('bastien59960_reactions_new_setting', 'default_value')),
        );
    }

    /**
     * Annule les modifications de données.
     *
     * @return array
     */
    public function revert_data()
    {
        // Annule les actions de `update_data()`
        return array(
            // Ajoutez ici les étapes pour annuler la mise à jour
            // Exemple :
            // array('config.remove', array('bastien59960_reactions_new_setting')),
        );
    }
}