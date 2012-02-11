<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_block.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_block extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_block';
		$this->_pk    = 'bid';

		parent::__construct();
	}

	public function fetch($bid) {
		if(($block = parent::fetch(dintval($bid)))) {
			$block['param'] = $block['param'] ? dunserialize($block['param']) : array();
		}
		return $block;
	}

	public function count_by_admincpwhere($wheresql) {
		$wheresql = $wheresql ? ' WHERE '.$wheresql : '';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table).' b LEFT JOIN '.DB::table('common_template_block').' tb ON tb.bid=b.bid'.$wheresql);
	}

	public function fetch_all_by_admincpwhere($wheresql, $ordersql, $start, $limit) {
		$wheresql = $wheresql ? ' WHERE '.$wheresql : '';
		return DB::fetch_all('SELECT b.*, tb.targettplname FROM '.DB::table($this->_table).' b LEFT JOIN '.DB::table('common_template_block').' tb ON tb.bid=b.bid'.$wheresql.' '.$ordersql.DB::limit($start, $limit), null, $this->_pk ? $this->_pk : '');
	}

	public function update_by_styleid($styleid, $data) {
		return $styleid ? DB::update($this->_table, $data, DB::field('styleid', $styleid)) : false;
	}

	public function fetch_by_styleid($styleid) {
		 return $styleid ? DB::fetch_first('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field('styleid', $styleid).' LIMIT 1') : array();
	}

	public function fetch_all_bid_by_blocktype($blocktype, $limit = 1000) {
		$data = array();
		if (($data = DB::fetch_all('SELECT bid FROM '.DB::table($this->_table).' WHERE '.DB::field('blocktype', $blocktype).' ORDER BY bid DESC'.DB::limit($limit), null, $this->_pk))) {
			$data = array_keys($data);
		}
		return $data;
	}

	public function update_dateline_to_expired($bids, $timestamp) {
		return $bids ? DB::query('UPDATE '.DB::table($this->_table).' SET `dateline`='.$timestamp.'-cachetime WHERE bid IN ('.dimplode($bids).') AND cachetime>0') : false;
	}

	public function fetch_all_recommended_block($id, $idtype, $wherearr = array(), $leftjoin = '', $fields = '') {
		$data = array();
		if($id && $idtype) {
			$where = $wherearr ? ' AND '.implode(' AND ', $wherearr) : '';
			$data = DB::fetch_all("SELECT bi.dataid,bi.uid,bi.username,bi.dateline,bi.isverified,bi.verifiedtime,b.bid,b.blockclass,b.name,b.script$fields FROM ".DB::table('common_block').' b
				LEFT JOIN '.DB::table('common_block_item_data')." bi ON b.bid=bi.bid $leftjoin WHERE bi.id='$id' AND bi.idtype='$idtype'$where ORDER BY b.bid DESC", null, 'bid');
		}
		return $data;
	}


	public function count_by_where($wheresql, $leftjoin = '') {
		return DB::result_first("SELECT COUNT(*) FROM ".DB::table($this->_table).' b'." $leftjoin $wheresql");
	}

	public function fetch_all_by_where($wheresql, $start = 0, $limit = 0, $leftjoin = '', $fields = '') {
		return DB::fetch_all("SELECT b.bid,b.blockclass,b.name,b.script,b.param$fields FROM ".DB::table($this->_table).' b'." $leftjoin $wheresql ORDER BY b.bid DESC ".DB::limit($start, $limit));
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		$this->_pre_cache_key = 'blockcache_';
		$this->_cache_ttl = getglobal('setting/memory/diyblock');
		$this->_allowmem = $this->_cache_ttl && memory('check');
		return parent::update($val, $data, $unbuffered, $low_priority);
	}

	public function delete($val, $unbuffered = false) {
		$this->_pre_cache_key = 'blockcache_';
		$this->_cache_ttl = getglobal('setting/memory/diyblock');
		$this->_allowmem = $this->_cache_ttl && memory('check');
		return parent::delete($val, $unbuffered);
	}
}

?>