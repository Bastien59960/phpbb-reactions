<?php

namespace bastien59\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
    protected $config;
    protected $template;

    public function __construct(\phpbb\config\config $config, \phpbb\template\template $template)
    {
        $this->config = $config;
        $this->template = $template;
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => 'load_language_on_setup',
        );
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'bastien59/reactions',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }
}
