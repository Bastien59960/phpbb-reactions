<?php
/**
 * Migration de correction de la position du module UCP
 *
 * Cette migration corrige la position du module UCP des réactions
 * pour qu'il apparaisse en dernière position dans "Préférences du forum".
 *
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_2 extends \phpbb\db\migration\migration
{
    /**
     * Dépendance : installation de base requise
     */
    public static function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_0'];
    }

    /**
     * Vérifie si la correction a déjà été effectuée
     */
    public function effectively_installed()
    {
        // Vérifier si le module UCP a une position valide (left_id > 0)
        $sql = 'SELECT left_id FROM ' . $this->table_prefix . "modules
                WHERE module_basename LIKE '%bastien59960%reactions%ucp%'
                AND module_class = 'ucp'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        // Si left_id > 0, la position est valide
        return $row && (int) $row['left_id'] > 0;
    }

    /**
     * Mise à jour des données
     */
    public function update_data()
    {
        return [
            ['custom', [[$this, 'fix_ucp_module_position']]],
        ];
    }

    /**
     * Corrige la position du module UCP des réactions
     */
    public function fix_ucp_module_position()
    {
        try {
            // 1. Récupérer l'ID du module UCP des réactions
            $sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules
                    WHERE module_basename LIKE '%bastien59960%reactions%ucp%'
                    AND module_class = 'ucp'";
            $result = $this->db->sql_query($sql);
            $module = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$module) {
                return true; // Le module n'existe pas, rien à faire
            }

            $module_id = (int) $module['module_id'];

            // 2. Récupérer UCP_PREFS et ses enfants pour calculer la position
            $sql = 'SELECT module_id, left_id, right_id FROM ' . $this->table_prefix . "modules
                    WHERE module_langname = 'UCP_PREFS'
                    AND module_class = 'ucp'";
            $result = $this->db->sql_query($sql);
            $ucp_prefs = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$ucp_prefs) {
                return true;
            }

            // 3. Trouver le dernier enfant de UCP_PREFS (hors notre module)
            $sql = 'SELECT MAX(right_id) as max_right FROM ' . $this->table_prefix . "modules
                    WHERE parent_id = " . (int) $ucp_prefs['module_id'] . "
                    AND module_id != " . $module_id . "
                    AND module_class = 'ucp'";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            // 4. Calculer la nouvelle position (après le dernier enfant)
            $new_left = $row && $row['max_right'] ? (int) $row['max_right'] + 1 : (int) $ucp_prefs['left_id'] + 1;
            $new_right = $new_left + 1;

            // 5. Mettre à jour la position du module
            $sql = 'UPDATE ' . $this->table_prefix . 'modules
                    SET left_id = ' . $new_left . ', right_id = ' . $new_right . '
                    WHERE module_id = ' . $module_id;
            $this->db->sql_query($sql);

        } catch (\Exception $e) {
            // Log mais ne pas échouer
        }

        return true;
    }
}
