-- ===================================================================
-- Fichier : setup-test-data.sql
-- Rôle : Insère un jeu de données de test cohérent pour les réactions.
--        Les timestamps sont générés dans le passé pour permettre
--        au cron de les traiter.
-- ===================================================================

-- Vider la table pour garantir un état propre
TRUNCATE TABLE `phpbb_post_reactions`;

-- Insertion de données de test avec des timestamps dans le passé
INSERT INTO `phpbb_post_reactions` (`reaction_id`, `post_id`, `topic_id`, `user_id`, `reaction_emoji`, `reaction_time`, `reaction_notified`) VALUES
-- Réactions très anciennes (devraient être traitées)
(1, 1, 1, 2, '👍', UNIX_TIMESTAMP() - 86400 * 2, 0),   -- Il y a 2 jours
(2, 2, 1, 2, '😃', UNIX_TIMESTAMP() - 3600 * 5, 0),    -- Il y a 5 heures

-- Réactions assez anciennes (devraient être traitées si le délai est < 1h)
(3, 3, 1, 2, '😡', UNIX_TIMESTAMP() - 3600, 0),       -- Il y a 1 heure
(4, 4, 1, 2, '🙂', UNIX_TIMESTAMP() - 1800, 0),       -- Il y a 30 minutes

-- Réactions récentes (ne devraient PAS être traitées si le délai est > 5min)
(5, 4, 1, 2, '🤩', UNIX_TIMESTAMP() - 300, 0),        -- Il y a 5 minutes
(6, 5, 1, 2, '🙃', UNIX_TIMESTAMP() - 120, 0),        -- Il y a 2 minutes

-- Réactions d'autres utilisateurs
(7, 5, 1, 59, '😝', UNIX_TIMESTAMP() - 86400, 0),     -- Il y a 1 jour
(8, 4, 1, 59, '🤩', UNIX_TIMESTAMP() - 600, 0),       -- Il y a 10 minutes

-- Réactions qui étaient à 1 (pour vérifier qu'elles sont bien remises à 0)
(9, 2, 1, 60, '🐵', UNIX_TIMESTAMP() - 3600 * 3, 0),    -- Il y a 3 heures
(10, 3, 1, 60, '🤪', UNIX_TIMESTAMP() - 3600 * 2, 0),   -- Il y a 2 heures
(11, 5, 1, 60, '😂', UNIX_TIMESTAMP() - 60, 0);        -- Il y a 1 minute

-- Message de confirmation
SELECT '✅ Données de test pour les réactions insérées avec succès.' as status;
