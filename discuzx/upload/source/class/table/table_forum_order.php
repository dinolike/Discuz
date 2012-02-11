<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_order.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_order extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_order';
		$this->_pk    = 'orderid';

		parent::__construct();
	}

	public function count_by_search($uid = null, $status = null, $orderid = null, $email = null, $username = null, $buyer = null, $admin = null, $submit_starttime = null, $submit_endtime = null, $confirm_starttime = null, $confirm_endtime = null) {
		$sql = '';
		$sql .= $uid !== null ? ' AND o.'.DB::field('uid', $uid) : '';
		$sql .= $status ? ' AND o.'.DB::field('status', $status) : '';
		$sql .= $orderid ? ' AND o.'.DB::field('orderid', $orderid) : '';
		$sql .= $email ? ' AND o.'.DB::field('email', $email) : '';
		$sql .= $username ? ' AND m.'.DB::field('username', $username) : '';
		$sql .= $buyer ? ' AND o.'.DB::field('buyer', $buyer) : '';
		$sql .= $admin ? ' AND o.'.DB::field('admin', $admin) : '';
		$sql .= $submit_starttime ? ' AND o.'.DB::field('submitdate', $submit_starttime, '>=') : '';
		$sql .= $submit_endtime ? ' AND o.'.DB::field('submitdate', $submit_endtime, '<') : '';
		$sql .= $confirm_starttime ? ' AND o.'.DB::field('confirmdate', $confirm_starttime, '>=') : '';
		$sql .= $confirm_endtime ? ' AND o.'.DB::field('confirmdate', $confirm_endtime, '<') : '';
		if($sql) {
			return DB::result_first('SELECT COUNT(*) FROM %t o '.($username ? DB::table('common_member').' m WHERE o.uid=m.uid' : 'WHERE 1').' %i', array($this->_table, $sql));
		} else {
			return 0;
		}
	}

	public function fetch_all_by_search($uid = null, $status = null, $orderid = null, $email = null, $username = null, $buyer = null, $admin = null, $submit_starttime = null, $submit_endtime = null, $confirm_starttime = null, $confirm_endtime = null, $start = null, $limit = null) {
		$sql = '';
		$sql .= $uid !== null ? ' AND o.'.DB::field('uid', $uid) : '';
		$sql .= $status ? ' AND o.'.DB::field('status', $status) : '';
		$sql .= $orderid ? ' AND o.'.DB::field('orderid', $orderid) : '';
		$sql .= $email ? ' AND o.'.DB::field('email', $email) : '';
		$sql .= $username ? ' AND m.'.DB::field('username', $username) : '';
		$sql .= $buyer ? ' AND o.'.DB::field('buyer', $buyer) : '';
		$sql .= $admin ? ' AND o.'.DB::field('admin', $admin) : '';
		$sql .= $submit_starttime ? ' AND o.'.DB::field('submitdate', $submit_starttime, '>=') : '';
		$sql .= $submit_endtime ? ' AND o.'.DB::field('submitdate', $submit_endtime, '<') : '';
		$sql .= $confirm_starttime ? ' AND o.'.DB::field('confirmdate', $confirm_starttime, '>=') : '';
		$sql .= $confirm_endtime ? ' AND o.'.DB::field('confirmdate', $confirm_endtime, '<') : '';
		if($sql) {
			return DB::fetch_all('SELECT * FROM %t o '.($username ? DB::table('common_member').' m WHERE o.uid=m.uid' : 'WHERE 1').' %i ORDER BY o.submitdate DESC '.DB::limit($start, $limit), array($this->_table, $sql));
		} else {
			return array();
		}
	}

	public function fetch_all($orderid, $status = '') {
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('orderid', $orderid).' %i', array($this->_table, ($status ? ' AND '.DB::field('status', $status) : '')));
	}

	public function delete_by_submitdate($submitdate) {
		return DB::query('DELETE FROM %t WHERE submitdate<%d', array($this->_table, $submitdate));
	}

	public function sum_amount_by_uid_submitdate_status($uid, $submitdate, $status) {
		return DB::result_first('SELECT SUM(amount) FROM %t WHERE uid=%d AND submitdate>=%d AND '.DB::field('status', $status), array($this->_table, $uid, $submitdate));
	}

}

?>