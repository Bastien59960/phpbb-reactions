<?php
/**
 * Fichier : notification_task.php
 * Chemin : bastien59960/reactions/cron/notification_task.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit la tâche cron pour l'envoi groupé des notifications de réactions par
 * e-mail (digest). Elle se déclenche périodiquement pour regrouper les nouvelles
 * réactions et envoyer un résumé aux utilisateurs concernés, évitant ainsi le spam.
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

    /** @var bool Flag pour vérifier si le namespace Twig a été enregistré */
    protected $twig_namespace_registered = false;

    /**
     * Constructeur
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,                   // 1. @dbal.conn
        \phpbb\config\config $config,                           // 2. @config
        \phpbb\notification\manager $notification_manager,       // 3. @notification_manager
        \phpbb\user_loader $user_loader,                        // 4. @user_loader
        \phpbb\language\language $language,                     // 5. @language
        \phpbb\template\template $template,                     // 6. @template
        $post_reactions_table,                                 // 7. %tables.post_reactions%
        $phpbb_root_path,                                      // 8. %core.root_path%
        $php_ext,                                              // 9. %core.php_ext%
        $table_prefix                                          // 10. %core.table_prefix%
    ) {
        // 2. Assignation de toutes les dépendances aux propriétés de la classe.
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

        $start_message = '[Reactions Cron] Run start (interval=' . $spam_minutes . ' min, threshold=' . date('Y-m-d H:i:s', $threshold_timestamp) . ')';
        error_log($start_message);

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
        error_log($found_message);

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
                error_log($orphan_message);
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
                error_log($self_react_message);
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
            error_log($nothing_message);
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
                error_log($skip_message);
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Vérifier préférence utilisateur
            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email === true)
            {
                $skipped_pref += $reaction_total_for_author;
                $skip_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (préférence e-mail désactivée).';
                error_log($skip_message);
                // Utilisateur a désactivé les récap emails
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Si, après tous les filtres, il n'y a plus de posts avec des réactions valides, on ignore.
            if (empty($data['posts']))
            {
                $empty_posts_message = '[Reactions Cron] Skip user_id ' . $author_id . ' (no valid posts left after filtering).';
                error_log($empty_posts_message);
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // TRANSFORMATION CRUCIALE : Convertir le tableau associatif des posts en tableau indexé.
            $data['posts'] = array_values($data['posts']);

            $since_time_formatted = date('d/m/Y H:i', $threshold_timestamp);
            // Envoyer l'e-mail récapitulatif
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
                $fail_message = '[Reactions Cron] Envoi non abouti pour user_id ' . $author_id . ' (status=' . $result['status'] . ').';
                error_log($fail_message);
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
     * Enregistre le namespace Twig pour l'extension (phpBB 3.3 compatible)
     */
    protected function register_twig_namespace()
    {
        if ($this->twig_namespace_registered)
        {
            return; // Déjà enregistré
        }

        $log_prefix = '[Reactions Cron]';

        try
        {
            // Pour phpBB 3.3, on doit accéder directement à la propriété protégée
            // du template pour récupérer l'environnement Twig
            $reflection = new \ReflectionClass($this->template);
            
            // Chercher la propriété 'twig' (phpBB 3.3)
            if ($reflection->hasProperty('twig'))
            {
                $twig_property = $reflection->getProperty('twig');
                $twig_property->setAccessible(true);
                $twig = $twig_property->getValue($this->template);
                
                if ($twig instanceof \Twig\Environment || $twig instanceof \Twig_Environment)
                {
                    $twig_loader = $twig->getLoader();
                    $extension_template_path = $this->phpbb_root_path . 'ext/bastien59960/reactions/styles/all/template';
                    
                    // Vérifier que le chemin existe
                    if (!is_dir($extension_template_path))
                    {
                        error_log("$log_prefix ERREUR: Le chemin du template n'existe pas: $extension_template_path");
                        return;
                    }
                    
                    $twig_loader->addPath($extension_template_path, 'bastien59960_reactions');
                    $this->twig_namespace_registered = true;
                    
                    error_log("$log_prefix Namespace Twig 'bastien59960_reactions' enregistré vers: $extension_template_path");
                }
                else
                {
                    error_log("$log_prefix ERREUR: L'objet Twig n'est pas une instance valide");
                }
            }
            else
            {
                error_log("$log_prefix ERREUR: Propriété 'twig' non trouvée dans la classe template");
            }
        }
        catch (\ReflectionException $e)
        {
            error_log("$log_prefix ERREUR Reflection: " . $e->getMessage());
        }
        catch (\Exception $e)
        {
            error_log("$log_prefix ERREUR lors de l'enregistrement du namespace Twig: " . $e->getMessage());
        }
    }

    /**
     * Construit et envoie un e-mail récapitulatif à un utilisateur.
     *
     * @param array $data Données groupées pour l'auteur
     * @param string $since_time_formatted Date formatée du début de la période.
     * @return array{status:string,error?:string} Statut d'envoi (sent, skipped_empty, failed).
     */
    protected function send_digest_email(array $data, string $since_time_formatted)
    {
        $author_id    = (int) $data['author_id'];
        $author_email = $data['author_email'];
        $author_name  = $data['author_name'] ?: 'Utilisateur';
        $author_lang  = $data['author_lang'] ?: 'en';

        $log_prefix = '[Reactions Cron]';

        if (empty($data['posts']))
        {
            $message = "$log_prefix Aucun contenu à envoyer pour user_id $author_id (récapitulatif vide).";
            error_log($message);
            return ['status' => 'skipped_empty'];
        }

        try
        {
            // S'assurer que la classe messenger est disponible avant de l'utiliser.
            if (!class_exists('messenger'))
            {
                include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
            }

            $messenger = new messenger(false);

            // 1. Charger la langue de l'utilisateur
            $lang_load_message = "$log_prefix Chargement de la langue '$author_lang' pour user_id $author_id.";
            error_log($lang_load_message);

            $this->language->add_lang(['common', 'email'], 'bastien59960/reactions');

            // =====================================================================
            // CORRECTION CRITIQUE : Enregistrer le namespace Twig pour phpBB 3.3
            // =====================================================================
            $this->register_twig_namespace();

            // =====================================================================
            // Génération du corps de l'e-mail à partir du template
            // =====================================================================
            $this->template->set_filenames(['reaction_digest_body' => '@bastien59960_reactions/email/reaction_digest.txt']);

            // Assigner les variables globales au template
            $this->template->assign_vars([
                'USERNAME'         => $author_name,
                'DIGEST_SINCE'     => $since_time_formatted,
                'DIGEST_UNTIL'     => date('d/m/Y H:i'),
                'DIGEST_SIGNATURE' => sprintf($this->language->lang('REACTIONS_DIGEST_SIGNATURE'), $this->config['sitename']),
            ]);

            // Itérer sur les posts et les réactions pour peupler les blocs du template
            foreach ($data['posts'] as $post_data)
            {
                $this->template->assign_block_vars('posts', [
                    'SUBJECT_PLAIN'     => $post_data['SUBJECT_PLAIN'],
                    'POST_URL_ABSOLUTE' => $post_data['POST_URL_ABSOLUTE'],
                ]);

                foreach ($post_data['reactions'] as $reaction)
                {
                    $this->template->assign_block_vars('posts.reactions', $reaction);
                }
            }

            $email_body = $this->template->assign_display('reaction_digest_body');

            // 3. Définir le destinataire et le sujet
            $messenger->to($author_email, $author_name);
            $messenger->subject($this->language->lang('REACTIONS_DIGEST_SUBJECT'));

            // Injecter le corps de l'e-mail que nous avons construit manuellement
            $messenger->set_mail_body($email_body);

            // 6. Envoyer l'e-mail
            $messenger->send(NOTIFY_EMAIL);

            $message = "$log_prefix E-mail digest envoyé à $author_name ($author_email) avec " . count($data['mark_ids']) . ' réactions.';
            error_log($message);

            return [
                'status' => 'sent',
            ];
        }
        catch (\Exception $e)
        {
            $error_message = "$log_prefix Exception in send_digest_email for user_id $author_id: " . $e->getMessage();
            $error_details = "$log_prefix Exception details: File: " . $e->getFile() . " Line: " . $e->getLine();

            error_log($error_message);
            error_log($error_details);

            return [
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Retourne le nom de la tâche (clé de langue) pour l'afficher dans l'ACP.
     *
     * @return string
     */
    public function get_name()
    {
        return 'bastien59960.reactions.notification';
    }

    /**
     * Détermine si la tâche peut s'exécuter (conditions de base).
     *
     * @return bool
     */
    public function is_runnable()
    {
        // La tâche peut s'exécuter si l'envoi d'e-mails est activé sur le forum
        return (bool) $this->config['email_enable'];
    }
}