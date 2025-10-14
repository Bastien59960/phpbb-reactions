<?php
/**
 * Fichier : controller/test.php — bastien59960/reactions/controller/test.php
 *
 * Contrôleur de test pour l'extension Reactions.
 *
 * Ce fichier peut être utilisé pour des expérimentations, des tests de développement ou des vérifications de fonctionnalités spécifiques liées aux réactions.
 * Il n'est pas utilisé en production et ne fait pas partie du flux métier principal de l'extension.
 *
 * Points clés :
 *   - Permet de tester rapidement des fonctions ou des intégrations
 *   - Sert de base pour des essais ou des débogages ponctuels
 *
 * Ce fichier peut être supprimé ou ignoré en production.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class test
{
    protected $db;
    protected $user;
    protected $reactions_table;
    protected $posts_table;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        $reactions_table,
        $posts_table
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->reactions_table = $reactions_table;
        $this->posts_table = $posts_table;
    }

    /**
     * Test the reactions functionality
     */
    public function handle()
    {
        $test_results = [
            'extension_loaded' => true,
            'database_connection' => $this->test_database_connection(),
            'table_exists' => $this->test_table_exists(),
            'utf8mb4_support' => $this->test_utf8mb4_support(),
            'common_emojis' => $this->get_common_emojis(),
            'sample_reactions' => $this->get_sample_reactions(),
        ];

        return new JsonResponse($test_results);
    }

    private function test_database_connection()
    {
        try {
            $this->db->sql_query("SELECT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function test_table_exists()
    {
        try {
            $sql = "SHOW TABLES LIKE '" . $this->reactions_table . "'";
            $result = $this->db->sql_query($sql);
            $exists = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            return (bool) $exists;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function test_utf8mb4_support()
    {
        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function get_common_emojis()
    {
        return ['👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'];
    }

    private function get_sample_reactions()
    {
        try {
            $sql = 'SELECT reaction_emoji, COUNT(*) as count 
                    FROM ' . $this->reactions_table . ' 
                    GROUP BY reaction_emoji 
                    ORDER BY count DESC 
                    LIMIT 10';
            $result = $this->db->sql_query($sql);
            
            $reactions = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $reactions[$row['reaction_emoji']] = (int) $row['count'];
            }
            $this->db->sql_freeresult($result);
            
            return $reactions;
        } catch (\Exception $e) {
            return [];
        }
    }
}