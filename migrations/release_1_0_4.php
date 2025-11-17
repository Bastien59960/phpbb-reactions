<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @version    1.0.4
 * @license    GNU General Public License, version 2 (GPL-2.0)
 *
 * Fichier : release_1_0_4.php 
 * Chemin : bastien59960/reactions/migrations/release_1_0_4.php
 *
 * Rôle :
 * Migration corrective pour créer les préférences de notification manquantes
 * pour tous les utilisateurs existants.
 */

namespace bastien59960\reactions\migrations;

class release_1_0_4 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Dépendances : s'exécute après la version 1.0.3
     */
    public static function depends_on()
    {
        return [
            '\bastien59960\reactions\migrations\release_1_0_3',
            '\phpbb\db\migration\data\v310\notifications',
        ];
    }
    
    /**
     * Vérifie si la migration est déjà installée
     */
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_version']) 
            && version_compare($this->config['bastien59960_reactions_version'], '1.0.4', '>=');
    }

    /**
     * Mise à jour des données : création des préférences manquantes
     */
    public function update_data()
    {
        return [
            // CORRECTION : Le callable doit être encapsulé dans un tableau supplémentaire.
            // Format correct : ['custom', [[$this, 'nom_de_la_methode']]]
            ['custom', [[$this, 'create_missing_notification_preferences']]],
            ['config.update', ['bastien59960_reactions_version', '1.0.4']],
        ];
    }

    /**
     * Pas de réversion pour cette migration (amélioration non destructive)
     */
    public function revert_data()
    {
        return [];
    }

    /**
     * Crée les préférences de notification manquantes pour tous les utilisateurs
     * 
     * Cette méthode vérifie chaque utilisateur actif et s'assure qu'il possède
     * les bonnes préférences pour les deux types de notifications de l'extension.
     */
    public function create_missing_notification_preferences()
    {
        // Récupérer les services nécessaires
        $notification_manager = $this->container->get('notification_manager');
        $user_loader = $this->container->get('user_loader');
        
        // Définir les types de notifications et leurs méthodes par défaut
        $notification_types = [
            'bastien59960.reactions.notification.type.reaction' => [
                'notification.method.board' => true,  // Activé par défaut
            ],
            'bastien59960.reactions.notification.type.reaction_email_digest' => [
                'notification.method.email' => true,  // Activé par défaut
            ],
        ];

        try {
            // Récupérer tous les utilisateurs actifs (non-bots, non-anonymes)
            $sql = 'SELECT user_id 
                    FROM ' . $this->table_prefix . 'users
                    WHERE user_type <> ' . USER_IGNORE . '
                      AND user_id <> ' . ANONYMOUS;
            $result = $this->db->sql_query($sql);
            $user_ids = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $user_ids[] = (int) $row['user_id'];
            }
            $this->db->sql_freeresult($result);

            if (empty($user_ids)) {
                return true;
            }

            // Pour chaque utilisateur
            foreach ($user_ids as $user_id) {
                // Pour chaque type de notification
                foreach ($notification_types as $item_type => $methods) {
                    // Pour chaque méthode (board/email)
                    foreach ($methods as $method => $default_enabled) {
                        // Vérifier si la préférence existe déjà
                        $sql = 'SELECT notify
                                FROM ' . $this->table_prefix . 'user_notifications
                                WHERE user_id = ' . (int) $user_id . '
                                  AND item_type = \'' . $this->db->sql_escape($item_type) . '\'
                                  AND method = \'' . $this->db->sql_escape($method) . '\'
                                  AND item_id = 0';
                        $result = $this->db->sql_query($sql);
                        $exists = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);

                        // Si la préférence n'existe pas, la créer
                        if (!$exists) {
                            $sql_insert = 'INSERT INTO ' . $this->table_prefix . 'user_notifications
                                (item_type, item_id, user_id, method, notify)
                                VALUES (
                                    \'' . $this->db->sql_escape($item_type) . '\',
                                    0,
                                    ' . (int) $user_id . ',
                                    \'' . $this->db->sql_escape($method) . '\',
                                    ' . ($default_enabled ? 1 : 0) . '
                                )';
                            $this->db->sql_query($sql_insert);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, on continue (non bloquant)
            // L'administrateur peut toujours relancer manuellement la migration
        }

        return true;
    }
}
