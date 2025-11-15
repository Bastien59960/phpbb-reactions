<?php
/**
 * Fichier : notification_task.php
 * Chemin : bastien59960/reactions/cron/notification_task.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Tâche cron principale pour l'envoi des résumés de réactions par e-mail.
 * Cette tâche s'exécute périodiquement, collecte les nouvelles réactions,
 * les groupe par utilisateur et par message, et envoie un e-mail de résumé.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\cron;

if (!defined('IN_PHPBB'))
{
    exit;
}

use messenger;

class notification_task extends \phpbb\cron\task\base
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\notification\manager */
    protected $notification_manager;

    /** @var \phpbb\user_loader */
    protected $user_loader;

    /** @var \phpbb\language\language */
    protected $language;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var string Nom de la table des réactions */
    protected $post_reactions_table;

    /** @var string Chemin racine phpBB */
    protected $phpbb_root_path;

    /** @var string Extension des fichiers php */
    protected $php_ext;

    /** @var string Préfixe des tables phpBB */
    protected $table_prefix;

    /** @var string Chemin du fichier de log pour le CLI */
    protected $log_file;

    /**
     * Constructeur
     *
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\config\config $config
     * @param \phpbb\notification\manager $notification_manager
     * @param \phpbb\user_loader $user_loader
     * @param \phpbb\language\language $language
     * @param \phpbb\template\template $template
     * @param string $post_reactions_table Nom de la table des réactions.
     * @param string $phpbb_root_path Chemin racine de phpBB.
     * @param string $php_ext Extension des fichiers PHP.
     * @param string $table_prefix Préfixe des tables.
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\config\config $config,
        \phpbb\notification\manager $notification_manager,
        \phpbb\user_loader $user_loader,
        \phpbb\language\language $language,
        \phpbb\template\template $template,
        $post_reactions_table,
        $phpbb_root_path,
        $php_ext,
        $table_prefix
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->notification_manager = $notification_manager;
        $this->user_loader = $user_loader;
        $this->language = $language;
        $this->template = $template;
        $this->post_reactions_table = $post_reactions_table;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->table_prefix = $table_prefix;
        
        // Définir le fichier de log pour le CLI
        $this->log_file = $this->phpbb_root_path . 'cache/reactions_cron.log';
    }

    /**
     * Log un message (compatible CLI et web)
     *
     * @param string $message Message à logger.
     * @return void
     */
    protected function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        
        // Écrire dans error_log (visible dans les logs Apache/PHP)
        error_log($message);
        
        // Écrire aussi dans un fichier dédié (visible depuis CLI)
        @file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Méthode principale exécutée par le cron
     *
     * Logique :
     * 1. Récupère toutes les réactions non notifiées qui ont dépassé le seuil anti-spam.
     * 2. Regroupe ces réactions par auteur de message.
     * 3. Pour chaque auteur, envoie un e-mail de résumé (digest) contenant toutes ses nouvelles réactions.
     */
    public function run()
    {
        // CORRECTION : Relire la configuration au moment de l'exécution pour garantir la fraîcheur de la valeur.
        $spam_minutes = (int) $this->config['bastien59960_reactions_spam_time'];
        if ($spam_minutes <= 0)
        {
            error_log('[Reactions Cron] Run skipped (spam_minutes <= 0).');
            return;
        }

        $spam_delay_seconds = $spam_minutes * 60;

        if (!function_exists('generate_board_url'))
        {
            include_once($this->phpbb_root_path . 'includes/functions.' . $this->php_ext);
        }

        // CORRECTION : Utiliser $_SERVER['REQUEST_TIME'] au lieu de time() pour être
        // compatible avec les environnements de test où la date système est modifiée.
        $current_time = isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time();
        $threshold_timestamp = $current_time - $spam_delay_seconds;
        $run_start = microtime(true);

        $this->log('═══════════════════════════════════════════════════════════════');
        $this->log('🚀 [Reactions Cron] DÉBUT DU RUN');
        $this->log('═══════════════════════════════════════════════════════════════');
        $this->log("📊 Configuration: interval={$spam_minutes} min, threshold=" . date('Y-m-d H:i:s', $threshold_timestamp));
        $this->log("⏰ Timestamp actuel: " . date('Y-m-d H:i:s', $current_time));

        // CORRECTION CRITIQUE : Forcer utf8mb4 pour la connexion AVANT la requête
        // Cela garantit que les emojis sont correctement lus depuis la base
        $this->log('🔧 Étape 1: Configuration du charset de connexion...');
        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
            $this->log('✅ SET NAMES utf8mb4 exécuté avec succès');
            
            // Vérifier que le charset a bien été appliqué
            $charset_check = $this->db->sql_query("SHOW VARIABLES LIKE 'character_set_connection'");
            $charset_row = $this->db->sql_fetchrow($charset_check);
            $this->db->sql_freeresult($charset_check);
            if ($charset_row) {
                $charset_value = $charset_row['Value'];
                $this->log("📋 Charset de connexion vérifié: {$charset_value}");
                if ($charset_value !== 'utf8mb4') {
                    $this->log("⚠️  ALERTE: Le charset n'est PAS utf8mb4! Les emojis seront corrompus!");
                } else {
                    $this->log("✅ Charset utf8mb4 confirmé - les emojis devraient être correctement lus");
                }
            }
        } catch (\Throwable $e) {
            $this->log("❌ ERREUR lors du SET NAMES utf8mb4: " . $e->getMessage());
        }

        $sql = 'SELECT r.reaction_id, r.post_id, r.user_id AS reacter_id, r.reaction_emoji, r.reaction_time,
                       p.poster_id AS author_id, p.topic_id, p.post_subject,
                       ru.username AS reacter_name,
                       au.username AS author_name, au.user_email AS author_email, au.user_lang AS author_lang
                FROM ' . $this->post_reactions_table . ' r
                LEFT JOIN ' . POSTS_TABLE . ' p ON (r.post_id = p.post_id)
                LEFT JOIN ' . USERS_TABLE . ' ru ON (r.user_id = ru.user_id)
                LEFT JOIN ' . USERS_TABLE . ' au ON (p.poster_id = au.user_id)
                WHERE r.reaction_notified = 0
                  AND r.reaction_time <= ' . (int) $threshold_timestamp . '
                ORDER BY au.user_id, p.post_id, r.reaction_time ASC';

        $this->log('🔍 Étape 2: Exécution de la requête SQL pour récupérer les réactions...');
        $result = $this->db->sql_query($sql);
        $total_reactions_found = $this->db->sql_affectedrows($result);
        
        $this->log("📦 Résultat SQL: {$total_reactions_found} réaction(s) non notifiée(s) trouvée(s)");
        $this->log('───────────────────────────────────────────────────────────────');

        $by_author = [];
        $reactions_to_cleanup = []; // Pour les réactions orphelines ou auto-infligées
        $reaction_count = 0;

        $this->log('🔄 Étape 3: Traitement de chaque réaction...');
        while ($row = $this->db->sql_fetchrow($result))
        {
            $reaction_count++;
            $this->log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->log("📝 Réaction #{$reaction_count} (reaction_id: {$row['reaction_id']})");
            $reaction_id   = (int) $row['reaction_id'];
            $post_id       = (int) $row['post_id'];
            $author_id     = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $author_name   = $row['author_name'] ?? '';
            $author_email  = $row['author_email'] ?? '';
            $author_lang   = $row['author_lang'] ?? '';
            $reacter_id    = (int) ($row['reacter_id'] ?? 0);
            $reacter_name  = $row['reacter_name'] ?? '';
            
            // CORRECTION CRITIQUE : Récupérer l'emoji directement depuis la DB sans conversion
            // Le problème peut venir de sql_fetchrow() qui pourrait corrompre l'emoji
            // On utilise directement la valeur brute depuis le résultat
            $emoji_raw = isset($row['reaction_emoji']) ? $row['reaction_emoji'] : '';
            
            // DEBUG : Logger l'emoji brut AVANT toute manipulation avec affichage visuel
            $emoji_hex = bin2hex($emoji_raw);
            $emoji_length = strlen($emoji_raw);
            $emoji_is_utf8 = mb_check_encoding($emoji_raw, 'UTF-8');
            $emoji_display = $emoji_raw ?: '(VIDE)';
            
            $this->log("  📍 Emoji brut depuis sql_fetchrow():");
            $this->log("     └─ Affichage visuel: [{$emoji_display}]");
            $this->log("     └─ Hex: {$emoji_hex}");
            $this->log("     └─ Longueur: {$emoji_length} octet(s)");
            $this->log("     └─ UTF-8 valide: " . ($emoji_is_utf8 ? '✅ OUI' : '❌ NON'));
            
            // Si l'emoji est vide ou corrompu, essayer de le récupérer directement avec une requête séparée
            if (empty($emoji_raw) || $emoji_raw === '?' || !$emoji_is_utf8)
            {
                $this->log("  ⚠️  Emoji semble corrompu! Tentative de récupération directe depuis la DB...");
                $direct_sql = 'SELECT reaction_emoji FROM ' . $this->post_reactions_table . ' WHERE reaction_id = ' . (int) $reaction_id;
                $direct_result = $this->db->sql_query($direct_sql);
                $direct_row = $this->db->sql_fetchrow($direct_result);
                $this->db->sql_freeresult($direct_result);
                
                if ($direct_row && !empty($direct_row['reaction_emoji']))
                {
                    $emoji_raw = $direct_row['reaction_emoji'];
                    $direct_hex = bin2hex($emoji_raw);
                    $this->log("  ✅ Récupération directe réussie:");
                    $this->log("     └─ Affichage visuel: [{$emoji_raw}]");
                    $this->log("     └─ Hex: {$direct_hex}");
                } else {
                    $this->log("  ❌ Récupération directe échouée - emoji toujours corrompu");
                }
            }
            
            $emoji = $emoji_raw;
            $r_time        = (int) ($row['reaction_time'] ?? 0);
            $post_subject  = $row['post_subject'] ?? '';

            if ($author_id <= 0)
            {
                $reactions_to_cleanup[] = $reaction_id;
                continue;
            }

            if ($author_id === $reacter_id)
            {
                $reactions_to_cleanup[] = $reaction_id;
                continue;
            }

            if (!isset($by_author[$author_id]))
            {
                $by_author[$author_id] = [
                    'author_id'    => $author_id,
                    'author_name'  => $author_name,
                    'author_email' => $author_email,
                    'author_lang'  => $author_lang,
                    'posts'        => [],
                    'mark_ids'     => [],
                ];
            }

            $subject_plain = ($post_subject !== '') ? html_entity_decode(strip_tags($post_subject), ENT_QUOTES, 'UTF-8') : $this->language->lang('NO_SUBJECT');
            // CORRECTION UTF-8 : Normaliser le sujet en UTF-8
            $subject_plain = $this->normalize_utf8($subject_plain);
            $post_url_absolute = generate_board_url() . "/viewtopic.{$this->php_ext}?p={$post_id}#p{$post_id}";
            $profile_url_absolute = generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$reacter_id}";

            if (!isset($by_author[$author_id]['posts'][$post_id]))
            {
                $by_author[$author_id]['posts'][$post_id] = [
                    'SUBJECT_PLAIN'     => $subject_plain,
                    'POST_URL_ABSOLUTE' => $post_url_absolute,
                    'reactions'         => [],
                ];
            }

            // CORRECTION UTF-8 : Normaliser l'emoji et le nom du réacteur en UTF-8
            $this->log("  🔄 Normalisation de l'emoji...");
            $emoji_before_normalize = $emoji;
            $emoji_normalized = $this->normalize_emoji($emoji);
            
            $this->log("     └─ Avant normalisation: [{$emoji_before_normalize}] (hex: " . bin2hex($emoji_before_normalize) . ")");
            $this->log("     └─ Après normalisation:  [{$emoji_normalized}] (hex: " . bin2hex($emoji_normalized) . ")");
            
            if ($emoji_before_normalize !== $emoji_normalized) {
                $this->log("     ⚠️  L'emoji a été modifié lors de la normalisation!");
            } else {
                $this->log("     ✅ L'emoji est inchangé après normalisation");
            }
            
            $reacter_name_normalized = $this->normalize_utf8($reacter_name);
            $this->log("  👤 Réacteur: {$reacter_name_normalized} (user_id: {$reacter_id})");
            $this->log("  📧 Auteur: {$author_name} (user_id: {$author_id}, email: {$author_email})");

            $by_author[$author_id]['posts'][$post_id]['reactions'][] = [
                'REACTION_ID'          => $reaction_id,
                'REACTER_ID'           => $reacter_id,
                'REACTER_NAME'         => $reacter_name_normalized,
                'EMOJI'                => $emoji_normalized, // Garder l'emoji brut pour le fallback
                'EMOJI_ORIGINAL'       => $emoji, // Garder l'original pour debug
                'TIME'                 => $r_time,
                'TIME_FORMATTED'       => date('d/m/Y H:i', $r_time),
                'PROFILE_URL_ABSOLUTE' => $profile_url_absolute,
            ];

            $by_author[$author_id]['mark_ids'][] = $reaction_id;
            $this->log("  ✅ Réaction ajoutée au groupe de l'auteur #{$author_id}");
        }

        $this->db->sql_freeresult($result);
        
        $this->log('───────────────────────────────────────────────────────────────');
        $this->log("📊 Étape 4: Résumé du groupement");
        $this->log("   └─ Total réactions traitées: {$reaction_count}");
        $this->log("   └─ Auteurs uniques: " . count($by_author));
        $this->log("   └─ Réactions à nettoyer (orphelines/auto): " . count($reactions_to_cleanup));

        if (empty($by_author))
        {
            $this->log('❌ Aucune réaction à traiter après groupement. Arrêt.');
            // S'il n'y a pas de réactions valides mais des réactions à nettoyer, on le fait.
            if (!empty($reactions_to_cleanup))
            {
                $this->log('🧹 Nettoyage de ' . count($reactions_to_cleanup) . ' réaction(s) orpheline(s)/auto-infligée(s).');
                $this->mark_reactions_as_handled($reactions_to_cleanup);
            }
            return;
        }
        
        $this->log('✅ Groupement terminé - ' . count($by_author) . ' auteur(s) à notifier');

        include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);

        $processed_authors = 0;
        $processed_reactions = 0;
        $sent_reactions = 0;
        $skipped_no_email = 0;
        $skipped_pref = 0;
        $skipped_empty = 0;
        $skipped_failed = 0;

        $this->log('═══════════════════════════════════════════════════════════════');
        $this->log('📧 Étape 5: Envoi des emails aux auteurs');
        $this->log('═══════════════════════════════════════════════════════════════');
        
        foreach ($by_author as $author_id => $data)
        {
            $this->log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->log("👤 Traitement de l'auteur #{$author_id}: {$data['author_name']}");
            $this->log("   └─ Email: {$data['author_email']}");
            $this->log("   └─ Langue: {$data['author_lang']}");
            $this->log("   └─ Nombre de réactions: " . count($data['mark_ids']));
            
            // Afficher toutes les réactions avec leurs emojis
            $this->log("   └─ Détail des réactions:");
            foreach ($data['posts'] as $post_id => $post_data) {
                $this->log("      📝 Post #{$post_id}: \"{$post_data['SUBJECT_PLAIN']}\"");
                if (isset($post_data['reactions']) && is_array($post_data['reactions'])) {
                    foreach ($post_data['reactions'] as $idx => $reaction) {
                        $emoji_display = $reaction['EMOJI'] ?? $reaction['EMOJI_ORIGINAL'] ?? '?';
                        $this->log("         " . ($idx + 1) . ". Emoji: [{$emoji_display}] par {$reaction['REACTER_NAME']} (hex: " . bin2hex($emoji_display) . ")");
                    }
                }
            }

            $processed_authors++;
            $reaction_total_for_author = isset($data['mark_ids']) ? count($data['mark_ids']) : 0;
            $processed_reactions += $reaction_total_for_author;
            $author_email = $data['author_email'];
            $author_name  = $data['author_name'] ?: 'Utilisateur';
            $author_lang  = $data['author_lang'] ?: 'fr';

            if (empty($author_email))
            {
                $skipped_no_email += $reaction_total_for_author;
                error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (no email).');
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email === true)
            {
                $skipped_pref += $reaction_total_for_author;
                error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (email preference disabled).');
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            if (empty($data['posts']))
            {
                $empty_posts_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (no valid posts).';
                error_log($empty_posts_message);
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            $data['posts'] = array_values($data['posts']);
            $since_time_formatted = date('d/m/Y H:i', $threshold_timestamp);

            // CORRECTION : Encapsuler l'envoi dans un try...catch pour éviter qu'une erreur ne bloque toute la boucle.
            try
            {
                $result = $this->send_digest_email($data, $since_time_formatted);
                error_log("[CRON] Résultat de send_digest_email pour l'auteur #{$author_id} : " . json_encode($result));
            }
            catch (\Throwable $e)
            {
                error_log("[CRON][ERREUR MAIL] Exception lors de l'envoi pour l'auteur #{$author_id} : " . $e->getMessage());
                // S'assurer que $result est un tableau d'échec pour que la logique continue
                $result = ['status' => 'failed', 'error' => $e->getMessage()];
            }

            if (!is_array($result))
            {
                $result = $result ? ['status' => 'sent'] : ['status' => 'failed'];
            }

            if ($result['status'] === 'sent')
            {
                $sent_reactions += $reaction_total_for_author;
                $this->mark_reactions_as_handled($data['mark_ids']);
            }
            elseif ($result['status'] === 'skipped_empty')
            {
                $skipped_empty += $reaction_total_for_author;
                $this->mark_reactions_as_handled($data['mark_ids']);
            }
            else
            {
                $skipped_failed += $reaction_total_for_author;
                error_log('[Reactions Cron] Send failed for user_id ' . $author_id . ' (status=' . $result['status'] . ').');
            }
        }

        // Nettoyer toutes les réactions orphelines/auto-infligées en une seule fois
        if (!empty($reactions_to_cleanup))
        {
            error_log('[Reactions Cron] Cleaning up ' . count($reactions_to_cleanup) . ' orphan/self reactions.');
            $this->mark_reactions_as_handled($reactions_to_cleanup);
        }

        // Utiliser le même timestamp que celui utilisé pour le calcul du seuil
        // pour la mise à jour de la dernière exécution.
        $this->config->set('bastien59960_reactions_cron_last_run', $current_time);

        $duration_ms = (microtime(true) - $run_start) * 1000;

        $summary_message = sprintf(
            '[Reactions Cron] Run complete in %.1fms (authors=%d, reactions=%d, sent=%d, no_email=%d, pref_off=%d, empty=%d, failed=%d)',
            $duration_ms,
            $processed_authors,
            $processed_reactions,
            $sent_reactions,
            $skipped_no_email,
            $skipped_pref,
            $skipped_empty,
            $skipped_failed
        );

        error_log($summary_message);
    }

    /**
     * Marque les réactions comme notifiées
     *
     * @param array $ids Tableau d'IDs de réactions à marquer.
     * @return void
     */
    protected function mark_reactions_as_handled(array $ids)
    {
        if (empty($ids))
        {
            return;
        }

        $ids = array_map('intval', $ids);
        $sql = 'UPDATE ' . $this->post_reactions_table . '
                SET reaction_notified = 1
                WHERE ' . $this->db->sql_in_set('reaction_id', $ids);
        $this->db->sql_query($sql);
    }

    /**
     * Récupère la préférence disable_cron_email pour un utilisateur
     *
     * @param int $user_id ID de l'utilisateur.
     * @return bool Retourne `true` si l'utilisateur a désactivé les e-mails,
     *              `false` sinon ou en cas d'erreur.
     */
    protected function get_user_disable_cron_email_pref($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0)
        {
            return false;
        }

        try
        {
            $sql = 'SELECT user_reactions_cron_email
                    FROM ' . USERS_TABLE . '
                    WHERE user_id = ' . (int) $user_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row)
            {
                return false;
            }

            return ((int) $row['user_reactions_cron_email']) === 0;
        }
        catch (\phpbb\db\exception $e)
        {
            error_log('[Reactions Cron] DB Error checking pref for user ' . $user_id . ': ' . $e->getMessage() . '. Assuming pref is ON.');
            return false;
        }
    }

    /**
     * Indique si la tâche doit s'exécuter
     *
     * La tâche s'exécute si le temps écoulé depuis la dernière exécution
     * est supérieur à l'intervalle défini dans la configuration.
     *
     * @return bool
     */
    public function should_run()
    {
        // CORRECTION : Simplification radicale. La tâche doit TOUJOURS être considérée comme "prête".
        // La logique de savoir s'il y a du travail à faire est déplacée dans la méthode run().
        // Cela évite les problèmes de cache de configuration et garantit que le cron est toujours
        // exécutable par `cron:run`, ce qui est essentiel pour le script de purge.
        return true;
    }

    /**
     * Construit et envoie un e-mail récapitulatif à un utilisateur
     *
     * @param array $data Données de l'auteur et de ses réactions groupées.
     * @param string $since_time_formatted Date formatée du seuil de temps.
     * @return array Résultat de l'envoi (ex: ['status' => 'sent']).
     */
    protected function send_digest_email(array $data, string $since_time_formatted)
    {
        $author_id    = (int) $data['author_id'];
        $author_email = $data['author_email'];
        $author_name  = $data['author_name'] ?: 'Utilisateur';
        $author_lang  = $data['author_lang'] ?: 'fr';

        $log_prefix = '[Reactions Cron]';

        if (empty($data['posts']))
        {
            error_log("$log_prefix Aucun contenu pour user_id $author_id");
            return ['status' => 'skipped_empty'];
        }

        // CORRECTION : Le bloc try...catch est essentiel pour gérer les erreurs d'envoi
        // sans faire planter toute la boucle du cron.
        try
        {
            // CORRECTION CRITIQUE : Changer la langue AVANT d'instancier le messenger.
            // Le messenger charge les fichiers de langue dans son constructeur.
            // Si on change la langue après, il est trop tard.
            $this->language->set_user_language($author_lang);
            $this->language->add_lang('email', 'bastien59960/reactions');
            $this->language->add_lang('common', 'bastien59960/reactions');

            // Utiliser le messenger natif de phpBB avec l'encodage forcé pour les emojis.
            $this->log("  📤 Tentative d'envoi d'email à {$author_name} ({$author_email})");
            $this->log("     └─ Nombre de réactions à inclure: " . count($data['mark_ids']));

            $email_sent = $this->send_email_with_messenger($data, $author_email, $author_name, $author_lang);

            if ($email_sent)
            {
                $this->log("  ✅ Email envoyé avec succès!");
                return ['status' => 'sent'];
            }

            $this->log("  ❌ Échec de l'envoi de l'email.");

            return [
                'status' => 'failed',
                'error' => 'send_email_with_messenger returned false',
            ];
        }
        catch (\Throwable $e)
        {
            error_log("$log_prefix Exception for user_id $author_id: " . $e->getMessage());
            error_log("$log_prefix File: " . $e->getFile() . " | Line: " . $e->getLine());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoie l'email en utilisant le messenger natif de phpBB.
     * 
     * @param array $data Données de l'auteur et de ses réactions groupées.
     * @param string $author_email Email du destinataire.
     * @param string $author_name Nom du destinataire.
     * @param string $author_lang Langue du destinataire.
     * @return bool True si l'email a été envoyé avec succès, false sinon.
     */
    protected function send_email_with_messenger(array $data, string $author_email, string $author_name, string $author_lang)
    {
        $this->log("      [Messenger] Utilisation du messenger phpBB");
        
        try
        {
            if (!class_exists('messenger'))
            {
                $this->log("     📦 [Messenger Fallback] Chargement de la classe messenger...");
                include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
            }

            $this->log("     🏗️  [Messenger] Création de l'instance messenger...");
            $messenger = new \messenger(false);
            
            // FORCER QUOTED-PRINTABLE : C'est la correction qui permet aux emojis de passer.
            $messenger->headers('Content-Transfer-Encoding: quoted-printable');
            $this->log("     🧪 [Messenger] Forçage de l'encodage 'quoted-printable' pour le support des emojis.");
            
            $author_name_utf8 = $this->normalize_utf8($author_name);
            $sitename_utf8 = $this->normalize_utf8($this->config['sitename']);
            
            $this->log("     📧 [Messenger] Configuration destinataire: {$author_name_utf8} <{$author_email}>");
            $messenger->to($author_email, $author_name_utf8);
            
            $subject = '🚀 ' . $this->language->lang('REACTIONS_DIGEST_SUBJECT') . ' ✨';
            $subject_utf8 = $this->normalize_utf8($subject);
            $this->log("     📌 [Messenger] Sujet: {$subject_utf8}");
            $messenger->subject($subject_utf8);

            $template_path = '@bastien59960_reactions/email/reaction_digest';
            $this->log("     📄 [Messenger Fallback] Template: {$template_path}");
            $messenger->template($template_path, $author_lang);

            // Assigner les variables globales
            $messenger->assign_vars([
                'HELLO_USERNAME'   => '✨ ' . $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_HELLO'), $author_name_utf8)),
                'DIGEST_INTRO'     => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_INTRO'), $sitename_utf8)),
                'DIGEST_SIGNATURE' => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $sitename_utf8)),
                'DIGEST_FOOTER'    => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_FOOTER')),
                'UNSUBSCRIBE_TEXT' => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE')),
                'SITENAME'         => '✅ ' . $sitename_utf8,
                'BOARD_URL'        => generate_board_url(),
                'U_UCP'            => generate_board_url() . "/ucp.{$this->php_ext}?i=ucp_notifications",
                'U_USER_PROFILE'   => generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$data['author_id']}",
                'L_REACTION_FROM'  => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_REACTION_FROM')),
                'L_ON_DATE'        => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_ON_DATE')),
                'L_VIEW_POST'      => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_VIEW_POST')),
                'REACTIONS_DIGEST_SUBJECT' => $subject_utf8,
            ]);

            // Assigner les blocs de posts
            foreach ($data['posts'] as $post_data)
            {
                $post_title_utf8 = $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_POST_TITLE'), $post_data['SUBJECT_PLAIN']));
                $messenger->assign_block_vars('posts', [
                    'POST_TITLE'        => $post_title_utf8,
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                if (isset($post_data['reactions']) && is_array($post_data['reactions']))
                {
                    $this->log("        └─ Traitement de " . count($post_data['reactions']) . " réaction(s) pour ce post");
                    foreach ($post_data['reactions'] as $idx => $reaction) {
                        // Utiliser l'emoji original si disponible, sinon celui normalisé
                        $emoji_to_convert = $reaction['EMOJI_ORIGINAL'] ?? $reaction['EMOJI'] ?? '?';
                        $emoji_utf8 = $this->normalize_emoji($emoji_to_convert);
                        
                        $this->log("           Réaction #" . ($idx + 1) . ":");
                        $this->log("              └─ Emoji à convertir: [{$emoji_to_convert}] (hex: " . bin2hex($emoji_to_convert) . ")");
                        $this->log("              └─ Emoji normalisé: [{$emoji_utf8}] (hex: " . ($emoji_utf8 !== '?' ? bin2hex($emoji_utf8) : '3f') . ")");
                        
                        // On envoie l'emoji brut, car 'quoted-printable' le gère.
                        $emoji_display = $emoji_utf8;
                        
                        $this->log("              └─ Emoji final (texte): {$emoji_display}");
                        
                        $reacter_name_utf8 = $this->normalize_utf8($reaction['REACTER_NAME']);
                        
                        $messenger->assign_block_vars('posts.reactions', [
                            'EMOJI'                => $emoji_display,
                            'REACTER_NAME'         => $reacter_name_utf8,
                            'TIME_FORMATTED'       => $reaction['TIME_FORMATTED'],
                            'PROFILE_URL_ABSOLUTE' => $reaction['PROFILE_URL_ABSOLUTE'],
                        ]);
                    }
                }
            }

            $this->log("     📤 [Messenger] Envoi de l'email...");
            $send_result = $messenger->send(NOTIFY_EMAIL);
            
            if ($send_result)
            {
                $this->log("     ✅ [Messenger] Email envoyé avec succès.");
            } else {
                $this->log("     ❌ [Messenger Fallback] Échec de l'envoi");
            }
            
            return $send_result;
        }
        catch (\Exception $e)
        {
            error_log('[Reactions Cron] Messenger exception: ' . $e->getMessage());
            error_log('[Reactions Cron] Messenger trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Construit le corps de l'email depuis les données et le template
     *
     * @param array $data Données de l'auteur et de ses réactions groupées.
     * @param string $author_name Nom du destinataire.
     * @param string $author_lang Langue du destinataire.
     * @return string Corps de l'email en texte brut.
     */
    protected function build_email_body(array $data, string $author_name, string $author_lang)
    {
        $this->log("        🔨 [build_email_body] Début de la construction...");
        
        $author_name_utf8 = $this->normalize_utf8($author_name);
        $sitename_utf8 = $this->normalize_utf8($this->config['sitename']);
        
        $body = '✨ ' . sprintf($this->language->lang('REACTIONS_DIGEST_HELLO'), $author_name_utf8) . "\n\n";
        $body .= sprintf($this->language->lang('REACTIONS_DIGEST_INTRO'), $sitename_utf8) . "\n\n";
        $body .= str_repeat('-', 50) . "\n\n";
        
        $this->log("        └─ En-tête construit");
        
        foreach ($data['posts'] as $post_id => $post_data)
        {
            $post_title = sprintf($this->language->lang('REACTIONS_DIGEST_POST_TITLE'), $post_data['SUBJECT_PLAIN']);
            $body .= $this->normalize_utf8($post_title) . "\n\n";
            
            $this->log("        📝 Post #{$post_id}: \"{$post_data['SUBJECT_PLAIN']}\"");
            
            if (isset($post_data['reactions']) && is_array($post_data['reactions']))
            {
                $this->log("        └─ Nombre de réactions pour ce post: " . count($post_data['reactions']));
                foreach ($post_data['reactions'] as $idx => $reaction)
                {
                    $emoji_original = $reaction['EMOJI'] ?? $reaction['EMOJI_ORIGINAL'] ?? '?';
                    $emoji_utf8 = $this->normalize_emoji($emoji_original);
                    
                    $this->log("           Réaction #" . ($idx + 1) . ":");
                    $this->log("              └─ Emoji original: [{$emoji_original}] (hex: " . bin2hex($emoji_original) . ")");
                    $this->log("              └─ Emoji normalisé: [{$emoji_utf8}] (hex: " . bin2hex($emoji_utf8) . ")");
                    
                    // Utiliser l'emoji directement (quoted-printable le supportera)
                    $emoji_display = ($emoji_utf8 !== '?' && $emoji_utf8 !== '') ? $emoji_utf8 : '[?]';
                    
                    $this->log("              └─ Emoji final pour email: [{$emoji_display}]");
                    
                    $reacter_name_utf8 = $this->normalize_utf8($reaction['REACTER_NAME']);
                    
                    $body .= "- {$emoji_display} par {$reacter_name_utf8} (le {$reaction['TIME_FORMATTED']}) ✅\n";
                }
            }
            
            $body .= "\n" . $this->language->lang('REACTIONS_DIGEST_VIEW_POST') . " : {$post_data['POST_URL_ABSOLUTE']}\n";
            $body .= str_repeat('-', 50) . "\n\n";
        }
        
        $body .= "\n" . $this->language->lang('REACTIONS_DIGEST_FOOTER') . "\n";
        $body .= $this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE') . "\n\n";
        $body .= $this->language->lang('REACTIONS_DIGEST_VIEW_POST') . " : " . generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$data['author_id']}\n";
        $body .= $this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE') . " : " . generate_board_url() . "/ucp.{$this->php_ext}?i=ucp_notifications\n\n";
        $body .= sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $sitename_utf8) . "\n";
        
        return $body;
    }

    /**
     * Normalise une chaîne en UTF-8 valide
     *
     * @param string $str Chaîne à normaliser.
     * @return string Chaîne normalisée en UTF-8.
     */
    protected function normalize_utf8($str)
    {
        if (!is_string($str))
        {
            return '';
        }

        // Si la chaîne est déjà en UTF-8 valide, la retourner telle quelle
        if (mb_check_encoding($str, 'UTF-8'))
        {
            return $str;
        }

        // Tenter de convertir depuis différents encodages courants
        $detected = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        
        if ($detected && $detected !== 'UTF-8')
        {
            $converted = mb_convert_encoding($str, 'UTF-8', $detected);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8'))
            {
                return $converted;
            }
        }

        // Dernier recours : nettoyer les caractères invalides
        return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    /**
     * Normalise un emoji en UTF-8 valide
     *
     * @param string $emoji Emoji à normaliser.
     * @return string Emoji normalisé en UTF-8, ou '?' si invalide.
     */
    protected function normalize_emoji($emoji)
    {
        if (empty($emoji) || !is_string($emoji))
        {
            return '?';
        }

        // Normaliser d'abord en UTF-8
        $emoji_utf8 = $this->normalize_utf8($emoji);

        // Vérifier que c'est un emoji valide (contient des caractères Unicode emoji)
        // Les emojis sont généralement dans les plages Unicode suivantes :
        // - U+1F300–U+1F9FF (Symbols and Pictographs)
        // - U+2600–U+26FF (Miscellaneous Symbols)
        // - U+2700–U+27BF (Dingbats)
        // - U+FE00–U+FE0F (Variation Selectors)
        // - U+1F900–U+1F9FF (Supplemental Symbols and Pictographs)
        // - U+1F1E0–U+1F1FF (Regional Indicator Symbols)
        
        // Vérifier que la chaîne contient au moins un caractère emoji
        if (preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]/u', $emoji_utf8))
        {
            return $emoji_utf8;
        }

        // Si ce n'est pas un emoji valide, retourner '?'
        return '?';
    }

    /**
     * Convertit un emoji en représentation textuelle pour les emails
     * 
     * SOLUTION DE CONTOURNEMENT : Content-Transfer-Encoding: 8bit ne supporte pas les emojis UTF-8.
     * On utilise une représentation textuelle purement ASCII pour garantir la compatibilité.
     *
     * @param string $emoji Emoji UTF-8 à convertir.
     * @return string Représentation textuelle de l'emoji (ex: "[thumbs up]" ou "[emoji]").
     */
    protected function emoji_to_text($emoji)
    {
        $this->log("              🔄 [emoji_to_text] Conversion de l'emoji en texte...");
        $this->log("                 └─ Emoji reçu: [{$emoji}] (hex: " . bin2hex($emoji) . ", length: " . strlen($emoji) . ")");
        
        if (empty($emoji) || $emoji === '?')
        {
            $this->log("                 ⚠️  Emoji vide ou '?' - retour de '[?]'");
            return '[?]';
        }

        // Mapping des emojis les plus courants vers leur nom textuel (ASCII pur, sans accents)
        // Format: emoji => description en français sans accents pour compatibilité email
        $emoji_map = [
            '👍' => '[pouce leve]',
            '👎' => '[pouce baisse]',
            '❤️' => '[coeur]',
            '❤' => '[coeur]', // Variante sans variation selector
            '😍' => '[yeux en coeur]',
            '😂' => '[rire aux larmes]',
            '😊' => '[sourire]',
            '🙂' => '[leger sourire]',
            '😑' => '[impassible]',
            '🙁' => '[leger froncement]',
            '😯' => '[surpris]',
            '😭' => '[pleure]',
            '😡' => '[en colere]',
            '😮' => '[bouche ouverte]',
            '🔥' => '[feu]',
            '⭐' => '[etoile]',
            '💯' => '[cent]',
            '🎉' => '[fete]',
            '✅' => '[coche]',
            '❌' => '[croix]',
        ];

        // Si l'emoji est dans le mapping, utiliser le nom textuel
        if (isset($emoji_map[$emoji]))
        {
            $result = $emoji_map[$emoji];
            $this->log("                 ✅ Emoji trouvé dans le mapping: [{$emoji}] → {$result}");
            return $result;
        }

        // Sinon, utiliser une description générique
        // On évite d'inclure l'emoji lui-même car il ne s'affichera pas avec 8bit
        $this->log("                 ⚠️  Emoji non trouvé dans le mapping - retour de '[emoji]'");
        return '[emoji]';
    }

    /**
     * Retourne le nom de la tâche
     *
     * @return string Nom de la tâche (utilisé dans l'ACP et par la CLI).
     */
    public function get_name()
    {
        return 'reactions.digest';
    }

    /**
     * Détermine si la tâche peut s'exécuter
     * La tâche ne peut s'exécuter que si les e-mails sont activés sur le forum.
     *
     * @return bool
     */
    public function is_runnable()
    {
        return (bool) $this->config['email_enable'];
    }
}
