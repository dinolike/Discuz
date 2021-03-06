DROP TABLE IF EXISTS `pre_common_tag`;
CREATE TABLE `pre_common_tag` (
  `tagid` int(10) unsigned NOT NULL AUTO_INCREMENT,
#  `tagname` char(30) NOT NULL,
  `tagname` varchar(30) NOT NULL,

  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'DX2 新增',

  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `total` mediumint(8) unsigned NOT NULL,

  `fupid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上層tagid',

  `same_tagname` text NOT NULL COMMENT '同義的tag',
  `same_tagid` tinytext NOT NULL,

  `link_tagname` text NOT NULL COMMENT '關聯的tag',
  `link_tagid` tinytext NOT NULL,

  `tag_author` varchar(15) NOT NULL DEFAULT '' COMMENT '建立者',
  `tag_authorid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `tag_dateline` int(10) unsigned NOT NULL DEFAULT '0',

  `last_author` varchar(15) NOT NULL DEFAULT '' COMMENT '最後使用者',
  `last_authorid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `last_dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tagid`),
  UNIQUE KEY `tagname` (`tagname`),

  KEY `total` (`total`),
  KEY `closed` (`closed`),

  KEY `status` (`status`,tagid)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `pre_common_tag_data`;
CREATE TABLE `pre_common_tag_data` (
  `itemid` int(10) unsigned NOT NULL,
  `tagid` int(10) unsigned NOT NULL,
  `tagname` varchar(30) NOT NULL,

  `idtype` varchar(20) NOT NULL DEFAULT '',

  KEY tagid (tagid,idtype),
  KEY idtype (idtype,itemid)
) ENGINE=MyISAM;