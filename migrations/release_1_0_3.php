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
 * caractères UTF-8 sur 4 bytes) ne pouvaient pas être stockés dans la colonne
 * `notification_data`, causant des erreurs SQL "Incorrect string value".
 *
 * La solution consiste à modifier cette colonne pour qu'elle utilise le jeu de
 * caractères `utf8mb4`, qui est la norme pour un support complet d'Unicode,
 * y compris les emojis. Cette modification est sûre et recommandée pour la
 * compatibilité avec le contenu moderne.
 */
namespace bastien59960\reactions\migrations;

class release_1_0_3 extends \phpbb\db\migration\migration
{
    /**
     * Dépendances de cette migration.
     * Elle ne s'exécutera qu'après la migration de la version 1.0.2.
     */
    public static function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_2'];
    }
    
    /**
     * Applique les changements de schéma.
     * Convertit la colonne notification_data en MTEXT_UNI pour supporter utf8mb4.
     */
    public function update_schema()
    {
        return [
            'change_columns' => [
                $this->table_prefix . 'notifications' => [
                    'notification_data' => ['MTEXT_UNI', null],
                ],
            ],
        ];
    }
    
    /**
     * Annule les changements de schéma (si nécessaire).
     * On laisse vide car revenir à latin1 casserait les emojis déjà stockés.
     */
    public function revert_schema()
    {
        return [];
    }
}