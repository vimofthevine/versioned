CREATE TABLE IF NOT EXISTS `entry_revisions` (
    `id` int(11) NOT NULL auto_increment,
    `entry_id` int(11) NOT NULL,
    `version` int(4) NOT NULL,
    `date` int(10) NOT NULL,
    `editor_id` int(11) NOT NULL,
    `diff` text NOT NULL,
    `comment` text NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

