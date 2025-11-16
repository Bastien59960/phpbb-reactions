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
    public function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_2'];
    }

    /**
     * Applique les changements de schéma.
     * Utilise la méthode portable de phpBB pour modifier la colonne.
     *
     * La clé 'change_columns' indique à phpBB que nous voulons modifier la
     * structure d'une colonne existante.
     */
    public function update_schema()
    {
        return [
            'change_columns'    => [
                $this->table_prefix . 'notifications'    => [
                    // MTEXT_UNI est un type de données portable de phpBB.
                    // Il se traduit par 'MEDIUMTEXT' avec le jeu de caractères et la collation
                    // Unicode les plus complets disponibles sur le système de base de données
                    // (généralement utf8mb4 et utf8mb4_unicode_ci sur MySQL).
                    // C'est la méthode correcte pour garantir la compatibilité avec les emojis.
                    'notification_data'    => ['MTEXT_UNI', null],
                ],
            ],
        ];
    }

    /**
     * Annule les changements de schéma.
     */
    public function revert_schema()
    {
        // Le retour en arrière (revert) est intentionnellement laissé vide.
        // Revenir de `utf8mb4` à un jeu de caractères plus ancien (comme `utf8`) est une
        // opération "lossy" (avec perte de données). Si des emojis ont déjà été
        // stockés, cette conversion échouerait ou corromprait les données.
        // Pour préserver l'intégrité des données, il est plus sûr de ne pas
        // proposer de chemin de retour automatique pour ce type de mise à niveau.
        return [];
    }
}