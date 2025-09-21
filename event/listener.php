<?php
namespace bastien59\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /**
     * Abonnement aux événements phpBB
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.viewtopic_modify_post_row' => 'add_reactions_block',
        ];
    }

    /**
     * Ajoute un bloc de réactions sous chaque post
     */
    public function add_reactions_block($event)
    {
        $post_row = $event['post_row'];

        // Pour l'instant, on crée juste un bloc vide
        $post_row['REACT_HTML'] = '<div class="post-reactions">Réactions à venir...</div>';

        $event['post_row'] = $post_row;
    }
}
