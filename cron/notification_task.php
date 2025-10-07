<?php
/**
 * Fichier : cron/notification_task.php — bastien59960/reactions/cron/notification_task.php
 *
 * Tâche cron pour l'envoi groupé des notifications de réactions par e-mail (digest).
 *
 * Ce fichier exécute périodiquement l'envoi des résumés de réactions reçues par les utilisateurs, en respectant la fenêtre anti-spam configurée.
 *
 * Points clés de la logique métier :
 *   - Agrégation des réactions sur une période donnée
 *   - Génération et envoi des e-mails de résumé
 *   - Respect des préférences utilisateur (opt-in/out)
 *   - Nettoyage des notifications orphelines si besoin
 *   - Gestion des erreurs et logs pour le suivi
 *
 * Ce fichier est appelé automatiquement par le système de tâches planifiées de phpBB.
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

    /** @var string Nom de la table des réactions (ex: phpbb_post_reactions) */
    protected $post_reactions_table;

    /** @var string Chemin racine phpBB (ex: './') */
    protected $phpbb_root_path;

    /** @var string Extension des fichiers php (ex: 'php') */
    protected $php_ext;

    /**
     * Constructeur
     *
     * Note : l'ordre des arguments doit rester compatible avec l'appel depuis
     * services.yml de l'extension (ou la déclaration d'enregistrement de la tâche).
     *
     * @param \phpbb\db\driver\driver_interface   $db
     * @param \phpbb\config\config                $config
     * @param \phpbb\notification\manager         $notification_manager
     * @param \phpbb\user_loader                  $user_loader
     * @param string                              $post_reactions_table  Table des réactions (extension)
     * @param string                              $phpbb_root_path
     * @param string                              $php_ext
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\config\config $config,
        \phpbb\notification\manager $notification_manager,
        \phpbb\user_loader $user_loader,
        $post_reactions_table,
        $phpbb_root_path,
        $php_ext
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->notification_manager = $notification_manager;
        $this->user_loader = $user_loader;
        $this->post_reactions_table = $post_reactions_table;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
    }

    /**
     * Nom de la tâche
     *
     * Doit être unique (ex : 'cron.task.reactions.notification_task')
     *
     * @return string
     */
    public function get_name()
    {
        return 'cron.task.reactions.notification_task';
    }

    /**
     * Condition d'exécution — ici on laisse phpBB décider (on renvoie true),
     * mais run() vérifie par la suite si le paramètre de délai est configuré.
     *
     * @return bool
     */
    public function can_run()
    {
        return true;
    }

    /**
     * Méthode principale exécutée par le cron.
     *
     * Elle :
     *  - récupère les réactions éligibles,
     *  - construit des e-mails groupés par utilisateur (destinataire),
     *  - envoie via messenger.tpl (language/email/reaction_recap).txt),
     *  - marque les réactions comme notifiées si l'envoi a réussi (ou si l'user
     *    a désactivé les emails).
     *
     * Important : on n'envoie pas d'e-mail si la préférence 'disable_cron_email'
     * est activée pour l'utilisateur destinataire.
     *
     * @return void
     */
    public function run()
    {
        // -------------------------
        // 0) Récupérer le délai anti-spam
        // -------------------------
        $spam_delay = (int) ($this->config['bastien59960_reactions_spam_time'] ?? 2700);
        if ($spam_delay <= 0)
        {
            // Si configuré à 0 => pas d'envoi par cron (comportement choisi).
            return;
        }

        // Seuil chronologique (les réactions antérieures à $threshold_timestamp sont traitées)
        $threshold_timestamp = time() - $spam_delay;

        // -------------------------
        // 1) Récupérer toutes les réactions non notifiées plus anciennes que le seuil
        //    On récupère aussi : post_id, auteur du post (author_id), emoji, timestamp,
        //    le nom du réacteur (pour l'affichage).
        // -------------------------
        $sql = 'SELECT r.reaction_id, r.post_id, r.user_id AS reacter_id, r.reaction_emoji, r.reaction_time,
                       p.poster_id AS author_id, p.post_subject,
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

        // Structure de regroupement :
        // $by_author[author_id] = [
        //     'author_id' => int,
        //     'author_name' => string,
        //     'author_email' => string,
        //     'author_lang' => string,
        //     'posts' => [
        //         post_id => [
        //             'post_subject' => string,
        //             'reactions' => [
        //                 [ 'reaction_id' => int, 'reacter_id' => int, 'reacter_name' => string, 'emoji' => string, 'time' => int ],
        //                 ...
        //             ]
        //         ],
        //     ],
        //     'mark_ids' => [reaction_id,...]  // pour marquer comme notifié si envoi OK / préférence
        // ];
        $by_author = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
            $reaction_id   = (int) $row['reaction_id'];
            $post_id       = (int) $row['post_id'];
            $author_id     = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $author_name   = $row['author_name'] ?? '';
            $author_email  = $row['author_email'] ?? '';
            $author_lang   = $row['author_lang'] ?? '';
            $post_subject  = $row['post_subject'] ?? '';
            $reacter_id    = (int) ($row['reacter_id'] ?? 0);
            $reacter_name  = $row['reacter_name'] ?? '';
            $emoji         = $row['reaction_emoji'] ?? '';
            $r_time        = (int) ($row['reaction_time'] ?? 0);

            if ($author_id <= 0)
            {
                // pas d'auteur trouvé (post orphan?) -> on marque pour éviter boucles
                // mais on continue (on pourrait aussi logger).
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

        // Si rien à traiter -> sortie
        if (empty($by_author))
        {
            return;
        }

        // -------------------------
        // Préparer l'envoi : inclure messenger du core phpBB
        // -------------------------
        // Le messenger est dans includes/functions_messenger.php
        // On inclut avec le phpbb_root_path et le php_ext passés au constructeur.
        include_once($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);

        // -------------------------
        // 2) Pour chaque destinataire (author), vérifier prefs & envoyer mail groupé
        // -------------------------
        foreach ($by_author as $author_id => $data)
        {
            $author_email = $data['author_email'];
            $author_name  = $data['author_name'] ?: 'Utilisateur';
            $author_lang  = $data['author_lang'] ?: 'en';

            // Si pas d'email (compte supprimé ou email vide) : marquer les réactions comme traitées (on ne peut pas envoyer)
            if (empty($author_email))
            {
                // marque comme traitées pour éviter réessais infinis
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // 2.a) Vérifier préférence utilisateur : disable_cron_email
            // On suppose l'existence d'une table phpbb_reactions_user_prefs(user_id, disable_bell, disable_cron_email)
            // Si la table n'existe pas, on considère que l'utilisateur accepte les emails (comportement par défaut).
            $disable_cron_email = $this->get_user_disable_cron_email_pref($author_id);

            if ($disable_cron_email)
            {
                // L'utilisateur a désactivé les récap emails -> on ne l'envoie pas,
                // mais on marque les réactions comme notifiées (on suppose qu'il ne veut pas d'historique par email).
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // 2.b) Construire le contenu du récapitulatif
            // Format : une ligne par réaction, triées par timestamp asc (déjà ordonnées globalement).
            // Ex : [2025-10-06 14:23] alice a réagi avec 👍 à votre message "Hello world"
            $recap_lines_arr = [];
            foreach ($data['posts'] as $post_id => $post_data)
            {
                $post_subject = $post_data['post_subject'] ?: '[no subject]';
                foreach ($post_data['reactions'] as $reaction)
                {
                    // Format de la date (YYYY-MM-DD HH:MM)
                    $ts = $reaction['time'];
                    // Utilisation d'un format lisible, tu peux adapter avec user->format_date si tu l'ajoutes au constructeur.
                    $when = date('Y-m-d H:i', (int) $ts);

                    $reactor = $reaction['reacter_name'] ?: ('#' . $reaction['reacter_id']);
                    $emoji = $reaction['emoji'] !== '' ? $reaction['emoji'] : '(emoji)';
                    $recap_lines_arr[] = sprintf(
                        '[%s] %s a réagi avec %s à votre message "%s"',
                        $when,
                        $reactor,
                        $emoji,
                        $post_subject
                    );
                }
            }

            // Si aucune ligne -> on marque et continue (sécurité)
            if (empty($recap_lines_arr))
            {
                $this->mark_reactions_as_handled($data['mark_ids']);
                continue;
            }

            // Composer le corps / variables de template
            $recap_text = implode("\n", $recap_lines_arr);

            // SINCE_TIME : affichage du seuil (point de départ du regroupement)
            $since_time = date('Y-m-d H:i', $threshold_timestamp);

            // 2.c) Envoi via messenger
            try
            {
                // Créer le messenger (false -> pas d'envoi IM/jabber)
                $messenger = new \messenger(false);

                // Sélectionner le template d'email (doit exister dans language/<lang>/email/reaction_recap.txt)
                // Le 2e argument est la langue du destinataire ('fr', 'en', ...)
                $messenger->template('reaction_recap', $author_lang);

                // Sujet : nous utilisons une clé de langue 'EMAIL_REACTION_RECAP_SUBJECT' si disponible,
                // sinon un fallback simple.
                // Note : ici on ne dispose pas de $this->language, donc on peut charger la langue via user_loader
                // ou laisser le template définir le sujet via la ligne "Subject: ..." dans fichier de langue.
                // Pour la simplicité, on passe un subject minimal (le template peut aussi avoir le Subject:)
                $subject = 'Nouvelles réactions sur vos messages';

                $messenger->subject($subject);

                // Destinataire
                $messenger->to($author_email, $author_name);

                // Variables passées au template (nommées en majuscules pour la compatibilité)
                $messenger->assign_vars(array(
                    'USERNAME'    => $author_name,
                    'SINCE_TIME'  => $since_time,
                    'RECAP_LINES' => $recap_text,
                ));

                // Envoi immédiat par e-mail
                $messenger->send(NOTIFY_EMAIL);
            }
            catch (\Exception $e)
            {
                // En cas d'erreur d'envoi, on ne marque pas les réactions (elles seront réessayées
                // au prochain passage du cron). On log l'erreur (ici trigger_error pour être simple).
                trigger_error('Reactions cron: échec envoi mail pour user_id ' . $author_id . ' : ' . $e->getMessage(), E_USER_WARNING);
                continue; // passer au destinataire suivant
            }

            // 2.d) Si on arrive ici, l'envoi a réussi -> marquer comme notifiées
            $this->mark_reactions_as_handled($data['mark_ids']);
        }

        // Fin run()
    }

    /**
     * Marque les réactions en base comme notifiées (reaction_notified = 1).
     *
     * @param array $ids Liste d'IDs de réaction (entiers)
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
     * Récupère la préférence disable_cron_email pour un utilisateur.
     * Si la table de prefs n'existe pas ou si la colonne est absente -> on retourne false.
     *
     * @param int $user_id
     * @return bool
     */
    protected function get_user_disable_cron_email_pref($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0)
        {
            return false;
        }

        // Vérifier si la table phpbb_reactions_user_prefs existe
        $check_sql = "SHOW TABLES LIKE $this->table_prefix . 'reactions_user_prefs'";
        $res = $this->db->sql_query($check_sql);
        $exists = (bool) $this->db->sql_fetchrow($res);
        $this->db->sql_freeresult($res);

        if (!$exists)
        {
            // Table absente -> comportement par défaut : ne pas bloquer l'envoi
            return false;
        }

// --- remplacement proposé ---
$sql = 'SELECT user_reactions_cron_email
        FROM ' . $this->table_prefix . "users
        WHERE user_id = " . (int) $user_id;
$result = $this->db->sql_query($sql);
$row = $this->db->sql_fetchrow($result);
$this->db->sql_freeresult($result);

// Si la colonne existe mais pas de ligne (improbable), on considère les mails activés
if (!$row)
{
    return false;
}

// user_reactions_cron_email : 1 = reçoit les e-mails, 0 = désactivé
return ((int) $row['user_reactions_cron_email']) === 0;

    }
}
