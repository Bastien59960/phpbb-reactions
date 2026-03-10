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
     * 3. Pour chaque auteur (max 50 par run), envoie un e-mail de résumé (digest).
     */
    public function run()
    {
        set_time_limit(0);

        $run_deadline = time() + 55;
        $current_time = isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time();

        try
        {
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

            $threshold_timestamp = $current_time - $spam_delay_seconds;

            // Forcer utf8mb4 pour la connexion afin de lire correctement les emojis
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");

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

            $by_author = [];
            $reactions_to_cleanup = [];

            while ($row = $this->db->sql_fetchrow($result))
            {
                $reaction_id  = (int) $row['reaction_id'];
                $post_id      = (int) $row['post_id'];
                $author_id    = isset($row['author_id']) ? (int) $row['author_id'] : 0;
                $author_name  = $row['author_name'] ?? '';
                $author_email = $row['author_email'] ?? '';
                $author_lang  = $row['author_lang'] ?? '';
                $reacter_id   = (int) ($row['reacter_id'] ?? 0);
                $reacter_name = $row['reacter_name'] ?? '';
                $emoji        = isset($row['reaction_emoji']) ? $row['reaction_emoji'] : '';
                $r_time       = (int) ($row['reaction_time'] ?? 0);
                $post_subject = $row['post_subject'] ?? '';

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
                if (!empty($reactions_to_cleanup))
                {
                    error_log('[Reactions Cron] Cleaning up ' . count($reactions_to_cleanup) . ' orphan/self reactions.');
                    $this->mark_reactions_as_handled($reactions_to_cleanup);
                }
                $this->config->set('bastien59960_reactions_cron_last_run', $current_time);
                return;
            }

            include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);

            $processed = 0;

            foreach ($by_author as $author_id => $data)
            {
                if ($processed >= 50 || time() >= $run_deadline)
                {
                    break;
                }

                $author_email = $data['author_email'];
                $author_name  = $data['author_name'] ?: 'Utilisateur';
                $author_lang  = $data['author_lang'] ?: 'fr';

                if (empty($author_email))
                {
                    error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (no email).');
                    $this->mark_reactions_as_handled($data['mark_ids']);
                    continue;
                }

                $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

                if ($disable_cron_email === true)
                {
                    error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (email preference disabled).');
                    $this->mark_reactions_as_handled($data['mark_ids']);
                    continue;
                }

                if (empty($data['posts']))
                {
                    error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (no valid posts).');
                    $this->mark_reactions_as_handled($data['mark_ids']);
                    continue;
                }

                $this->language->set_user_language($author_lang);
                $this->language->add_lang('email', 'bastien59960/reactions');
                $this->language->add_lang('common', 'bastien59960/reactions');

                $author_name_utf8 = $this->normalize_utf8($author_name);
                $sitename_utf8 = $this->normalize_utf8($this->config['sitename']);

                $token = hash_hmac('sha256', 'reactions_unsub_' . $author_id, $this->config['cookie_seed']);
                $unsub_url = generate_board_url() . '/app.php/reactions/unsubscribe?u=' . $author_id . '&token=' . urlencode($token);

                $subject = $this->language->lang('REACTIONS_DIGEST_SUBJECT');
                $subject_utf8 = $this->normalize_utf8($subject);

                $messenger = new \messenger(true);
                $messenger->headers('Content-Transfer-Encoding: quoted-printable');
                $messenger->to($author_email, $author_name_utf8);
                $messenger->subject($subject_utf8);
                $messenger->template('@bastien59960_reactions/email/reaction_digest', $author_lang);

                $since_time_formatted = date('d/m/Y H:i', $threshold_timestamp);

                $messenger->assign_vars([
                    'HELLO_USERNAME'   => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_HELLO'), $author_name_utf8)),
                    'DIGEST_INTRO'     => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_INTRO'), $sitename_utf8)),
                    'DIGEST_SIGNATURE' => $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $sitename_utf8)),
                    'DIGEST_FOOTER'    => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_FOOTER')),
                    'UNSUBSCRIBE_TEXT' => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE')),
                    'L_UNSUBSCRIBE_LINK' => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_UNSUBSCRIBE_LINK')),
                    'U_UNSUBSCRIBE'    => $unsub_url,
                    'SITENAME'         => $sitename_utf8,
                    'BOARD_URL'        => generate_board_url(),
                    'U_UCP'            => generate_board_url() . "/ucp.{$this->php_ext}?i=ucp_notifications",
                    'U_USER_PROFILE'   => generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$data['author_id']}",
                    'L_REACTION_FROM'  => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_REACTION_FROM')),
                    'L_ON_DATE'        => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_ON_DATE')),
                    'L_VIEW_POST'      => $this->normalize_utf8($this->language->lang('REACTIONS_DIGEST_VIEW_POST')),
                    'REACTIONS_DIGEST_SUBJECT' => $subject_utf8,
                ]);

                $data['posts'] = array_values($data['posts']);

                foreach ($data['posts'] as $post_data)
                {
                    $post_title_utf8 = $this->normalize_utf8(sprintf($this->language->lang('REACTIONS_DIGEST_POST_TITLE'), $post_data['SUBJECT_PLAIN']));
                    $messenger->assign_block_vars('posts', [
                        'POST_TITLE'        => $post_title_utf8,
                        'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                    ]);

                    if (isset($post_data['reactions']) && is_array($post_data['reactions']))
                    {
                        foreach ($post_data['reactions'] as $reaction)
                        {
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
                $messenger->save_queue();

                if ($send_result !== false)
                {
                    $this->mark_reactions_as_handled($data['mark_ids'], 1); // email envoyé
                    $processed++;
                }
                else
                {
                    error_log('[Reactions Cron] Send failed for user_id ' . $author_id . '.');
                    $this->mark_reactions_as_handled($data['mark_ids'], 3); // email échoué
                }
            }

            // Nettoyer les réactions orphelines/auto-infligées
            if (!empty($reactions_to_cleanup))
            {
                error_log('[Reactions Cron] Cleaning up ' . count($reactions_to_cleanup) . ' orphan/self reactions.');
                $this->mark_reactions_as_handled($reactions_to_cleanup);
            }

            $this->config->set('bastien59960_reactions_cron_last_run', $current_time);

            error_log('[Reactions Cron] Run complete: processed=' . $processed . ' authors.');
        }
        catch (\Throwable $exception)
        {
            error_log('[Reactions Cron] Unhandled exception: ' . $exception->getMessage());
            error_log('[Reactions Cron] Unhandled trace: ' . $exception->getFile() . ':' . $exception->getLine());
            $this->config->set('bastien59960_reactions_cron_last_run', $current_time);
        }
    }

    /**
     * Marque les réactions comme notifiées
     *
     * @param array $ids Tableau d'IDs de réactions à marquer.
     * @return void
     */
    /**
     * Marque les réactions comme traitées par le cron.
     *
     * @param array $ids         IDs de réactions à marquer.
     * @param int   $email_sent  Statut email :
     *                           0 = pending (ne pas utiliser ici)
     *                           1 = email envoyé avec succès
     *                           2 = email ignoré (pas d'email, préf. désactivée, self-réaction…)
     *                           3 = email échoué (send() === false ou exception)
     */
    protected function mark_reactions_as_handled(array $ids, int $email_sent = 2)
    {
        if (empty($ids))
        {
            return;
        }

        $ids = array_map('intval', $ids);
        $sql = 'UPDATE ' . $this->post_reactions_table . '
                SET reaction_notified = 1, reaction_email_sent = ' . (int) $email_sent . '
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
        if (!(int) $this->config['bastien59960_reactions_enabled'])
        {
            return false;
        }

        $spam_minutes = (int) $this->config['bastien59960_reactions_spam_time'];
        if ($spam_minutes <= 0)
        {
            return false;
        }

        $last_run = (int) $this->config['bastien59960_reactions_cron_last_run'];
        $interval = max(60, $spam_minutes * 60);

        return $last_run < (time() - $interval);
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
     * @return string Emoji normalisé en UTF-8, ou 'XXX' si invalide.
     */
    protected function normalize_emoji($emoji)
    {
        if (empty($emoji) || !is_string($emoji))
        {
            return 'XXX';
        }

        // Normaliser d'abord en UTF-8
        $emoji_utf8 = $this->normalize_utf8($emoji);

        // Vérifier que c'est un emoji valide (contient des caractères Unicode emoji)
        if (preg_match('/[\p{S}\p{P}\p{N}]/u', $emoji_utf8))
        {
            return $emoji_utf8;
        }

        return 'XXX';
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
