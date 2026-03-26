<?php
/**
 * Digest messenger with a CLI-safe Twig cache path.
 *
 * @package bastien59960/reactions
 * @license GPL-2.0-only
 */

namespace bastien59960\reactions\cron;

class digest_messenger extends \messenger
{
    /** @var string */
    protected $cache_path;

    public function __construct($cache_path, $use_queue = true)
    {
        $this->cache_path = (string) $cache_path;
        parent::__construct($use_queue);
    }

    protected function setup_template()
    {
        global $phpbb_container, $phpbb_dispatcher;

        if ($this->template instanceof \phpbb\template\template)
        {
            return;
        }

        $cache_path = $this->resolve_cache_path($this->cache_path);

        $template_environment = new \phpbb\template\twig\environment(
            $phpbb_container->get('config'),
            $phpbb_container->get('filesystem'),
            $phpbb_container->get('path_helper'),
            $cache_path,
            $phpbb_container->get('ext.manager'),
            new \phpbb\template\twig\loader(
                $phpbb_container->get('filesystem')
            ),
            $phpbb_dispatcher,
            array()
        );
        $template_environment->setLexer($phpbb_container->get('template.twig.lexer'));

        $this->template = new \phpbb\template\twig\twig(
            $phpbb_container->get('path_helper'),
            $phpbb_container->get('config'),
            new \phpbb\template\context(),
            $template_environment,
            $cache_path,
            $phpbb_container->get('user'),
            $phpbb_container->get('template.twig.extensions.collection'),
            $phpbb_container->get('ext.manager')
        );
    }

    protected function resolve_cache_path($preferred_path)
    {
        $candidates = array();
        $preferred_path = rtrim((string) $preferred_path, '/\\');
        if ($preferred_path !== '')
        {
            $candidates[] = $preferred_path;
        }

        $tmp_path = rtrim(sys_get_temp_dir(), '/\\') . '/phpbb_reactions_mail_twig_cache';
        if (!in_array($tmp_path, $candidates, true))
        {
            $candidates[] = $tmp_path;
        }

        foreach ($candidates as $path)
        {
            if (!is_dir($path))
            {
                @mkdir($path, 0775, true);
            }

            if (is_dir($path) && is_writable($path))
            {
                return $path;
            }
        }

        return $preferred_path !== '' ? $preferred_path : $tmp_path;
    }
}
