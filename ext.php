<?php
/**
 * Extension Reactions pour phpBB 3.3+
 * 
 * Cette extension permet aux utilisateurs de réagir aux messages du forum avec des emojis.
 * Elle inclut un système de notifications (cloche + email) avec anti-spam configurable.
 * 
 * Fonctionnalités principales :
 * - Réactions par emojis sur les messages
 * - Notifications immédiates par cloche
 * - Notifications par email avec délai anti-spam (45 min par défaut)
 * - Tooltips affichant les utilisateurs ayant réagi
 * - Limites configurables (max 20 types de réactions par post, 10 réactions par utilisateur)
 * - Support multilingue (FR/EN)
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions;

/**
 * Classe principale de l'extension
 * 
 * Gère l'activation, désactivation et la configuration des notifications
 * de l'extension Reactions.
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Vérifie si l'extension peut être activée
	 * 
	 * @return bool True si phpBB >= 3.3.0, False sinon
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.3.0', '>=');
	}

    /**
     * Retourne la version actuelle de l'extension
     * 
     * @return string Version de l'extension (doit correspondre aux migrations)
     */
    public function get_version()
    {
        return '1.0.1';
    }

    /**
     * Étape d'activation de l'extension
     * 
     * Active les notifications de l'extension dans le système phpBB.
     * Cette méthode est appelée lors de l'activation de l'extension.
     * 
     * @param mixed $old_state État précédent de l'extension
     * @return string|mixed 'notification' si première activation, sinon résultat parent
     */
    public function enable_step($old_state)
    {
        if ($old_state === false)
        {
            // Activer le type de notification dans le système phpBB
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->enable_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::enable_step($old_state);
    }

    /**
     * Étape de désactivation de l'extension
     * 
     * Désactive les notifications de l'extension dans le système phpBB.
     * Cette méthode est appelée lors de la désactivation de l'extension.
     * 
     * @param mixed $old_state État précédent de l'extension
     * @return string|mixed 'notification' si première désactivation, sinon résultat parent
     */
    public function disable_step($old_state)
    {
        if ($old_state === false)
        {
            // Désactiver le type de notification dans le système phpBB
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->disable_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::disable_step($old_state);
    }

    /**
     * Étape de purge de l'extension
     * 
     * Supprime toutes les notifications de l'extension.
     * Cette méthode est appelée lors de la suppression de l'extension.
     * 
     * @param mixed $old_state État précédent de l'extension
     * @return string|mixed 'notification' si première purge, sinon résultat parent
     */
    public function purge_step($old_state)
    {
        if ($old_state === false)
        {
            // Supprimer toutes les notifications de l'extension
            $notification_manager = $this->container->get('notification_manager');
            $notification_manager->purge_notifications('bastien59960.reactions.notification');
            return 'notification';
        }
        return parent::purge_step($old_state);
    }
}
