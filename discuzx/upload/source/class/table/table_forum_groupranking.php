<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_groupranking.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_groupranking extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_groupranking';
		$this->_pk    = 'fid';

		parent::__construct();
	}
	public function fetch_all_today_ranking($num = 10) {
		return DB::fetch_all("SELECT * FROM ".DB::table('forum_groupranking')." ORDER BY today LIMIT 0, $num");
	}
}

?>