<?php
/**
 * Fichier : test.php
 * Chemin : bastien59960/reactions/controller/test.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce contrôleur fournit un point de terminaison de diagnostic simple, accessible
 * via une URL, pour l'extension. Il effectue une série de vérifications de base
 * (connexion BDD, existence de la table, support UTF8MB4) et retourne les
 * résultats au format JSON.
 *
 * @input void (Requête GET)
 * @output \Symfony\Component\HttpFoundation\JsonResponse Un rapport de diagnostic JSON.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class test
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var string */
    protected $reactions_table;

    /**
     * Constructeur
     *
     * @param \phpbb\db\driver\driver_interface $db
     * @param string $reactions_table
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        $reactions_table
    ) {
        $this->db = $db;
        $this->reactions_table = $reactions_table;
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