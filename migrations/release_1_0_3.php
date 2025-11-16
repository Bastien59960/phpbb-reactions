<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2024 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : release_1_0_3.php 
 * Chemin : /migrations/release_1_0_3.php 
 *
 * @brief      Migration pour rendre la colonne des notifications compatible avec les emojis.
 *
 * Cette migration corrige un problème fondamental où les emojis (qui sont des
 * caractères UTF-8 sur 4 octets) ne pouvaient pas être stockés dans la colonne
 * `notification_data`, causant des erreurs SQL "Incorrect string value".
 *
 * CORRECTION : Utilise une requête SQL directe au lieu de change_columns car
 * le DBAL de phpBB ne gère pas bien la conversion de charset/collation.
 */
namespace bastien59960\reactions\migrations;

class release_1_0_3 extends \phpbb\db\migration\migration
{
    /**
     * Elle ne s'exécutera qu'après la migration de la version 1.0.2.
     */
    public static function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_2'];
    }
    
    /**
     * Vérifie si la migration doit être effectuée.
     * On vérifie si la colonne est déjà en utf8mb4 pour éviter de la reconvertir.
     */
    public function effectively_installed()
    {
        // Récupérer les infos de la colonne
        $sql = "SELECT CHARACTER_SET_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$this->table_prefix}notifications' 
                AND COLUMN_NAME = 'notification_data'";
        $result = $this->db->sql_query($sql);
        $charset = $this->db->sql_fetchfield('CHARACTER_SET_NAME');
        $this->db->sql_freeresult($result);
        
        // Si déjà en utf8mb4, la migration est déjà effectuée
        return ($charset === 'utf8mb4');
    }
    
    /**
     * Applique les changements de schéma via SQL direct.
     * C'est la seule méthode fiable pour changer le charset d'une colonne.
     */
    public function update_schema()
    {
        return [
            'custom' => [
                // On passe directement le "callable" (un tableau [objet, 'nom_methode'])
                [$this, 'convert_notification_data_to_utf8mb4']
            ],
        ];
    }
    
    /**
     * Fonction de conversion personnalisée.
     * Convertit la colonne notification_data en utf8mb4 pour supporter les emojis.
     */
    public function convert_notification_data_to_utf8mb4()
    {
        try {
            // IMPORTANT : On doit d'abord convertir en BLOB (binaire) pour ne pas perdre
            // les données lors de la conversion de charset. C'est la procédure recommandée.
            $sql_array = [
                // Étape 1 : Convertir en BLOB (binaire, pas de charset)
                "ALTER TABLE {$this->table_prefix}notifications 
                 MODIFY notification_data MEDIUMBLOB",
                
                // Étape 2 : Convertir de BLOB vers MEDIUMTEXT utf8mb4
                "ALTER TABLE {$this->table_prefix}notifications 
                 MODIFY notification_data MEDIUMTEXT 
                 CHARACTER SET utf8mb4 
                 COLLATE utf8mb4_bin",
            ];
            
            // Début de la transaction
            $this->db->sql_transaction('begin');

            foreach ($sql_array as $sql)
            {
                $this->db->sql_query($sql);
            }

            // Valider la transaction
            $this->db->sql_transaction('commit');
        } catch (\phpbb\db\sql_exception $e) {
            $this->db->sql_transaction('rollback');
            return false; // Indique à phpBB que la migration a échoué
        }

        return true;
    }
    
    /**
     * Met à jour les données de l'extension (version).
     */
    public function update_data()
    {
        return [
            // Met à jour le numéro de version dans la table de configuration.
            // Le nom de la variable de configuration est déduit du nom de l'extension.
            // Pour "bastien59960/reactions", la variable est "bastien59960_reactions_version".
            ['config.set', ['bastien59960_reactions_version', '1.0.3']],
        ];
    }

    /**
     * Indique explicitement qu'il n'y a pas d'action de réversion pour le schéma.
     * C'est un choix délibéré. La conversion de la colonne `notification_data`
     * en utf8mb4 est une amélioration non destructive pour la base de données
     * de phpBB. Il n'y a aucun avantage à revenir en arrière.
     * Retourner `false` empêche l'erreur "UNDEFINED_METHOD" lors d'une purge.
     */
    public function revert_schema()
    {
        return false;
    }

    /**
     * Indique explicitement qu'il n'y a pas de réversion de données.
     * Retourner `false` est la manière propre de signaler au migrateur d'ignorer cette étape.
     */
    public function revert_data()
    {
        return false;
    }
}