<?php
/**
 * Fichier : acp/main_module.php — bastien59960/reactions/acp/main_module.php
 * Fichier : main_module.php
 * Chemin : bastien59960/reactions/acp/main_module.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/acp/main_module.php
 *
 * Module principal ACP pour l'extension Reactions.
 * Rôle :
 * Ce fichier définit le module du Panneau d'Administration (ACP) pour l'extension.
 * Il gère l'affichage et le traitement du formulaire de configuration, permettant
 * aux administrateurs de régler les paramètres globaux de l'extension (limites,
 * délai anti-spam, etc.) et de lancer l'import depuis une ancienne version.
 *
 * Ce fichier gère la logique métier et l'affichage des pages de configuration de l'extension dans le panneau d'administration (ACP) de phpBB.
 * Informations reçues :
 * - Via le formulaire POST : les nouvelles valeurs pour les paramètres de configuration.
 *
 * Points clés :
 *   - Lecture et sauvegarde des paramètres de l'extension
 *   - Intégration avec le template ACP
 *   - Sécurité et validation des entrées administrateur
 *
 * Ce module permet à l'administrateur de configurer tous les aspects de l'extension Reactions via l'interface ACP.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// Déclaration du namespace (chemin virtuel du fichier dans phpBB)
// Format : vendeur\nom_extension\acp
namespace bastien59960\reactions\acp;

/**
 * Classe principale du module ACP
 * 
 * phpBB cherche automatiquement cette classe quand un admin accède
 * à la page de configuration de l'extension.
 */
class main_module
{
    /**
     * URL d'action du formulaire
     * 
     * phpBB remplit automatiquement cette variable avec l'URL de la page
     * actuelle. Elle est utilisée dans le template pour savoir où envoyer
     * le formulaire quand on clique sur "Envoyer".
     * 
     * @var string
     */
    public $u_action;

    /**
     * Titre de la page ACP
     * 
     * Ce titre apparaît dans l'onglet du navigateur et en haut de la page.
     * Sera rempli avec la traduction de 'ACP_REACTIONS_SETTINGS'.
     * 
     * @var string
     */
    public $page_title;

    /**
     * Nom du fichier template à utiliser
     * 
     * phpBB cherchera le fichier : adm/style/acp_reactions_settings.html
     * 
     * @var string
     */
    public $tpl_name;

