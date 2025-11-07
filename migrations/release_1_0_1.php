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
            array('custom', array(array($this, 'safe_run_importer'))),
            array('config.add', array('bastien59960_reactions_imported', 1)),
        );
    }

    public function revert_data()
    {
        // L'importation est une opération à sens unique.
        // Il n'y a rien à faire lors de la purge, mais il est CRUCIAL
        // de retourner un tableau vide pour éviter une erreur fatale.
        return [];
    }

    /**
     * Méthode d'importation sécurisée.
     *
     * Encapsule l'appel au service d'importation dans un bloc try/catch
     * pour garantir que la migration ne plantera jamais, même si l'importateur échoue.
     *
     * CRUCIAL : Cette méthode ne retourne RIEN (void). C'est ce qui indique au
     * migrateur qu'il n'y a pas d'étapes SQL à traiter ou à inverser,
     * prévenant ainsi l'erreur TypeError lors de la purge.
     */
    public function safe_run_importer()
    {
        try {
            $importer = $this->container->get('bastien59960.reactions.importer');
            if ($this->container->has('console.io')) {
                $importer->set_io($this->container->get('console.io'));
            }
            $importer->run();
        } catch (\Throwable $e) {
            // Ne rien faire, on ne veut pas que la migration échoue.
            // On pourrait logger l'erreur ici si nécessaire.
        }
    }
}