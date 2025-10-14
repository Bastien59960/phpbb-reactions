<?php
/**
 * Fichier : ext.php â€” bastien59960/reactions/ext.php
 *
 * Classe principale de l'extension Reactions pour phpBB.
 *
 * Ce fichier gÃ¨re l'activation, la dÃ©sactivation et la purge de l'extension, 
 * ainsi que l'enregistrement des types de notifications personnalisÃ©s auprÃ¨s du systÃ¨me phpBB.
 *
 * Points clÃ©s de la logique mÃ©tier :
 *   - VÃ©rification de la compatibilitÃ© phpBB
 *   - Enregistrement/dÃ©sactivation/purge des types de notifications lors des changements d'Ã©tat de l'extension
 *   - Gestion de la version de l'extension (pour les migrations)
 *
 * IMPORTANT - Distinction entre NOM DE SERVICE et NOM DE TYPE :
 * 
 *   ðŸ“¦ NOM DE SERVICE (dans services.yml) : 
 *      'bastien59960.reactions.notification'
 *      â†’ UtilisÃ© par Symfony pour l'injection de dÃ©pendances
 *      â†’ C'est juste un identifiant interne pour charger la classe
 * 
 *   ðŸ”” NOM DE TYPE (dans la mÃ©thode get_type() de la classe) :
 *      'notification.type.reaction'
 *      â†’ UtilisÃ© par phpBB pour identifier le type de notification en base de donnÃ©es
 *      â†’ C'est ce qui est stockÃ© dans phpbb_notification_types
 *      â†’ C'est ce qu'il faut utiliser avec enable_notifications()
 * 
 * âš ï¸  ERREUR COMMUNE : Utiliser le nom du service au lieu du nom du type
 *     âŒ $notification_manager->enable_notifications('bastien59960.reactions.notification');
 *     âœ… $notification_manager->enable_notifications('notification.type.reaction');
 *
 * Ce fichier est le point d'entrÃ©e de l'extension pour phpBB et doit Ãªtre prÃ©sent 
 * pour que l'extension soit reconnue et gÃ©rÃ©e correctement.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions;

/**
 * Classe principale de l'extension
 * 
 * GÃ¨re l'activation, dÃ©sactivation et la configuration des notifications
 * de l'extension Reactions.
 */
class ext extends \phpbb\extension\base
{
	/**
	 * VÃ©rifie si l'extension peut Ãªtre activÃ©e
	 * 
	 * Cette mÃ©thode est appelÃ©e par phpBB AVANT d'activer l'extension.
	 * Elle permet de vÃ©rifier que l'environnement est compatible.
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
	 * Cette version DOIT correspondre aux migrations prÃ©sentes dans le dossier migrations/
	 * Si la version change, phpBB exÃ©cutera les nouvelles migrations automatiquement.
	 * 
	 * @return string Version de l'extension (doit correspondre aux migrations)
	 */
	public function get_version()
	{
		return '1.0.1';
	}

	/**
	 * Ã‰tape d'activation de l'extension
	 * 
	 * Cette mÃ©thode est appelÃ©e par phpBB lors de l'activation de l'extension.
	 * Elle enregistre les types de notifications auprÃ¨s du systÃ¨me de notifications phpBB.
	 * 
	 * CORRECTION CRITIQUE :
	 * On doit utiliser les NOMS DE TYPES (get_type()) et NON les noms de services.
	 * 
	 * L'extension Reactions possÃ¨de DEUX types de notifications :
	 * 
	 * 1ï¸âƒ£ notification.type.reaction (notification cloche instantanÃ©e)
	 *    - DÃ©fini dans : notification/type/reaction.php
	 *    - MÃ©thode get_type() retourne : 'notification.type.reaction'
	 *    - UtilisÃ© pour : Notifier immÃ©diatement l'auteur d'un post qu'on a rÃ©agi
	 * 
	 * 2ï¸âƒ£ notification.type.reaction_email_digest (notification email groupÃ©e)
	 *    - DÃ©fini dans : notification/type/reaction_email_digest.php
	 *    - MÃ©thode get_type() retourne : 'notification.type.reaction_email_digest'
	 *    - UtilisÃ© pour : Envoyer un rÃ©sumÃ© pÃ©riodique par email (cron)
	 * 
	 * Ces noms DOIVENT correspondre EXACTEMENT Ã  ce qui est :
	 * - RetournÃ© par la mÃ©thode get_type() de chaque classe
	 * - StockÃ© dans phpbb_notification_types (colonne notification_type_name)
	 * - CrÃ©Ã© par la migration (migrations/release_1_0_0.php)
	 * 
	 * @param mixed $old_state Ã‰tat prÃ©cÃ©dent de l'extension (false = premiÃ¨re activation)
	 * @return string|mixed 'notification' si premiÃ¨re activation, sinon rÃ©sultat parent
	 */
public function enable_step($old_state)
{
    if ($old_state === false)
    {
        // RÃ©cupÃ©rer le gestionnaire de notifications phpBB
        $notification_manager = $this->container->get('notification_manager');

        // âœ… Utiliser uniquement les NOMS DE TYPES (get_type())
        // Activation du type "cloche" (instantanÃ©)
        try {
            $notification_manager->enable_notifications('notification.type.reaction');
        } catch (\phpbb\notification\exception $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] enable_notifications(reaction) failed: ' . $e->getMessage(), E_USER_NOTICE);
            }
        }

