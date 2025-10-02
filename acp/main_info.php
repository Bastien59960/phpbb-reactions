<?php
/**
 * Reactions Extension for phpBB 3.3
 * ACP Module Information - Définit comment le module apparaît dans le menu admin
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\acp;

/**
 * Classe d'information du module ACP
 * 
 * Ce fichier décrit UNIQUEMENT la structure du module (menu, permissions).
 * Le vrai travail (affichage, traitement) est fait par main_module.php.
 */
class main_info
{
    /**
     * Retourne la configuration du module
     * 
     * phpBB lit ce tableau pour :
     * - Ajouter l'entrée dans le menu ACP
     * - Savoir quel fichier exécuter lors d'un clic
     * - Vérifier les permissions d'accès
     * 
     * @return array Configuration du module
     */
    public function module()
    {
        return [
            // Fichier contenant la classe main_module qui fait le vrai travail
            'filename'  => '\bastien59960\reactions\acp\main_module',
            
            // Titre du module dans le menu (clé de traduction)
            'title'     => 'ACP_REACTIONS_TITLE',
            
            // Liste des sous-pages (modes) disponibles
            'modes'     => [
                // Mode "settings" - Page de configuration
                'settings' => [
                    // Titre de la sous-page
                    'title' => 'ACP_REACTIONS_SETTINGS',
                    
                    // Permissions requises pour accéder à cette page
                    // ext_bastien59960/reactions : extension activée
                    // acl_a_board : droits d'administration du forum
                    'auth'  => 'ext_bastien59960/reactions && acl_a_board',
                    
                    // Catégorie parente dans le menu
                    'cat'   => ['ACP_REACTIONS_TITLE']
                ],
                
                // Vous pouvez ajouter d'autres modes ici :
                // 'statistics' => [...],
                // 'import' => [...],
            ],
        ];
    }
}

/**
 * RÉSUMÉ DU FONCTIONNEMENT
 * 
 * 1. phpBB lit ce fichier au chargement de l'ACP
 * 2. Ajoute "Post Reactions" dans le menu Extensions
 * 3. Quand l'admin clique dessus, phpBB charge main_module.php
 * 4. main_module.php affiche et traite le formulaire de configuration
 */
