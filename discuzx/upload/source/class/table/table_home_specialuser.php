<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_home_specialuser.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_home_specialuser extends discuz_table
{
	public function __construct() {

		$this->_table = 'home_specialuser';
		$this->_pk    = '';

		parent::__construct();
	}

	public function fetch_all_by_status($status, $start = 0, $limit = 0) {
		return DB::fetch_all('SELECT * FROM %t WHERE status=%d ORDER BY displayorder'.DB::limit($start, $limit), array($this->_table, $status), 'uid');
	}

	public function count_by_status($status, $username = '') {
		$addsql = $username ? " AND username='".addslashes($username)."' " : '';
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE status=%d'.$addsql, array($this->_table, $status));
	}

	public function update_by_uid_status($uid, $status, $data) {
		return DB::update($this->_table, $data, array('uid' => dintval($uid), 'status' => dintval($status)));
	}

	public function delete_by_uid_status($uid, $status) {
		return DB::delete($this->_table, DB::field('uid', $uid).' AND '.DB::field('status', $status));
	}

	public function fetch_by_uid_status($uid, $status) {
		return $uid ? DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND status=%d', array($this->_table, $uid, $status)) : array();
	}
}

?>