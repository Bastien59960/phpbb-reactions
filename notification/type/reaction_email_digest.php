<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions
 *
 * Définit le type de notification pour l'envoi groupé d'e-mails (digest).
 * Cette classe est essentielle pour que le système de messagerie de phpBB
 * puisse trouver et utiliser les templates d'e-mail de l'extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB'))
{
    exit;
}

class reaction_email_digest extends \phpbb\notification\type\base
{
    /**
     * Retourne le nom unique du type de notification.
     * Ce nom est utilisé pour l'identifier dans la base de données et le système.
     *
     * @return string
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Indique si ce type de notification est disponible.
     *
     * @return bool
     */
    public function is_available()
    {
        return true;
    }

    /**
     * Spécifie le nom du template d'e-mail à utiliser.
     * phpBB cherchera 'reaction_digest.html' et 'reaction_digest.txt'.
     *
     * @return string|bool Le nom du template ou false si aucun e-mail n'est envoyé.
     */
    public function get_email_template()
    {
        // Le préfixe de l'extension est géré par le messenger,
        // il suffit de donner le nom du fichier de base.
        return 'reaction_digest';
    }

    /**
     * Cette notification est gérée par une tâche cron et n'est pas créée directement.
     * Ces méthodes ne sont donc pas utilisées mais doivent exister.
     */
    public static function get_item_id($data) { return 0; }
    public function find_users_for_notification($data, $options = []) { return []; }
}