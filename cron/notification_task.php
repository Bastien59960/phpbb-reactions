<?php
/**
 * =============================================================================
 * Fichier : /cron/notification_task.php
 * Extension : bastien59960/reactions
 * =============================================================================
 *
 * @package   bastien59960/reactions
 * @author    Bastien (bastien59960)
 * @copyright (c) 2025 Bastien59960
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * @description
 * Définit la tâche cron pour l'envoi groupé des notifications de réactions
 * par e-mail (digest). Cette tâche se déclenche périodiquement pour regrouper les
 * nouvelles réactions et envoyer un résumé aux utilisateurs concernés, évitant
 * ainsi le spam d'e-mails.
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

        error_log('[Reactions Cron] Run start (interval=' . $spam_minutes . ' min, threshold=' . date('Y-m-d H:i:s', $threshold_timestamp) . ')');

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

        $result = $this->db->sql_query($sql);
        $total_reactions_found = $this->db->sql_affectedrows($result);
        
        error_log('[Reactions Cron] Found ' . $total_reactions_found . ' unnotified reactions to process.');

        $by_author = [];
        $reactions_to_cleanup = []; // Pour les réactions orphelines ou auto-infligées

        while ($row = $this->db->sql_fetchrow($result))
        {
            $reaction_id   = (int) $row['reaction_id'];
            $post_id       = (int) $row['post_id'];
            $author_id     = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $author_name   = $row['author_name'] ?? '';
            $author_email  = $row['author_email'] ?? '';
            $author_lang   = $row['author_lang'] ?? '';
            $reacter_id    = (int) ($row['reacter_id'] ?? 0);
            $reacter_name  = $row['reacter_name'] ?? '';
            $emoji         = $row['reaction_emoji'] ?? '';
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
            $emoji_normalized = $this->normalize_emoji($emoji);
            $reacter_name_normalized = $this->normalize_utf8($reacter_name);

            $by_author[$author_id]['posts'][$post_id]['reactions'][] = [
                'REACTION_ID'          => $reaction_id,
                'REACTER_ID'           => $reacter_id,
                'REACTER_NAME'         => $reacter_name_normalized,
                'EMOJI'                => $emoji_normalized,
                'TIME'                 => $r_time,
                'TIME_FORMATTED'       => date('d/m/Y H:i', $r_time),
                'PROFILE_URL_ABSOLUTE' => $profile_url_absolute,
            ];

            $by_author[$author_id]['mark_ids'][] = $reaction_id;
        }

        $this->db->sql_freeresult($result);

        if (empty($by_author))
        {
            error_log('[Reactions Cron] No reactions to process after grouping. Exiting.');
            // S'il n'y a pas de réactions valides mais des réactions à nettoyer, on le fait.
            if (!empty($reactions_to_cleanup))
            {
                error_log('[Reactions Cron] Cleaning up ' . count($reactions_to_cleanup) . ' orphan/self reactions.');
                $this->mark_reactions_as_handled($reactions_to_cleanup);
            }
            return;
        }

        include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);

        $processed_authors = 0;
        $processed_reactions = 0;
        $sent_reactions = 0;
        $skipped_no_email = 0;
        $skipped_pref = 0;
        $skipped_empty = 0;
        $skipped_failed = 0;

        foreach ($by_author as $author_id => $data)
        {
            error_log("[CRON] Début du traitement pour l'auteur #{$author_id} ({$data['author_name']}) avec " . count($data['mark_ids']) . " réaction(s).");

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

        try
        {
            // CORRECTION CRITIQUE : Changer la langue AVANT d'instancier le messenger.
            // Le messenger charge les fichiers de langue dans son constructeur.
            // Si on change la langue après, il est trop tard.
            $this->language->set_user_language($author_lang);
            $this->language->add_lang('email', 'bastien59960/reactions');
            $this->language->add_lang('common', 'bastien59960/reactions');

            if (!class_exists('messenger'))
            {
                include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
            }

            $messenger = new \messenger(false);
            
            // CORRECTION UTF-8 : Normaliser tous les strings en UTF-8 avant l'envoi
            $author_name_utf8 = $this->normalize_utf8($author_name);
            $sitename_utf8 = $this->normalize_utf8($this->config['sitename']);
            
            $messenger->to($author_email, $author_name_utf8);
            $subject = $this->language->lang('REACTIONS_DIGEST_SUBJECT');
            $subject_utf8 = $this->normalize_utf8($subject);
            $messenger->subject($subject_utf8);

            // CORRECTION : Spécifier le chemin complet du template pour plus de robustesse.
            // Cela garantit que phpBB trouve les fichiers, même dans des configurations non standard.
            $template_path = '@bastien59960_reactions/email/reaction_digest';
            $messenger->template($template_path, $author_lang);

            // Assigner les variables globales (toutes normalisées en UTF-8)
            $messenger->assign_vars([
                'HELLO_USERNAME'   => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_HELLO'), $author_name_utf8)),
                'DIGEST_INTRO'     => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_INTRO'), $sitename_utf8)),
                'DIGEST_SIGNATURE' => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $sitename_utf8)),
                'DIGEST_FOOTER'    => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_FOOTER')),
                'UNSUBSCRIBE_TEXT' => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE')),
                'SITENAME'         => $sitename_utf8,
                'BOARD_URL'        => generate_board_url(),
                'U_UCP'            => generate_board_url() . "/ucp.{$this->php_ext}?i=ucp_notifications",
                'U_USER_PROFILE'   => generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$author_id}",
                'L_REACTION_FROM'  => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_REACTION_FROM')),
                'L_ON_DATE'        => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_ON_DATE')),
                'L_VIEW_POST'      => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_VIEW_POST')),
                'REACTIONS_DIGEST_SUBJECT' => $subject_utf8,
            ]);

            // Assigner les blocs de posts (tous normalisés en UTF-8)
            foreach ($data['posts'] as $post_data)
            {
                $post_title_utf8 = $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_POST_TITLE'), $post_data['SUBJECT_PLAIN']));
                $messenger->assign_block_vars('posts', [
                    'POST_TITLE'        => $post_title_utf8,
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                if (isset($post_data['reactions']) && is_array($post_data['reactions']))
                {
                    foreach ($post_data['reactions'] as $reaction) {
                        // CORRECTION CRITIQUE : Normaliser l'emoji en UTF-8 et s'assurer qu'il est valide
                        $emoji_utf8 = $this->normalize_emoji($reaction['EMOJI']);
                        $reacter_name_utf8 = $this->normalize_utf8($reaction['REACTER_NAME']);
                        
                        $messenger->assign_block_vars('posts.reactions', [
                            'EMOJI'                => $emoji_utf8,
                            'REACTER_NAME'         => $reacter_name_utf8,
                            'TIME_FORMATTED'       => $reaction['TIME_FORMATTED'],
                            'PROFILE_URL_ABSOLUTE' => $reaction['PROFILE_URL_ABSOLUTE'],
                        ]);
                    }
                }
            }

            $send_result = $messenger->send(NOTIFY_EMAIL);

            if ($send_result)
            {
                error_log("$log_prefix Email sent successfully to $author_name ($author_email) - " . count($data['mark_ids']) . " reactions");
                return ['status' => 'sent'];
            }
            else
            {
                error_log("$log_prefix Send failed for $author_email (messenger->send() = false)");
                return ['status' => 'failed', 'error' => 'messenger send returned false'];
            }
        }
        catch (\Exception $e)
        {
            error_log("$log_prefix Exception for user_id $author_id: " . $e->getMessage());
            error_log("$log_prefix File: " . $e->getFile() . " | Line: " . $e->getLine());

            return [
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
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
     * Retourne le nom de la tâche
     *
     * @return string Nom de la tâche (utilisé dans l'ACP et par la CLI).
     */
    public function get_name()
    {
        return 'bastien59960.reactions.notification';
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
