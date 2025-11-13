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
        $spam_minutes = (int) ($this->config['bastien59960_reactions_spam_time'] ?? 45);
        if ($spam_minutes <= 0)
        {
            error_log('[Reactions Cron] Run skipped (spam_minutes <= 0).');
            return;
        }

        $spam_delay = $spam_minutes * 60;

        if (!function_exists('generate_board_url'))
        {
            include_once($this->phpbb_root_path . 'includes/functions.' . $this->php_ext);
        }

        $this->language->add_lang('common', 'bastien59960/reactions');
        $threshold_timestamp = time() - $spam_delay;
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
                error_log('[Reactions Cron] Skipping reaction_id ' . $reaction_id . ' (orphan post). Marking as handled.');
                $this->mark_reactions_as_handled([$reaction_id]);
                continue;
            }

            if ($author_id === $reacter_id)
            {
                error_log('[Reactions Cron] Skipping reaction_id ' . $reaction_id . ' (self-reaction). Marking as handled.');
                $this->mark_reactions_as_handled([$reaction_id]);
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

            $by_author[$author_id]['posts'][$post_id]['reactions'][] = [
                'REACTION_ID'          => $reaction_id,
                'REACTER_ID'           => $reacter_id,
                'REACTER_NAME'         => $reacter_name,
                'EMOJI'                => $emoji ?: '?',
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
            $result = $this->send_digest_email($data, $since_time_formatted);

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

        $this->config->set('bastien59960_reactions_cron_last_run', time());

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
        $spam_minutes = (int) ($this->config['bastien59960_reactions_spam_time'] ?? 45);
        if ($spam_minutes <= 0)
        {
            return false;
        }

        $interval = max(60, $spam_minutes * 60);
        $last_run = (int) ($this->config['bastien59960_reactions_cron_last_run'] ?? 0);

        return (time() - $last_run) >= $interval;
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
            if (!class_exists('messenger'))
            {
                include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
            }

            $messenger = new \messenger(false);

            // Charger la langue de l'utilisateur
            $this->language->set_user_language($author_lang);
            $this->language->add_lang('email', 'bastien59960/reactions');
            $this->language->add_lang('common', 'bastien59960/reactions');

            $messenger->to($author_email, $author_name);
            
            $subject = $this->language->lang('REACTIONS_DIGEST_SUBJECT');
            $messenger->subject($subject);

            // Template relatif à partir de styles/all/template/
            $messenger->template('email/reaction_digest', $author_lang);

            // Assigner les variables globales
            $messenger->assign_vars([
                'HELLO_USERNAME'   => sprintf($this->language->lang('REACTIONS_DIGEST_HELLO'), $author_name),
                'DIGEST_INTRO'     => sprintf($this->language->lang('REACTIONS_DIGEST_INTRO'), $this->config['sitename']),
                'DIGEST_SIGNATURE' => sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $this->config['sitename']),
                'DIGEST_FOOTER'    => $this->language->lang('REACTIONS_DIGEST_FOOTER'),
                'UNSUBSCRIBE_TEXT' => $this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE'),
                'SITENAME'         => $this->config['sitename'],
                'BOARD_URL'        => generate_board_url(),
                'U_UCP'            => generate_board_url() . "/ucp.{$this->php_ext}?i=ucp_notifications",
                'U_USER_PROFILE'   => generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$author_id}",
                'L_REACTION_FROM'  => $this->language->lang('REACTIONS_DIGEST_REACTION_FROM'),
                'L_ON_DATE'        => $this->language->lang('REACTIONS_DIGEST_ON_DATE'),
                'L_VIEW_POST'      => $this->language->lang('REACTIONS_DIGEST_VIEW_POST'),
                'REACTIONS_DIGEST_SUBJECT' => $subject,
            ]);

            // Assigner les blocs de posts
            foreach ($data['posts'] as $post_data)
            {
                $messenger->assign_block_vars('posts', [
                    'POST_TITLE'        => sprintf($this->language->lang('REACTIONS_DIGEST_POST_TITLE'), $post_data['SUBJECT_PLAIN']),
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                if (isset($post_data['reactions']) && is_array($post_data['reactions']))
                {
                    foreach ($post_data['reactions'] as $reaction) {
                        $messenger->assign_block_vars('posts.reactions', [
                            'EMOJI'                => $reaction['EMOJI'],
                            'REACTER_NAME'         => $reaction['REACTER_NAME'],
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
     * Retourne le nom de la tâche
     *
     * @return string Nom de la tâche (utilisé dans l'ACP et par la CLI).
     */
    public function get_name()
    {
        return 'cron.task.bastien59960.reactions.notification';
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
