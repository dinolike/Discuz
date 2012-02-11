<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: collection_index.php 26928 2011-12-28 01:46:08Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$navtitle = lang('core', 'title_collection');
$searchtitle = '';
$oplist = array('all', 'my', 'search');
if(!in_array($op, $oplist)) {
	$op = '';
}

$cpp = $tpp;
$start = ($page-1)*$cpp;

if($op == 'all' || $op == 'search') {
	if($op == 'search' && $_GET['kw']) {
		$orderbyarr = array('dateline');
		$searchtitle = $_GET['kw'];
		$count = C::t('forum_collection')->count_by_title($searchtitle);
	} else {
		$orderbyarr = array('follownum', 'threadnum', 'commentnum', 'dateline');
		$count = C::t('forum_collection')->count();
	}

	$orderby = (in_array($_GET['order'], $orderbyarr)) ? $_GET['order'] : 'dateline';
	$collectiondata = processCollectionData(C::t('forum_collection')->fetch_all('', $orderby, 'DESC', $start, $cpp, $searchtitle));
	$htmlsearchtitle = htmlspecialchars($searchtitle);
	$multipage = multi($count, $cpp, $page, 'forum.php?mod=collection&op='.$op.(($htmlsearchtitle) ? '&kw='.$htmlsearchtitle : ''));

	include template('forum/collection_all');
} elseif ($op == 'my') {
	$mycollection = C::t('forum_collection')->fetch_all_by_uid($_G['uid']);
	$myctid = array_keys($mycollection);
	$teamworker = C::t('forum_collectionteamworker')->fetch_all_by_uid($_G['uid']);
	$twctid = array_keys($teamworker);
	$follow = C::t('forum_collectionfollow')->fetch_all_by_uid($_G['uid']);
	$followctid = array_keys($follow);

	if(!$myctid) {
		$myctid = array();
	}
	if(!$twctid) {
		$twctid = array();
	}
	if(!$followctid) {
		$followctid = array();
	}

	$ctidlist = array_merge($myctid, $twctid, $followctid);

	if(count($ctidlist) > 0) {
		$tfcollection = $mycollection + $teamworker + $follow;
		$collectiondata = C::t('forum_collection')->fetch_all($ctidlist, 'dateline', 'DESC');
		$collectiondata = processCollectionData($collectiondata, $tfcollection);
	}

	include template('forum/collection_mycollection');
} else {
	if(!$tid) {
		$hotcollection = array();
		loadcache('collection');

		if(TIMESTAMP - $_G['cache']['collection']['dateline'] > 300) {
			$collection = C::t('forum_collection')->range(0, 500, 10);
			if(!$collection) {
				$collection = C::t('forum_collection')->range(0, 500);
			}
			$collectioncache = array('dateline' => TIMESTAMP, 'data' => $collection);
			savecache('collection', $collectioncache);
		} else {
			$collection = &$_G['cache']['collection']['data'];
		}
		$count = count($collection);
		for($i = $start; $i < $start+$cpp; $i++) {
			if(!$collection[$i]) {
				break;
			}
			$hotcollection[] = $collection[$i];
		}
		unset($collection);
		$hotcollection = processCollectionData($hotcollection);
	} else {
		$tidrelate = C::t('forum_collectionrelated')->fetch($tid);
		$ctids = explode("\t", $tidrelate['collection'], -1);
		$count = count($ctids);
		$hotcollection = C::t('forum_collection')->fetch_all($ctids, 'follownum', 'DESC', $start, $cpp);
		$hotcollection = processCollectionData($hotcollection);
	}

	$multipage = multi($count, $cpp, $page, 'forum.php?mod=collection'.($tid ? '&tid='.$tid : ''));

	include template('forum/collection_index');
}


?>