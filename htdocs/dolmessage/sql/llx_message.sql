
CREATE TABLE IF NOT EXISTS `llx_message` (
`row_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `usergroup_id` int(11) DEFAULT NULL,
  `number` int(11) NOT NULL DEFAULT '1',
  `entity` int(11) NOT NULL,
  `message_id` varchar(264) NOT NULL,
  `message_uid` int(11) NOT NULL,
  `datec` datetime NOT NULL,
  `recent` int(11) NOT NULL,
  `unseen` int(11) NOT NULL,
  `flagged` int(11) NOT NULL,
  `answered` int(11) NOT NULL,
  `joint` int(11) NOT NULL
  UNIQUE KEY `message_id` (`message_id`)
) ;