        // Activation du type "email digest"
        try {
            $notification_manager->enable_notifications('notification.type.reaction_email_digest');
        } catch (\phpbb\notification\exception $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] enable_notifications(reaction_email_digest) failed: ' . $e->getMessage(), E_USER_NOTICE);
            }
        }

        return 'notification';
    }

    return parent::enable_step($old_state);
}


	/**
	 * Ã‰tape de dÃ©sactivation de l'extension
	 * 
	 * Cette mÃ©thode est appelÃ©e par phpBB lors de la dÃ©sactivation de l'extension.
	 * Elle dÃ©sactive les types de notifications (mais ne les supprime PAS de la base).
	 * 
	 * Les notifications existantes restent en base mais ne sont plus actives.
	 * L'utilisateur peut rÃ©activer l'extension sans perdre les notifications passÃ©es.
	 * 
	 * @param mixed $old_state Ã‰tat prÃ©cÃ©dent de l'extension (false = premiÃ¨re dÃ©sactivation)
	 * @return string|mixed 'notification' si premiÃ¨re dÃ©sactivation, sinon rÃ©sultat parent
	 */
	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			// RÃ©cupÃ©rer le gestionnaire de notifications phpBB
			$notification_manager = $this->container->get('notification_manager');
			
			// âœ… CORRECTION : Utiliser les NOMS DE TYPES (get_type())
			
			// DÃ©sactiver la notification cloche
			$notification_manager->disable_notifications('notification.type.reaction');
			
			// DÃ©sactiver la notification email groupÃ©e
			$notification_manager->disable_notifications('notification.type.reaction_email_digest');
			
			return 'notification';
		}
		
		return parent::disable_step($old_state);
	}

	/**
	 * Ã‰tape de purge de l'extension
	 * 
	 * Cette mÃ©thode est appelÃ©e par phpBB lors de la SUPPRESSION DÃ‰FINITIVE de l'extension.
	 * Elle supprime TOUTES les notifications de l'extension de la base de donnÃ©es.
	 * 
	 * âš ï¸  ATTENTION : Cette action est IRRÃ‰VERSIBLE
	 * Toutes les notifications existantes seront dÃ©finitivement supprimÃ©es.
	 * 
	 * La purge est diffÃ©rente de la dÃ©sactivation :
	 * - DÃ©sactivation : Les donnÃ©es restent, mais l'extension est inactive
	 * - Purge : Les donnÃ©es sont supprimÃ©es dÃ©finitivement
	 * 
	 * Lors de la purge, phpBB va Ã©galement :
	 * 1. ExÃ©cuter les mÃ©thodes revert_data() et revert_schema() des migrations
	 * 2. Supprimer les tables crÃ©Ã©es par l'extension
	 * 3. Supprimer les colonnes ajoutÃ©es par l'extension
	 * 4. Supprimer les configurations de l'extension
	 * 
	 * @param mixed $old_state Ã‰tat prÃ©cÃ©dent de l'extension (false = premiÃ¨re purge)
	 * @return string|mixed 'notification' si premiÃ¨re purge, sinon rÃ©sultat parent
	 */
public function purge_step($old_state)
{
    if ($old_state === false)
    {
        $notification_manager = $this->container->get('notification_manager');

        try {
            $notification_manager->purge_notifications('notification.type.reaction');
        } catch (\phpbb\notification\exception $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] purge_notifications(reaction) failed: ' . $e->getMessage(), E_USER_NOTICE);
            }
        }

        try {
            $notification_manager->purge_notifications('notification.type.reaction_email_digest');
        } catch (\phpbb\notification\exception $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] purge_notifications(reaction_email_digest) failed: ' . $e->getMessage(), E_USER_NOTICE);
            }
        }

        return 'notification';
    }

    return parent::purge_step($old_state);
}

}
