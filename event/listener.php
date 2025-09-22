namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return [
            'core.viewtopic_modify_post_row' => 'test_event',
        ];
    }

    public function test_event($event)
    {
        // Exemple : ajouter un template statique après le message
        $post_row = $event['post_row'];
        $post_row['MESSAGE'] .= '<div style="background:blue;color:white;">TEMPLATE STATIQUE CHARGÉ (BON EMPLACEMENT) !</div>';
        $event['post_row'] = $post_row;
    }
}
