<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_home_album.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_home_album extends discuz_table
{
	public function __construct() {

		$this->_table = 'home_album';
		$this->_pk    = 'albumid';

		parent::__construct();
	}

	public function count_by_catid($catid) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE catid = %d', array($this->_table, $catid));
	}

	public function count_by_uid($uid) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE uid = %d', array($this->_table, $uid));
	}

	public function update_num_by_albumid($albumid, $inc, $field = 'picnum', $uid = '') {
		$parameter = array($this->_table, $inc, $albumid);
		if($uid) {
			$parameter[] = $uid;
			$uidsql = ' AND uid = %d';
		}
		$field = daddslashes($field);
		return DB::query('UPDATE %t SET '.$field.'='.$field.'+\'%d\' WHERE albumid=%d '.$uidsql, $parameter);
	}

	public function delete_by_uid($uid) {
		return DB::delete($this->_table, DB::field('uid', $uid));
	}

	public function update_by_catid($catid, $data) {
		return DB::update($this->_table, $data, DB::field('catid', $catid));
	}

	public function fetch_uid_by_username($users) {
		if(!$users) {
			return null;
		}
		return DB::fetch_all('SELECT uid FROM %t WHERE username IN (%n)', array($this->_table, $users), 'uid');
	}

	public function fetch_albumid_by_albumname_uid($albumname, $uid) {
		return DB::result_first('SELECT albumid FROM %t WHERE albumname=%s AND uid=%d', array($this->_table, $albumname, $uid));
	}

	public function fetch_albumid_by_searchkey($searchkey, $limit) {
		return DB::fetch_all('SELECT albumid FROM %t WHERE 1 %i ORDER BY albumid DESC %i', array($this->_table, $searchkey, DB::limit(0, $limit)));
	}

	public function fetch_uid_by_uid($uid) {
		if(!is_array($uid)) {
			$uid = explode(',', $uid);
		}
		if(!$uid) {
			return null;
		}
		return DB::fetch_all('SELECT uid FROM %t WHERE uid IN (%n)', array($this->_table, $uid), 'uid');
	}

	public function fetch($albumid, $uid = '') {
		$data = $this->fetch_all_by_uid($uid, false, 0, 0, $albumid);
		return $data[0];
	}

	public function fetch_all($albumids, $order = false, $start = 0, $limit = 0) {
		return $this->fetch_all_by_uid('', $order, $start, $limit, $albumids);
	}

	public function fetch_all_by_uid($uid, $order = false, $start = 0, $limit = 0, $albumid = '') {
		$parameter = array($this->_table);
		$wherearr = array();
		if($albumid != '') {
			$wherearr[] = DB::field('albumid', $albumid);
		}
		if($uid) {
			$wherearr[] = DB::field('uid', $uid);
		}
		if($order) {
			$ordersql = ' ORDER BY '.DB::order($order, 'DESC');
		}
		if($limit) {
			$parameter[] = DB::limit($start, $limit);
			$ordersql .= ' %i';
		}

		$wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';

		if(empty($wheresql)) {
			return null;
		}

		return DB::fetch_all('SELECT * FROM %t '.$wheresql.$ordersql, $parameter);
	}

	public function fetch_all_by_block($aids, $bannedids, $uids, $catid, $startrow, $items, $orderby) {
		$wheres = array();
		if($aids) {
			$wheres[] = 'albumid IN ('.dimplode($aids).')';
		}
		if($bannedids) {
			$wheres[]  = 'albumid NOT IN ('.dimplode($bannedids).')';
		}
		if($uids) {
			$wheres[] = 'uid IN ('.dimplode($uids).')';
		}
		if($catid && !in_array('0', $catid)) {
			$wheres[] = 'catid IN ('.dimplode($catid).')';
		}
		$wheres[] = "friend = '0'";
		$wheresql = $wheres ? implode(' AND ', $wheres) : '1';

		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).' WHERE '.$wheresql.' ORDER BY '.DB::order($orderby, 'DESC').DB::limit($startrow, $items));
	}

	public function fetch_all_by_search($fetchtype, $uids, $albumname, $searchname, $catid, $starttime, $endtime, $albumids, $orderfield = '', $ordersort = 'DESC', $start = 0, $limit = 0, $findex = '') {
		$parameter = array($this->_table);
		$wherearr = array();
		if(is_array($uids) && count($uids)) {
			$parameter[] = $uids;
			$wherearr[] = 'uid IN(%n)';
		}

		if($albumname) {
			if($searchname == false) {
				$parameter[] = $albumname;
				$wherearr[] = 'albumname=%s';
			} else {
				$parameter[] = '%'.$albumname.'%';
				$wherearr[] = 'albumname LIKE %s';
			}
		}

		if($catid) {
			$parameter[] = $catid;
			$wherearr[] = 'catid=%d';
		}

		if($starttime) {
			$parameter[] = strtotime($starttime);
			$wherearr[] = 'dateline>%d';
		}

		if($endtime) {
			$parameter[] = $endtime;
			$wherearr[] = 'dateline<%d';
		}

		if(is_array($albumids) && count($albumids)) {
			$parameter[] = $albumids;
			$wherearr[] = 'albumid IN(%n)';
		}

		if($fetchtype == 3) {
			$selectfield = "count(*)";
		} elseif ($fetchtype == 2) {
			$selectfield = "albumid";
		} else {
			$selectfield = "*";
			if($orderfield) {
				$ordersql = 'ORDER BY '.DB::order($orderfield, $ordersort);
			}
			if($limit) {
				$parameter[] = DB::limit($start, $limit);
				$ordersql .= ' %i';
			}
		}

		if($findex) {
			$findex = 'USE INDEX('.$findex.')';
		}

		$wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';

		if($fetchtype == 3) {
			return DB::result_first("SELECT $selectfield FROM %t $wheresql", $parameter);
		} else {
			return DB::fetch_all("SELECT $selectfield FROM %t {$findex} $wheresql $ordersql", $parameter);
		}
	}
}

?>