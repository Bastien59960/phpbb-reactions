<?php
/**
 * Fichier : unsubscribe.php
 * Chemin : bastien59960/reactions/controller/unsubscribe.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Contrôleur de désabonnement en un clic pour les emails de résumé de réactions.
 * Valide le token HMAC et met à jour la préférence utilisateur en base.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\db\tools\tools_interface;
use phpbb\language\language;
use phpbb\request\request;
use phpbb\template\template;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IN_PHPBB'))
{
    exit;
}

class unsubscribe
{
    protected $config;
    protected $db;
    protected $language;
    protected $request;
    protected $template;
    protected $db_tools;
    protected $table_prefix;

    public function __construct(
        config $config,
        driver_interface $db,
        language $language,
        request $request,
        template $template,
        tools_interface $db_tools,
        $table_prefix
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->db_tools = $db_tools;
        $this->table_prefix = (string) $table_prefix;
    }

    public function handle(): Response
    {
        $user_id = (int) $this->request->variable('u', 0);
        $token = $this->request->variable('token', '');

        $http_status = 400;
        $event_status = 'invalid_request';
        $user_email = '';
        $message = 'Lien de desabonnement invalide ou expire.';

        if ($user_id > 0 && $token !== '')
        {
            $expected = hash_hmac('sha256', 'reactions_unsub_' . $user_id, $this->config['cookie_seed']);
            if (hash_equals($expected, $token))
            {
                $sql = 'SELECT user_email, user_reactions_cron_email
                    FROM ' . USERS_TABLE . '
                    WHERE user_id = ' . (int) $user_id;
                $result = $this->db->sql_query_limit($sql, 1);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if (!$row)
                {
                    $http_status = 404;
                    $event_status = 'user_not_found';
                    $message = 'Compte introuvable.';
                }
                else
                {
                    $user_email = (string) ($row['user_email'] ?? '');
                    $was_subscribed = ((int) ($row['user_reactions_cron_email'] ?? 0) === 1);

                    if ($was_subscribed)
                    {
                        $this->db->sql_query(
                            'UPDATE ' . USERS_TABLE . ' SET user_reactions_cron_email = 0 WHERE user_id = ' . (int) $user_id
                        );
                    }

                    $http_status = 200;
                    $event_status = $was_subscribed ? 'unsubscribed' : 'already_unsubscribed';
                    $message = $was_subscribed
                        ? 'Vous avez ete desabonne des notifications de reactions par email.'
                        : 'Vous etiez deja desabonne des notifications de reactions par email.';
                }
            }
            else
            {
                $http_status = 403;
                $event_status = 'invalid_signature';
            }
        }

        $this->log_adminhelper_unsubscribe_event($event_status, $http_status, [
            'user_id' => $user_id,
            'user_email' => $user_email,
        ]);

        // Simple HTML response (no full page needed)
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Desabonnement</title></head><body><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        return new Response($html, (int) $http_status);
    }

    private function log_adminhelper_unsubscribe_event($status, $http_status, array $context = [])
    {
        if (!$this->is_adminhelper_unsubscribe_log_available())
        {
            return;
        }

        $log_data = [
            'user_id' => isset($context['user_id']) ? (int) $context['user_id'] : 0,
            'user_email' => isset($context['user_email']) ? substr(trim((string) $context['user_email']), 0, 255) : '',
            'unsubscribe_type' => 'reactions_notify',
            'token_expires_at' => 0,
            'http_status' => (int) $http_status,
            'event_status' => substr(strtolower(trim((string) $status)), 0, 32),
            'request_method' => substr(strtoupper((string) $this->request->server('REQUEST_METHOD', 'GET')), 0, 8),
            'request_ip' => substr((string) $this->request->server('REMOTE_ADDR', ''), 0, 40),
            'request_user_agent' => substr((string) $this->request->server('HTTP_USER_AGENT', ''), 0, 255),
            'logged_at' => time(),
        ];

        $sql = 'INSERT INTO ' . $this->get_adminhelper_unsubscribe_log_table() . ' '
            . $this->db->sql_build_array('INSERT', $log_data);
        $this->db->sql_query($sql);
    }

    private function is_adminhelper_unsubscribe_log_available()
    {
        static $table_exists = null;
        if ($table_exists !== null)
        {
            return (bool) $table_exists;
        }

        try
        {
            $table_exists = (bool) $this->db_tools->sql_table_exists($this->get_adminhelper_unsubscribe_log_table());
        }
        catch (\Throwable $e)
        {
            $table_exists = false;
        }

        return (bool) $table_exists;
    }

    private function get_adminhelper_unsubscribe_log_table()
    {
        return $this->table_prefix . 'adminhelper_unsubscribe_log';
    }
}
