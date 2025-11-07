<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_1.php
 * Rôle :
 * Cette migration est dédiée à l'importation des données d'une ancienne
 * extension. Elle appelle le service d'importation dédié.
 */

namespace bastien59960\reactions\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // Cette migration ne s'exécute qu'une seule fois.
        // On vérifie si une config qu'elle pose existe.
        return isset($this->config['bastien59960_reactions_imported']);
    }

    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'run_importer'))),
            array('config.add', array('bastien59960_reactions_imported', 1)),
        );
    }

    public function run_importer()
    {
        $importer = $this->container->get('bastien59960.reactions.importer');
        if ($this->container->has('console.io')) {
            $importer->set_io($this->container->get('console.io'));
        }
        $importer->run();
    }
}