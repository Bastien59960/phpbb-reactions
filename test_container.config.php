<?php
/**
 * Fichier : test_container.config.php
 * Chemin : bastien59960/reactions/test_container.config.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Fichier de configuration optionnel pour le script de diagnostic `test_container.php`.
 * Il permet de surcharger certains paramètres pour le débogage, notamment pour
 * simuler une date et une heure spécifiques afin de tester le comportement
 * temporel des tâches CRON.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

return [
    // Décommentez et modifiez cette ligne pour forcer une date.
    // Format : 'YYYY-MM-DD HH:MM:SS'
    'MOCK_TIME' => '2025-11-13 12:00:00',
];
