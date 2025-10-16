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

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\language\language */
    protected $language;

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
        $this->template = $template; // Ligne essentielle à restaurer
        $this->post_reactions_table = $post_reactions_table;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->table_prefix = $table_prefix;
    }

    /**
     * Nom de la tâche
     */
    public function get_name()
    {
        return 'cron.task.reactions.notification_task';
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

        error_log('[Reactions Cron] Run start (interval=' . $spam_minutes . ' min, threshold=' . date('Y-m-d H:i:s', $threshold_timestamp) . ')');

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

        // Structure de regroupement par auteur
        $by_author = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
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
                $this->mark_reactions_as_handled([$reaction_id]);
                continue;
            }

            $subject_plain = ($post_subject !== '') ? strip_tags($post_subject) : $this->language->lang('NO_SUBJECT');
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
                error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (aucune adresse e-mail).');
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Vérifier préférence utilisateur
            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email === true)
            {
                $skipped_pref += $reaction_total_for_author;
                error_log('[Reactions Cron] Skip user_id ' . $author_id . ' (préférence e-mail désactivée).');
                // Utilisateur a désactivé les récap emails
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Si, après tous les filtres, il n'y a plus de posts avec des réactions valides, on ignore.
            if (empty($data['posts']))
            {
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // TRANSFORMATION CRUCIALE : Convertir le tableau associatif des posts en tableau indexé.
            $data['posts'] = array_values($data['posts']);

            // Envoyer l'e-mail récapitulatif
            $result = $this->send_digest_email($data, $threshold_timestamp);

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
                error_log('[Reactions Cron] Envoi non abouti pour user_id ' . $author_id . ' (status=' . $result['status'] . ').');
            }
        }

        $this->config->set('bastien59960_reactions_cron_last_run', time());

        $duration_ms = (microtime(true) - $run_start) * 1000;
        error_log(sprintf(
            '[Reactions Cron] Run complete in %.1fms (authors=%d, reactions=%d, sent=%d, no_email=%d, pref_off=%d, empty=%d, failed=%d)',
            $duration_ms,
            $processed_authors,
            $processed_reactions,
            $sent_reactions,
            $skipped_no_email,
            $skipped_pref,
            $skipped_empty,
            $skipped_failed
        ));
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
     * @param int $threshold_timestamp Timestamp du seuil pour le message
     * @return array{status:string,error?:string} Statut d'envoi (sent, skipped_empty, failed).
     */
    protected function send_digest_email(array $data, int $threshold_timestamp)
    {
        $author_id    = (int) $data['author_id'];
        $author_email = $data['author_email'];
        $author_name  = $data['author_name'] ?: 'Utilisateur';
        $author_lang  = $data['author_lang'] ?: 'en';
        $since_time = date('d/m/Y H:i', $threshold_timestamp);

        if (empty($data['posts']))
        {
            error_log('[Reactions Cron] Aucun contenu à envoyer pour user_id ' . $author_id . ' (récapitulatif vide).');
            return ['status' => 'skipped_empty'];
        }

        try
        {
            $messenger = new \messenger(false, $this->template);

            // Définir explicitement le template HTML
            $messenger->template('reaction_digest', 'bastien59960_reactions', $author_lang);
            $messenger->to($author_email, $author_name);

            // Ajouter les headers pour HTML et UTF-8
            $messenger->headers('Content-type: text/html; charset=UTF-8');
            $messenger->headers('Content-Transfer-Encoding: 8bit');

            // Charger la langue pour l'utilisateur cible
            $this->language->add_lang('reactions', 'bastien59960/reactions', false, $author_lang);

            // Variables globales pour le template
            $messenger->assign_vars([
                'USERNAME'         => htmlspecialchars($author_name),
                'DIGEST_SINCE'     => $since_time,
                'DIGEST_UNTIL'     => date('d/m/Y H:i'),
                'DIGEST_SIGNATURE' => sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $this->config['sitename']),
            ]);

            // Log pour déboguer les données
            error_log('[Reactions Cron] Données posts pour user_id ' . $author_id . ': ' . json_encode($data['posts']));

            // Itérer sur les posts et les assigner comme des blocs au template
            foreach ($data['posts'] as $post_data)
            {
                // Étape 1 : Assigner les données du bloc parent 'posts'
                $messenger->assign_block_vars('posts', [
                    'SUBJECT_PLAIN'     => htmlspecialchars($post_data['SUBJECT_PLAIN']),
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                // Étape 2 : Itérer sur les réactions et les assigner au sous-bloc 'posts.reactions'
                foreach ($post_data['reactions'] as $reaction)
                {
                    // La syntaxe correcte est d'utiliser le nom du bloc parent comme préfixe.
                    $messenger->assign_block_vars('posts.reactions', [
                        'REACTER_NAME'         => htmlspecialchars($reaction['REACTER_NAME']),
                        'EMOJI'                => $reaction['EMOJI'],
                        'TIME_FORMATTED'       => $reaction['TIME_FORMATTED'],
                        'PROFILE_URL_ABSOLUTE' => $reaction['PROFILE_URL_ABSOLUTE'],
                    ]);
                }
            }

            $messenger->send(NOTIFY_EMAIL);

            error_log('[Reactions Cron] E-mail digest envoyé à ' . $author_name . ' (' . $author_email . ') avec ' . count($data['mark_ids']) . ' réactions.');
            return [
                'status' => 'sent',
            ];
        }
        catch (\Exception $e)
        {
            // Log d'erreur amélioré
            $error_details = "File: " . $e->getFile() . "\n" .
                             "Line: " . $e->getLine() . "\n" .
                             "Trace: " . $e->getTraceAsString();
            error_log('[Reactions Cron] Échec critique lors de l\'envoi de l\'e-mail pour user_id ' . $author_id . ' : ' . $e->getMessage());
            error_log('[Reactions Cron] Détails de l\'erreur: ' . $error_details);
            return [
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
