<?php
namespace bastien59960\reactions\event;

class listener
{
    public function test_event($event)
    {
        $event['post_row']['REACTIONS'] = '<div style="background:red;color:white;padding:10px;margin:5px 0;">TEST ÉVÉNEMENT PHP FONCTIONNE!</div>';
    }
}
