-- ===================================================================
CREATE TABLE IF NOT EXISTS `phpbb_post_reactions` (
  `reaction_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `topic_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `reaction_emoji` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reaction_time` int(11) unsigned NOT NULL,
  `reaction_notified` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`reaction_id`),
  UNIQUE KEY `post_user_emoji` (`post_id`,`user_id`,`reaction_emoji`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `reaction_notified` (`reaction_notified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE TABLE `phpbb_post_reactions`;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS PopulateReactionsGroup1()
BEGIN
    DECLARE post_counter INT DEFAULT 1;
    DECLARE reaction_counter INT;
    DECLARE current_topic_id INT;
    DECLARE current_user_id INT;
    DECLARE current_emoji VARCHAR(10);
    
    SET @users = '2,58,59,60';
    SET @emojis = '🫠,🫨,👩‍👩‍👧‍👦,👨‍💻,🏳️‍🌈,🏴‍☠️,🧜‍♀️,🧗‍♂️,🎉,👍,❤️,😂,😮,😢,😡,🔥,👌,🥳,🤔,✅';

    WHILE post_counter <= 9 DO
        SELECT topic_id INTO current_topic_id FROM phpbb_posts WHERE post_id = post_counter;

        SET reaction_counter = 1;
        WHILE reaction_counter <= 10 DO
            SET current_user_id = SUBSTRING_INDEX(SUBSTRING_INDEX(@users, ',', (post_counter + reaction_counter) % 4 + 1), ',', -1);
            SET current_emoji = SUBSTRING_INDEX(SUBSTRING_INDEX(@emojis, ',', (post_counter + reaction_counter) % 20 + 1), ',', -1);

            INSERT IGNORE INTO `phpbb_post_reactions` (`post_id`, `topic_id`, `user_id`, `reaction_emoji`, `reaction_time`, `reaction_notified`)
            VALUES (
                post_counter, 
                current_topic_id, 
                current_user_id, 
                current_emoji, 
                UNIX_TIMESTAMP() - (post_counter * 10000) - (reaction_counter * 100), 
                0
            );

            SET reaction_counter = reaction_counter + 1;
        END WHILE;
        SET post_counter = post_counter + 1;
    END WHILE;
END$$
DELIMITER ;

CALL PopulateReactionsGroup1();
DROP PROCEDURE IF EXISTS PopulateReactionsGroup1;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS PopulateReactionsGroup2()
BEGIN
    DECLARE post_counter INT DEFAULT 10;
    DECLARE user_counter INT;
    DECLARE current_topic_id INT;

    WHILE post_counter <= 18 DO
        SELECT topic_id INTO current_topic_id FROM phpbb_posts WHERE post_id = post_counter;

        SET user_counter = 1;
        WHILE user_counter <= 60 DO
            INSERT IGNORE INTO `phpbb_post_reactions` (`post_id`, `topic_id`, `user_id`, `reaction_emoji`, `reaction_time`, `reaction_notified`)
            VALUES (post_counter, current_topic_id, user_counter, '👍', UNIX_TIMESTAMP() - (post_counter * 1000) - user_counter, 0);

            SET user_counter = user_counter + 1;
        END WHILE;
        SET post_counter = post_counter + 1;
    END WHILE;
END$$
DELIMITER ;

CALL PopulateReactionsGroup2();
DROP PROCEDURE IF EXISTS PopulateReactionsGroup2;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS PopulateReactionsGroup3()
BEGIN
    INSERT IGNORE INTO `phpbb_post_reactions` (`post_id`, `topic_id`, `user_id`, `reaction_emoji`, `reaction_time`, `reaction_notified`) VALUES
    (19, 2, 58, '🤔', UNIX_TIMESTAMP() - 100000, 0),
    (20, 2, 2, '👍', UNIX_TIMESTAMP() - 99000, 0),
    (20, 2, 58, '👍', UNIX_TIMESTAMP() - 98000, 0),
    (20, 2, 59, '👎', UNIX_TIMESTAMP() - 97000, 0),
    (20, 2, 60, '👎', UNIX_TIMESTAMP() - 96000, 0),
    (20, 2, 58, '🤔', UNIX_TIMESTAMP() - 95000, 0),
    (21, 1, 2, '❤️', UNIX_TIMESTAMP() - 94000, 0),
    (21, 1, 58, '❤️', UNIX_TIMESTAMP() - 93000, 0),
    (21, 1, 59, '❤️', UNIX_TIMESTAMP() - 92000, 0),
    (21, 1, 60, '❤️', UNIX_TIMESTAMP() - 91000, 0),
    (21, 1, 2, '🔥', UNIX_TIMESTAMP() - 90000, 0),
    (21, 1, 58, '🔥', UNIX_TIMESTAMP() - 89000, 0),
    (22, 2, 59, '😂', UNIX_TIMESTAMP() - 88000, 0),
    (22, 2, 60, '😂', UNIX_TIMESTAMP() - 87000, 0),
    (22, 2, 2, '😂', UNIX_TIMESTAMP() - 86000, 0),
    (22, 2, 58, '😂', UNIX_TIMESTAMP() - 85000, 0),
    (22, 2, 59, '🤣', UNIX_TIMESTAMP() - 84000, 0),
    (23, 2, 2, '✅', UNIX_TIMESTAMP() - 83000, 0),
    (23, 2, 58, '👍', UNIX_TIMESTAMP() - 82000, 0),
    (25, 2, 60, '⏰', UNIX_TIMESTAMP() - 86400 * 5, 0),
    (25, 2, 59, '⏰', UNIX_TIMESTAMP() - 86400 * 3, 0),
    (26, 2, 60, '👍', UNIX_TIMESTAMP() - 81000, 0),
    (26, 2, 60, '❤️', UNIX_TIMESTAMP() - 80000, 0),
    (26, 2, 60, '🥳', UNIX_TIMESTAMP() - 79000, 0);
END$$
DELIMITER ;

CALL PopulateReactionsGroup3();
DROP PROCEDURE IF EXISTS PopulateReactionsGroup3;