    /**
     * ========================================================================
     * MÉTHODE PRINCIPALE - POINT D'ENTRÉE DU MODULE
     * ========================================================================
     * 
     * Cette méthode est appelée automatiquement par phpBB quand un admin
     * accède à la page de configuration. Elle fait deux choses :
     * 1. Afficher le formulaire de configuration
     * 2. Traiter les données soumises par le formulaire
     * 
     * @param int    $id   ID du module (fourni par phpBB, rarement utilisé)
     * @param string $mode Mode du module (ex: 'settings')
     */
    public function main($id, $mode)
    {
        // ====================================================================
        // ÉTAPE 1 : ACCÉDER AUX SERVICES GLOBAUX DE PHPBB
        // ====================================================================
        
        // Ces variables "global" permettent d'accéder aux objets phpBB
        // disponibles partout dans le code
        global $config,          // Configuration du forum (stockée en DB)
               $request,         // Gestion des données POST/GET
               $template,        // Moteur de templates (affichage HTML)
               $user,            // Informations sur l'utilisateur actuel
               $phpbb_container; // Container de services (injection de dépendances)

        // ====================================================================
        // ÉTAPE 2 : CHARGER LES TRADUCTIONS
        // ====================================================================
        
        // Récupérer le service de gestion des langues depuis le container
        // Le container est comme un "magasin" où phpBB stocke tous ses services
        $language = $phpbb_container->get('language');
        
        // Charger le fichier de langue ACP de notre extension
        // Chemin : language/[langue_actuelle]/acp/common.php
        // Cela permet d'utiliser $user->lang('ACP_REACTIONS_TITLE') etc.
        $language->add_lang('acp/common', 'bastien59960/reactions');
        
        // Charger aussi les traductions générales
        $language->add_lang('common', 'bastien59960/reactions');

        // ====================================================================
        // ÉTAPE 3 : DÉFINIR LES PROPRIÉTÉS DE LA PAGE
        // ====================================================================
        
        // Nom du template à charger (sans l'extension .html)
        $this->tpl_name = 'acp_reactions_settings';
        
        // Titre de la page (sera traduit automatiquement)
        // Cherche la clé 'ACP_REACTIONS_SETTINGS' dans language/*/acp/common.php
        $this->page_title = $user->lang('ACP_REACTIONS_SETTINGS');
        
        // ====================================================================
        // ÉTAPE 4 : SÉCURITÉ - GÉNÉRER UN TOKEN CSRF
        // ====================================================================
        
        // Un token CSRF protège contre les attaques "Cross-Site Request Forgery"
        // Il s'agit d'un code secret unique qui est vérifié lors de l'envoi du formulaire
        // pour s'assurer que la requête vient bien de notre site et non d'un site malveillant
        add_form_key('bastien59960_reactions');

        // ====================================================================
        // ÉTAPE 5 : TRAITER L'ENVOI DU FORMULAIRE (SI SOUMIS)
        // ====================================================================
        
        // Vérifier si le formulaire a été soumis
        // is_set_post('submit') retourne true si le bouton "Envoyer" a été cliqué
        if ($request->is_set_post('submit'))
        {
            // ----------------------------------------------------------------
            // 5.1 : VÉRIFIER LE TOKEN CSRF
            // ----------------------------------------------------------------
            
            // check_form_key vérifie que le token CSRF est valide
            // Si invalide, c'est peut-être une attaque → on refuse
            if (!check_form_key('bastien59960_reactions'))
            {
                // Afficher une erreur et un lien retour
                // E_USER_WARNING = niveau d'erreur PHP (non fatal)
                trigger_error(
                    $user->lang('FORM_INVALID') . adm_back_link($this->u_action),
                    E_USER_WARNING
                );
            }

            // ----------------------------------------------------------------
            // 5.2 : RÉCUPÉRER LES VALEURS DU FORMULAIRE
            // ----------------------------------------------------------------
            
            // $request->variable() récupère une valeur POST de façon sécurisée
            // Syntaxe : variable('nom_du_champ', valeur_par_défaut)
            // phpBB nettoie automatiquement les données pour éviter les injections
            
            // Temps anti-spam (en minutes)
            // Si le champ est vide ou invalide, utilise 45 par défaut
            $spam_time = $request->variable('spam_time', 45);
            
            // Nombre max de types de réactions par post
            $max_per_post = $request->variable('max_per_post', 20);
            
            // Nombre max de réactions par utilisateur par post
            $max_per_user = $request->variable('max_per_user', 10);

            // ----------------------------------------------------------------
            // 5.3 : VALIDER LES VALEURS
            // ----------------------------------------------------------------
            
            // Vérifier que le temps anti-spam n'est pas négatif
            // (0 est accepté pour désactiver les notifications)
            if ($spam_time < 0)
            {
                trigger_error(
                    $user->lang('INVALID_VALUE') . adm_back_link($this->u_action),
                    E_USER_WARNING
                );
            }
            
            // Vérifier que le nombre de types de réactions est entre 1 et 100
            if ($max_per_post < 1 || $max_per_post > 100)
            {
                trigger_error(
                    $user->lang('INVALID_VALUE') . adm_back_link($this->u_action),
                    E_USER_WARNING
                );
            }
            
            // Vérifier que le nombre de réactions par user est entre 1 et 50
            if ($max_per_user < 1 || $max_per_user > 50)
            {
                trigger_error(
                    $user->lang('INVALID_VALUE') . adm_back_link($this->u_action),
                    E_USER_WARNING
                );
            }

            // ----------------------------------------------------------------
            // 5.4 : SAUVEGARDER EN BASE DE DONNÉES
            // ----------------------------------------------------------------
            
            // $config->set() enregistre une valeur dans la table phpbb_config
            // Ces valeurs sont ensuite accessibles partout via $config['nom_clé']
            
            $config->set('bastien59960_reactions_spam_time', $spam_time);
            $config->set('bastien59960_reactions_max_per_post', $max_per_post);
            $config->set('bastien59960_reactions_max_per_user', $max_per_user);

            // ----------------------------------------------------------------
            // 5.5 : AFFICHER UN MESSAGE DE SUCCÈS
            // ----------------------------------------------------------------
            
            // trigger_error() avec juste un message (pas de niveau d'erreur)
            // affiche un message de confirmation puis redirige
            // 'CONFIG_UPDATED' = "Configuration mise à jour avec succès"
            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        // ====================================================================
        // ÉTAPE 6 : PRÉPARER L'AFFICHAGE DU FORMULAIRE
        // ====================================================================
        
        // assign_vars() envoie des variables au template HTML
        // Dans le template, on pourra utiliser {U_ACTION}, {REACTIONS_SPAM_TIME}, etc.
        $template->assign_vars([
            // URL où le formulaire doit être soumis
            'U_ACTION' => $this->u_action,
            
            // Valeurs actuelles des paramètres (lues depuis la DB)
            // (int) force la conversion en nombre entier pour éviter les problèmes
            'REACTIONS_SPAM_TIME'    => (int) $config['bastien59960_reactions_spam_time'],
            'REACTIONS_MAX_PER_POST' => (int) $config['bastien59960_reactions_max_per_post'],
            'REACTIONS_MAX_PER_USER' => (int) $config['bastien59960_reactions_max_per_user'],
        ]);
        
        // ====================================================================
        // FIN - phpBB affiche automatiquement le template
        // ====================================================================
        
        // Pas besoin de "return" ou "echo" : phpBB utilise automatiquement
        // le template défini dans $this->tpl_name pour générer la page HTML
    }
    /**
 * Mode import depuis ancienne extension
 */
public function import($id, $mode)
{
    global $config, $request, $template, $user, $phpbb_container, $db;

    $language = $phpbb_container->get('language');
    $language->add_lang('acp/common', 'bastien59960/reactions');
    $language->add_lang('common', 'bastien59960/reactions');

    $this->tpl_name = 'acp_reactions_import';
    $this->page_title = 'Importer réactions anciennes';

    add_form_key('bastien59960_reactions_import');

    // Vérifier que les anciennes tables existent
    $old_reactions_table = $phpbb_container->getParameter('core.table_prefix') . 'reactions';
    $old_types_table = $phpbb_container->getParameter('core.table_prefix') . 'reaction_types';
    
    $tables_exist = true;
    try {
        $db->sql_query("SELECT 1 FROM $old_reactions_table LIMIT 1");
        $db->sql_query("SELECT 1 FROM $old_types_table LIMIT 1");
    } catch (\Exception $e) {
        $tables_exist = false;
    }

    if ($request->is_set_post('import')) {
        if (!check_form_key('bastien59960_reactions_import')) {
            trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
        }

        if (!$tables_exist) {
            trigger_error('Les anciennes tables de réactions sont introuvables.' . adm_back_link($this->u_action), E_USER_WARNING);
        }

        // Mapping emojis PNG vers Unicode
        $emoji_map = [
            '1f44d.png' => '👍',  // Like
            '1f44e.png' => '👎',  // Dislike
            '1f642.png' => '🙂',  // Happy
            '1f60d.png' => '😍',  // Love
            '1f602.png' => '😂',  // Funny
            '1f611.png' => '😑',  // Neutral
            '1f641.png' => '🙁',  // Unhappy
            '1f62f.png' => '😯',  // Surprised
            '1f62d.png' => '😭',  // Cry
            '1f621.png' => '😡',  // Mad
            'OMG.png'   => '😮',  // OMG
        ];

        $new_reactions_table = $phpbb_container->getParameter('core.table_prefix') . 'post_reactions';

        // Lire anciennes réactions
        $sql = "SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time 
                FROM $old_reactions_table 
                ORDER BY reaction_time ASC";
        $result = $db->sql_query($sql);

        $imported = 0;
        $skipped = 0;

        while ($row = $db->sql_fetchrow($result)) {
            $png_name = $row['reaction_file_name'];
            
            // Vérifier si on a un mapping
            if (!isset($emoji_map[$png_name])) {
                $skipped++;
                continue;
            }

            $emoji = $emoji_map[$png_name];

            // Vérifier doublon
            $check_sql = 'SELECT reaction_id FROM ' . $new_reactions_table . '
                         WHERE post_id = ' . (int) $row['post_id'] . '
                           AND user_id = ' . (int) $row['reaction_user_id'] . "
                           AND reaction_emoji = '" . $db->sql_escape($emoji) . "'";
            $check_result = $db->sql_query($check_sql);
            $exists = $db->sql_fetchrow($check_result);
            $db->sql_freeresult($check_result);

            if ($exists) {
                $skipped++;
                continue;
            }

            // Insérer dans nouvelle table
            $insert_data = [
                'post_id' => (int) $row['post_id'],
                'topic_id' => (int) $row['topic_id'],
                'user_id' => (int) $row['reaction_user_id'],
                'reaction_emoji' => $emoji,
                'reaction_time' => (int) $row['reaction_time'],
                'reaction_notified' => 1, // Déjà anciennes
            ];

            $insert_sql = 'INSERT INTO ' . $new_reactions_table . ' ' . $db->sql_build_array('INSERT', $insert_data);
            
            try {
                $db->sql_query($insert_sql);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }
        $db->sql_freeresult($result);

        trigger_error("Importation terminée : $imported réactions importées, $skipped ignorées." . adm_back_link($this->u_action));
    }

    $template->assign_vars([
        'U_ACTION' => $this->u_action,
        'TABLES_EXIST' => $tables_exist,
        'OLD_REACTIONS_TABLE' => $old_reactions_table,
        'OLD_TYPES_TABLE' => $old_types_table,
    ]);
}
}