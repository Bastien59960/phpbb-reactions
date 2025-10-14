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
        $this->template = $template;
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
        // Récupérer le délai anti-spam (défaut: 2700s = 45 minutes)
        $spam_delay = (int) ($this->config['bastien59960_reactions_spam_time'] ?? 2700);
        if ($spam_delay <= 0)
        {
            // Si configuré à 0 => pas d'envoi par cron
            return;
        }

        // Seuil chronologique
        $threshold_timestamp = time() - $spam_delay;

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

            if (!isset($by_author[$author_id]['posts'][$post_id]))
            {
                $by_author[$author_id]['posts'][$post_id] = [
                    'topic_id'     => $topic_id,
                    'post_subject' => $post_subject,
                    'reactions' => [],
                ];
            }

            $by_author[$author_id]['posts'][$post_id]['reactions'][] = [
                'reaction_id'  => $reaction_id,
                'reacter_id'   => $reacter_id,
                'reacter_name' => $reacter_name,
                'emoji'        => $emoji,
                'time'         => $r_time,
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

        // Pour chaque destinataire, envoyer un e-mail groupé
        foreach ($by_author as $author_id => $data)
        {
            $author_email = $data['author_email'];
            $author_name  = $data['author_name'] ?: 'Utilisateur';
            $author_lang  = $data['author_lang'] ?: 'en';

            // Si pas d'email
            if (empty($author_email))
            {
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Vérifier préférence utilisateur
            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email)
            {
                // Utilisateur a désactivé les récap emails
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Envoyer l'e-mail récapitulatif
            $email_sent = $this->send_digest_email($data, $threshold_timestamp);

            // Si l'e-mail a été envoyé (ou si l'utilisateur ne le voulait pas), marquer les réactions comme notifiées
            if ($email_sent)
            {
                $this->mark_reactions_as_handled($data['mark_ids']);
            }
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

        // Vérifier directement dans la table users (colonne user_reactions_cron_email)
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

        // user_reactions_cron_email : 1 = reçoit les e-mails, 0 = désactivé
        return ((int) $row['user_reactions_cron_email']) === 0;
    }

    /**
     * Indique si la tâche doit s'exécuter (appelé par phpBB)
     * On s'exécute toutes les heures (3600 secondes)
     */
    public function should_run()
    {
        $last_run = (int) $this->config['bastien59960_reactions_cron_last_run'];
        $interval = 3600; // 1 heure

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
     * @return bool True si l'e-mail a été envoyé ou si l'utilisateur ne le souhaitait pas, false en cas d'échec.
     */
    protected function send_digest_email(array $data, int $threshold_timestamp)
    {
        $author_id    = (int) $data['author_id'];
        $author_email = $data['author_email'];
        $author_name  = $data['author_name'] ?: 'Utilisateur';
        $author_lang  = $data['author_lang'] ?: 'en';

        // Construire le contenu textuel du récapitulatif
        $recap_lines_arr = [];
        foreach ($data['posts'] as $post_id => $post_data)
        {
            $post_subject = $post_data['post_subject'] ?: '[sans sujet]';
            foreach ($post_data['reactions'] as $reaction)
            {
                $when = date('d/m/Y H:i', (int) $reaction['time']);
                $reactor = $reaction['reacter_name'] ?: ('Utilisateur #' . $reaction['reacter_id']);
                $emoji = $reaction['emoji'] ?: '?';
                $recap_lines_arr[] = sprintf(
                    '- Le %s, %s a réagi avec %s à votre message : "%s"',
                    $when, $reactor, $emoji, $post_subject
                );
            }
        }

        if (empty($recap_lines_arr))
        {
            return true; // Rien à envoyer, on peut marquer comme traité
        }

        $recap_text = implode("\n", $recap_lines_arr);
        $since_time = date('d/m/Y H:i', $threshold_timestamp);

        try
        {
            $messenger = new \messenger(null, $this->template, null, null, null, null, true);
            
            $messenger->template('@bastien59960_reactions/reaction_digest', $author_lang);
            $messenger->to($author_email, $author_name);
            $messenger->assign_vars([
                'USERNAME'    => $author_name,
                'SINCE_TIME'  => $since_time,
                'RECAP_LINES' => $recap_text,
            ]);
            $messenger->send(NOTIFY_EMAIL);

            error_log('[Reactions Cron] E-mail digest envoyé à ' . $author_name . ' (' . $author_email . ') avec ' . count($data['mark_ids']) . ' réactions.');
            return true;
        }
        catch (\Exception $e)
        {
            error_log('[Reactions Cron] Échec envoi mail pour user_id ' . $author_id . ' : ' . $e->getMessage());
            return false;
        }
    }
}
