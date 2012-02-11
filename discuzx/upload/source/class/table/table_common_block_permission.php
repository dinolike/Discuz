<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_block_permission.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_block_permission extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_block_permission';
		$this->_pk    = '';

		parent::__construct();
	}

	public function fetch($bid, $uid){
		return DB::fetch_first('SELECT * FROM %t WHERE bid=%d AND uid=%d', array($this->_table, $bid, $uid));
	}

	public function fetch_all_by_bid($bid, $uid = 0) {
		return DB::fetch_all('SELECT * FROM %t WHERE bid=%d'.($uid ? ' AND '.DB::field('uid', $uid) : '').' ORDER BY inheritedtplname', array($this->_table, $bid), 'uid');
	}


	public function fetch_all_by_uid($uids, $flag = true, $sort = 'ASC', $start = 0, $limit = 0) {
		$wherearr = array();
		$sort = $sort === 'ASC' ? 'ASC' : 'DESC';
		if($uids) {
			$wherearr[] = DB::field('uid', $uids);
		}
		if(!$flag) {
			$wherearr[] = 'inheritedtplname = \'\'';
		}
		$where = $wherearr ? ' WHERE '.implode(' AND ', $wherearr) : '';
		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).$where.' ORDER BY uid '.$sort.', inheritedtplname'.DB::limit($start, $limit), NULL, ($uids && !is_array($uids)) ? 'bid' : '');
	}

	public function count_by_uids($uids, $flag) {
		$wherearr = array();
		if($uids) {
			$wherearr[] = DB::field('uid', $uids);
		}
		if(!$flag) {
			$wherearr[] = 'inheritedtplname = \'\'';
		}
		$where = $wherearr ? ' WHERE '.implode(' AND ', $wherearr) : '';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table).$where);
	}

	public function fetch_permission_by_uid($uids) {
		return DB::fetch_all('SELECT uid, sum(allowmanage) as allowmanage, sum(allowrecommend) as allowrecommend, sum(needverify) as needverify FROM '.DB::table($this->_table)." WHERE uid IN (".dimplode($uids).") GROUP BY uid", null, 'uid');
	}

	public function delete_by_bid_uid_inheritedtplname($bid = false, $uids = false, $inheritedtplname = false) {
		$wherearr = array();
		if($bid) {
			$wherearr[] = DB::field('bid', $bid);
		}
		if($uids) {
			$wherearr[] = DB::field('uid', $uids);
		}
		if($inheritedtplname === true) {
			$wherearr[] = "inheritedtplname!=''";
		} elseif($inheritedtplname !== false) {
			$wherearr[] = DB::field('inheritedtplname', $inheritedtplname);
		}
		return $wherearr ? DB::delete($this->_table, implode(' AND ', $wherearr)) : false;
	}


	public function insert_batch($users, $bids, $tplname = '') {
		$blockperms = array();
		if(!empty($users) && !empty($bids)){
			if(!is_array($bids)) {
				$bids = array($bids);
			}

			$uids = $notinherit = array();
			foreach($users as $user) {
				$uids[] = $user['uid'];
			}
			if(!empty($uids)) {
				foreach($this->fetch_all_by_uid($uids, false) as $value) {
					if(in_array($value['bid'], $bids)) {
						$notinherit[$value['bid']][$value['uid']] = true;
					}
				}
			}
			foreach($users as $user) {
				$tplname = !empty($user['inheritedtplname']) ? $user['inheritedtplname'] : $tplname;
				foreach ($bids as $bid) {
					if(empty($notinherit[$bid][$user['uid']])) {
						$blockperms[] = "('$bid','$user[uid]','$user[allowmanage]','$user[allowrecommend]','$user[needverify]','$tplname')";
					}
				}
			}
			if($blockperms) {
				DB::query('REPLACE INTO '.DB::table($this->_table).' (bid,uid,allowmanage,allowrecommend,needverify,inheritedtplname) VALUES '.implode(',', $blockperms));
				return $uids;
			} else {
				return FALSE;
			}
		}
		return false;
	}

	public function insert_by_bid($bid, $users) {
		$sqlarr = $uids = array();
		$bid = intval($bid);
		if(!empty($bid) && !empty($users)) {
			foreach ($users as $v) {
				$sqlarr[] = "('$bid','$v[uid]','$v[allowmanage]','$v[allowrecommend]','$v[needverify]','')";
				$uids[] = $v['uid'];
			}
			if(!empty($sqlarr)) {
				DB::query('REPLACE INTO '.DB::table($this->_table).' (bid,uid,allowmanage,allowrecommend,needverify,inheritedtplname) VALUES '.implode(',', $sqlarr));
			}
		}
		return $uids;
	}
}

?>