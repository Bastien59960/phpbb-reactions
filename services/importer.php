<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /service/importer.php
 * Rôle :
 * Ce service est responsable de l'importation des données depuis une ancienne
 * extension de "likes" (par exemple, phpbb/boardrules ou une autre) vers la
 * nouvelle structure de l'extension "Reactions".
 *
 * Il est conçu pour être appelé via une migration de base de données.
 */

namespace bastien59960\reactions\services;

use phpbb\db\driver\driver_interface;
use phpbb\log\log_interface;
use phpbb\user;
use phpbb\db\tools;
use Symfony\Component\Console\Output\OutputInterface;

class importer
{
    /** @var driver_interface */
    protected $db;

    /** @var log_interface */
    protected $log;

    /** @var user */
    protected $user;

    /** @var tools */
    protected $db_tools;

    /** @var string */
    protected $table_prefix;

    /** @var OutputInterface|null */
    protected $io;

    /**
     * Constructeur du service d'importation.
     *
     * @param driver_interface $db           Connexion à la base de données.
     * @param log_interface    $log          Service de journalisation.
     * @param user             $user         Service utilisateur.
     * @param tools            $db_tools     Outils de base de données.
     * @param string           $table_prefix Préfixe des tables phpBB.
     */
    public function __construct(
        driver_interface $db,
        log_interface $log,
        user $user,
        tools $db_tools,
        $table_prefix
    ) {
        $this->db = $db;
        $this->log = $log;
        $this->user = $user;
        $this->db_tools = $db_tools;
        $this->table_prefix = $table_prefix;
        $this->io = null;
    }

    /**
     * Définit l'interface de sortie pour les messages de console.
     *
     * @param OutputInterface $io
     */
    public function set_io(OutputInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Affiche un message à la fois dans la console (si disponible) et dans les logs admin.
     *
     * @param string $message Le message à afficher.
     * @param string $type    Type de message pour la console ('info', 'comment', 'question', 'error').
     */
    protected function output($message, $type = 'info')
    {
        if ($this->io) {
            $this->io->writeln("<$type>$message</$type>");
        }
        // Nous loguons uniquement les informations importantes dans le journal admin pour ne pas le surcharger.
        if ($type === 'info' || $type === 'error') {
            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_REACTIONS_IMPORT', time(), [$message]);
        }
    }

    /**
     * Exécute le processus d'importation.
     */
    public function run()
    {
        $this->output('Démarrage du service d\'importation pour l\'extension Reactions.', 'info');

        // Nom de la table de l'ancienne extension de "likes".
        // À ADAPTER si le nom est différent.
        $old_likes_table = $this->table_prefix . 'likes';

        // 1. Vérifier si l'ancienne table existe
        if (!$this->db_tools->sql_table_exists($old_likes_table)) {
            $this->output("L'ancienne table '$old_likes_table' n'a pas été trouvée. Aucune importation n'est nécessaire.", 'comment');
            $this->output('Processus d\'importation terminé.', 'info');
            return;
        }

        $this->output("Ancienne table '$old_likes_table' détectée. Début de la migration...", 'info');

        // 2. Définir la table de destination
        $reactions_table = $this->table_prefix . 'post_reactions';

        // 3. Compter le nombre d'entrées à migrer
        $sql = 'SELECT COUNT(*) as total FROM ' . $old_likes_table;
        $result = $this->db->sql_query($sql);
        $total_likes = (int) $this->db->sql_fetchfield('total');
        $this->db->sql_freeresult($result);

        if ($total_likes === 0) {
            $this->output('Aucun "like" à importer depuis l\'ancienne table.', 'comment');
            $this->output('Processus d\'importation terminé.', 'info');
            return;
        }

        $this->output("Nombre total de 'likes' à importer : $total_likes", 'question');

        // 4. Préparer la requête de sélection depuis l'ancienne table
        // Hypothèses sur la structure de l'ancienne table :
        // - post_id : ID du message
        // - user_id : ID de l'utilisateur qui a "liké"
        // - like_time : Timestamp du "like"
        // À ADAPTER si les noms de colonnes sont différents.
        $sql_select = 'SELECT post_id, user_id, like_time
            FROM ' . $old_likes_table;

        $result = $this->db->sql_query($sql_select);

        $imported_count = 0;
        $skipped_count = 0;

        // 5. Parcourir les anciennes données et les insérer dans la nouvelle table
        while ($row = $this->db->sql_fetchrow($result)) {
            // On utilise l'emoji "pouce levé" par défaut pour les anciens "likes".
            $reaction_data = [
                'post_id'           => (int) $row['post_id'],
                'user_id'           => (int) $row['user_id'],
                'reaction_emoji'    => '👍',
                'reaction_time'     => (int) $row['like_time'],
                'reaction_notified' => 1, // On considère les anciennes réactions comme déjà "vues".
            ];

            // Vérifier si une réaction identique (même utilisateur, même post, même emoji) n'existe pas déjà.
            // C'est une sécurité si la migration est lancée plusieurs fois par erreur.
            $sql_check = 'SELECT reaction_id FROM ' . $reactions_table . '
                WHERE post_id = ' . $reaction_data['post_id'] . '
                AND user_id = ' . $reaction_data['user_id'] . "
                AND reaction_emoji = '" . $this->db->sql_escape($reaction_data['reaction_emoji']) . "'";
            $check_result = $this->db->sql_query($sql_check);
            $exists = $this->db->sql_fetchrow($check_result);
            $this->db->sql_freeresult($check_result);

            if ($exists) {
                $skipped_count++;
                continue;
            }

            // Insertion dans la nouvelle table
            $sql_insert = 'INSERT INTO ' . $reactions_table . ' ' . $this->db->sql_build_array('INSERT', $reaction_data);
            
            try {
                $this->db->sql_query($sql_insert);
                $imported_count++;
            } catch (\phpbb\db\sql_error $e) {
                $this->output("Erreur lors de l'importation du like pour le post " . $reaction_data['post_id'] . ": " . $e->getMessage(), 'error');
                $skipped_count++;
            }
        }
        $this->db->sql_freeresult($result);

        // 6. Afficher le résumé
        $this->output("Importation terminée.", 'info');
        $this->output("  - Réactions importées avec succès : $imported_count", 'info');
        $this->output("  - Réactions ignorées (doublons ou erreurs) : $skipped_count", 'comment');

        // Optionnel : Supprimer l'ancienne table après migration réussie.
        // C'est une bonne pratique mais peut être risqué. Laisser commenté par défaut.
        /*
        if ($skipped_count === 0 && $imported_count === $total_likes) {
            $this->output("Suppression de l'ancienne table '$old_likes_table'...", 'comment');
            $this->db_tools->sql_table_drop($old_likes_table);
        }
        */

        return [];
    }
}

?>