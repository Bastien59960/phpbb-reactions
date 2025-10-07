<?php
/**
 * Fichier : ext.php — bastien59960/reactions/ext.php
 *
 * Classe principale de l'extension Reactions pour phpBB.
 *
 * Ce fichier gère l'activation, la désactivation et la purge de l'extension, ainsi que l'enregistrement des types de notifications personnalisés auprès du système phpBB.
 *
 * Points clés de la logique métier :
 *   - Vérification de la compatibilité phpBB
 *   - Enregistrement/désactivation/purge des types de notifications lors des changements d'état de l'extension
 *   - Gestion de la version de l'extension (pour les migrations)
 *
 * Ce fichier est le point d'entrée de l'extension pour phpBB et doit être présent pour que l'extension soit reconnue et gérée correctement.
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
