<?php
/**
 * Fichier : cron/notification_task.php — bastien59960/reactions/cron/notification_task.php
 *
 * Tâche cron pour l'envoi groupé des notifications de réactions par e-mail (digest).
 *
 * CORRECTION : Ajout de la référence correcte au service de notification
 * 'bastien59960.reactions.notification.type.reaction_email_digest'
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\cron;

if (!defined('IN_PHPBB'))
{
    exit;
}

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

    /** @var \phpbb\notification\messenger_factory */
    protected $messenger_factory;

    /** @var string Nom de la table des réactions */
    protected $post_reactions_table;

    /** @var string Chemin racine phpBB */
    protected $phpbb_root_path;

    /** @var string Extension des fichiers php */
    protected $php_ext;

    /** @var string Préfixe des tables phpBB */
    protected $table_prefix;

    /** @var \Symfony\Component\Console\Output\OutputInterface|null */
    protected $io;

    /**
     * Constructeur
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
        $table_prefix,
        \phpbb\notification\messenger_factory $messenger_factory,
        \Symfony\Component\Console\Output\OutputInterface $io = null
    ) {
        $this->db                   = $db;
        $this->config               = $config;
        $this->notification_manager = $notification_manager;
        $this->user_loader          = $user_loader;
        $this->language             = $language;
        $this->template             = $template;
        $this->post_reactions_table = $post_reactions_table;
        $this->phpbb_root_path      = $phpbb_root_path;
        $this->php_ext              = $php_ext;
        $this->table_prefix         = $table_prefix;
        $this->messenger_factory    = $messenger_factory;
        $this->io                   = $io;
    }

    /**
     * Condition d'exécution
     */
    public function can_run()
    {
        return true;
    }

    /**
     * Méthode principale exécutée par le cron
     */
    public function run()
    {
        // Détecter si on est en mode CLI pour afficher les logs dans la console
        $io = $this->io;

        // Récupérer le délai anti-spam (en minutes, défaut : 45)
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

        // Charge uniquement le fichier de langue nécessaire pour les logs et les messages génériques.
        // La langue de l'e-mail sera chargée pour chaque utilisateur individuellement.
        $this->language->add_lang('common', 'bastien59960/reactions');
        // Seuil chronologique pour récupérer les réactions.
        // On s'assure que seules les réactions plus anciennes que le délai sont traitées.
        $threshold_timestamp = time() - $spam_delay; // $spam_delay est en secondes
        $run_start = microtime(true);

        $start_message = '[Reactions Cron] Run start (interval=' . $spam_minutes . ' min, threshold=' . date('Y-m-d H:i:s', $threshold_timestamp) . ')';
        if ($io) {
            $io->writeln("<info>$start_message</info>");
        } else {
            error_log($start_message);
        }

        // Récupérer toutes les réactions non notifiées plus anciennes que le seuil
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
        $found_message = '[Reactions Cron] Found ' . $total_reactions_found . ' unnotified reactions to process.';
        if ($io) {
            $io->writeln("<info>$found_message</info>");
        } else {
            error_log($found_message);
        }

        // Structure de regroupement par auteur
        $by_author = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
            // Extraction des données de la ligne
            $reaction_id   = (int) $row['reaction_id'];
            $post_id       = (int) $row['post_id'];
            $author_id     = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $author_name   = $row['author_name'] ?? '';
            $author_email  = $row['author_email'] ?? '';
            $author_lang   = $row['author_lang'] ?? '';
            $topic_id      = (int) ($row['topic_id'] ?? 0);
            $post_subject  = $row['post_subject'] ?? '';
            $reacter_id    = (int) ($row['reacter_id'] ?? 0);
            $reacter_name  = $row['reacter_name'] ?? '';
            $emoji         = $row['reaction_emoji'] ?? '';
            $r_time        = (int) ($row['reaction_time'] ?? 0);

            if ($author_id <= 0)
            {
                // Post orphelin -> marquer pour éviter les boucles
                $orphan_message = '[Reactions Cron] Skipping reaction_id ' . $reaction_id . ' (orphan post, no author). Marking as handled.';
                if ($io) {
                    $io->writeln("<comment>$orphan_message</comment>");
                } else {
                    error_log($orphan_message);
                }
                $this->mark_reactions_as_handled([$reaction_id]);
                continue;
            }

            if (!isset($by_author[$author_id]))
            {
                $by_author[$author_id] = [
                    'author_id' => $author_id,
                    'author_name' => $author_name,
                    'author_email' => $author_email,
                    'author_lang' => $author_lang,
                    'posts' => [],
                    'mark_ids' => [],
                ];
            }

            // Ignorer les réactions où l'auteur réagit à son propre message.
            if ($author_id === $reacter_id)
            {
                $self_react_message = '[Reactions Cron] Skipping reaction_id ' . $reaction_id . ' (self-reaction). Marking as handled.';
                if ($io) {
                    $io->writeln("<comment>$self_react_message</comment>");
                } else {
                    error_log($self_react_message);
                }
                $this->mark_reactions_as_handled([$reaction_id]);
                continue;
            }

            $subject_plain = ($post_subject !== '') ? html_entity_decode(strip_tags($post_subject), ENT_QUOTES, 'UTF-8') : $this->language->lang('NO_SUBJECT');
            $post_url_absolute = generate_board_url() . "/viewtopic.{$this->php_ext}?p={$post_id}#p{$post_id}";
            $profile_url_absolute = generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u={$reacter_id}";

            if (!isset($by_author[$author_id]['posts'][$post_id]))
            {
                $by_author[$author_id]['posts'][$post_id] = [
                    'SUBJECT_PLAIN'     => $subject_plain,
                    'POST_URL_ABSOLUTE' => $post_url_absolute,
                    'reactions'         => [], // Initialiser le tableau des réactions pour ce post
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

        // Si rien à traiter
        if (empty($by_author))
        {
            $nothing_message = '[Reactions Cron] No reactions to process after grouping. Exiting.';
            if ($io) {
                $io->writeln("<info>$nothing_message</info>");
            } else {
                error_log($nothing_message);
            }
            return;
        }

        // Inclure le messenger phpBB
        include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);

        $processed_authors = 0;
        $processed_reactions = 0;
        $sent_reactions = 0;
        $skipped_no_email = 0;
        $skipped_pref = 0;
        $skipped_empty = 0;
        $skipped_failed = 0;

        // Pour chaque destinataire, envoyer un e-mail groupé
        foreach ($by_author as $author_id => $data)
        {
            $processed_authors++;
            $reaction_total_for_author = isset($data['mark_ids']) ? count($data['mark_ids']) : 0;
            $processed_reactions += $reaction_total_for_author;
            $author_email = $data['author_email'];
            $author_name  = $data['author_name'] ?: 'Utilisateur';
            $author_lang  = $data['author_lang'] ?: 'en';

            // Si pas d'email
            if (empty($author_email))
            {
                $skipped_no_email += $reaction_total_for_author;
                $skip_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (aucune adresse e-mail).';
                if ($io) {
                    $io->writeln("<comment>$skip_message</comment>");
                } else {
                    error_log($skip_message);
                }
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Vérifier préférence utilisateur
            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email === true)
            {
                $skipped_pref += $reaction_total_for_author;
                $skip_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (préférence e-mail désactivée).';
                if ($io) {
                    $io->writeln("<comment>$skip_message</comment>");
                } else {
                    error_log($skip_message);
                }
                // Utilisateur a désactivé les récap emails
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Si, après tous les filtres, il n'y a plus de posts avec des réactions valides, on ignore.
            if (empty($data['posts']))
            {
                $empty_posts_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (no valid posts left after filtering).';
                if ($io) {
                    $io->writeln("<comment>$empty_posts_message</comment>");
                } else {
                    error_log($empty_posts_message);
                }
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // TRANSFORMATION CRUCIALE : Convertir le tableau associatif des posts en tableau indexé.
            $data['posts'] = array_values($data['posts']);

            $since_time_formatted = date('d/m/Y H:i', $threshold_timestamp);
            // Envoyer l'e-mail récapitulatif
            $result = $this->send_digest_email($data, $since_time_formatted, $io);

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
                $fail_message = '[Reactions Cron] Envoi non abouti pour user_id ' . $author_id . ' (status=' . $result['status'] . ').';
                if ($io) {
                    $io->writeln("<error>$fail_message</error>");
                } else {
                    error_log($fail_message);
                }
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

        if ($io) {
            $io->writeln("<info>$summary_message</info>");
        } else {
            error_log($summary_message);
        }
    }

    /**
     * Marque les réactions comme notifiées
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
            // Vérifier directement dans la table users (colonne user_reactions_email)
            $sql = 'SELECT user_reactions_cron_email
                    FROM ' . USERS_TABLE . '
                    WHERE user_id = ' . (int) $user_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row)
            {
                return false; // L'utilisateur n'existe pas
            }

            // user_reactions_cron_email : 1 = reçoit les e-mails, 0 = désactivé
            return ((int) $row['user_reactions_cron_email']) === 0;
        }
        catch (\phpbb\db\exception $e)
        {
            // La colonne n'existe probablement pas, on considère que l'utilisateur veut recevoir les e-mails.
            error_log('[Reactions Cron] DB Error checking pref for user ' . $user_id . ': ' . $e->getMessage() . '. Assuming pref is ON.');
            return false; // Ne pas désactiver les e-mails
        }
    }

    /**
     * Indique si la tâche doit s'exécuter (appelé par phpBB).
     * La fréquence dépend du délai configuré dans l'ACP (en minutes).
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
     * Définit le prochain moment d'exécution
     */
    public function is_runnable()
    {
        return true;
    }

    /**
     * Construit et envoie un e-mail récapitulatif à un utilisateur.
     *
     * @param array $data Données groupées pour l'auteur
     * @param string $since_time_formatted Date formatée du début de la période.
     * @param \Symfony\Component\Console\Output\OutputInterface|null $io L'objet de sortie console, si disponible.
     * @return array{status:string,error?:string} Statut d'envoi (sent, skipped_empty, failed).
     */
    protected function send_digest_email(array $data, string $since_time_formatted, $io = null)
    {
        $author_id    = (int) $data['author_id'];
        $author_email = $data['author_email'];
        $author_name  = $data['author_name'] ?: 'Utilisateur';
        $author_lang  = $data['author_lang'] ?: 'en';

        $log_prefix = '[Reactions Cron]';

        if (empty($data['posts']))
        {
            $message = "$log_prefix Aucun contenu à envoyer pour user_id $author_id (récapitulatif vide).";
            if ($io) $io->writeln("<comment>$message</comment>");
            else error_log($message);
            return ['status' => 'skipped_empty'];
        }

        try
        {
            $messenger = $this->messenger_factory->get_messenger('email');

            // 1. Charger la langue de l'utilisateur AVANT de charger le template
            $this->language->add_lang('common', 'bastien59960/reactions', false, $author_lang);
            // CORRECTION CRITIQUE : Charger aussi les fichiers de langue principaux de phpBB.
            $this->language->add_lang(['common', 'email'], false, false, $author_lang);
            
            // 2. Charger le template d'e-mail en utilisant la syntaxe standard de phpBB.
            $messenger->template('@bastien59960_reactions/email/reaction_digest', $author_lang);

            // 3. Définir le destinataire
            $messenger->to($author_email, $author_name);

            // 4. Assigner les variables globales au template
            $messenger->assign_vars([
                'USERNAME'         => $author_name,
                'DIGEST_SINCE'     => $since_time_formatted,
                'DIGEST_UNTIL'     => date('d/m/Y H:i'),
                'DIGEST_SIGNATURE' => sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $this->config['sitename']),
            ]);

            // 5. Itérer sur les posts et les réactions pour peupler les blocs du template
            foreach ($data['posts'] as $post_data)
            {
                $messenger->assign_block_vars('posts', [
                    'SUBJECT_PLAIN'     => $post_data['SUBJECT_PLAIN'],
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                foreach ($post_data['reactions'] as $reaction)
                {
                    $messenger->assign_block_vars('posts.reactions', $reaction);
                }
            }

            // 6. Envoyer l'e-mail
            $messenger->send(NOTIFY_EMAIL);

            $message = "$log_prefix E-mail digest envoyé à $author_name ($author_email) avec " . count($data['mark_ids']) . ' réactions.';
            if ($io) $io->writeln("<info>$message</info>");
            else error_log($message);

            return [
                'status' => 'sent',
            ];
        }
        catch (\Exception $e)
        {
            $error_message = "$log_prefix Exception in send_digest_email for user_id $author_id: " . $e->getMessage();
            $error_details = "$log_prefix Exception details: File: " . $e->getFile() . " Line: " . $e->getLine();

            if ($io) {
                $io->writeln("<error>$error_message</error>");
                $io->writeln("<error>$error_details</error>");
            } else {
                error_log($error_message);
                error_log($error_details);
            }

            return [
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
