<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_index.php 27203 2012-01-11 03:14:19Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/forumlist');

$gid = intval(getgpc('gid'));
$showoldetails = get_index_online_details();

if(!$_G['uid'] && !$gid && $_G['setting']['cacheindexlife'] && !defined('IN_ARCHIVER') && !defined('IN_MOBILE')) {
	get_index_page_guest_cache();
}

$newthreads = round((TIMESTAMP - $_G['member']['lastvisit'] + 600) / 1000) * 1000;

$catlist = $forumlist = $sublist = $forumname = $collapse = $favforumlist = array();
$threads = $posts = $todayposts = $announcepm = 0;
$postdata = $_G['cache']['historyposts'] ? explode("\t", $_G['cache']['historyposts']) : array(0,0);
$postdata[0] = intval($postdata[0]);
$postdata[1] = intval($postdata[1]);

list($navtitle, $metadescription, $metakeywords) = get_seosetting('forum');
if(!$navtitle) {
	$navtitle = $_G['setting']['navs'][2]['navname'];
	$nobbname = false;
} else {
	$nobbname = true;
}
if(!$metadescription) {
	$metadescription = $navtitle;
}
if(!$metakeywords) {
	$metakeywords = $navtitle;
}

if($_G['setting']['indexhot']['status'] && $_G['cache']['heats']['expiration'] < TIMESTAMP) {
	require_once libfile('function/cache');
	updatecache('heats');
}

if($_G['uid']) {
	$favfids = array();
	$forum_favlist = C::t('home_favorite')->fetch_all_by_uid_idtype($_G['uid'], 'fid');
	foreach($forum_favlist as $key => $favorite) {
		if(defined('IN_MOBILE')) {
			$forum_favlist[$key]['title'] = strip_tags($favorite['title']);
		}
		$favfids[] = $favorite['id'];
	}
	if($favfids) {
		$favforumlist = C::t('forum_forum')->fetch_all($favfids);
		foreach($favforumlist as $id => $forum) {
			forum($favforumlist[$id]);
		}
	}
}


if(empty($gid) && empty($_G['member']['accessmasks']) && empty($showoldetails)) {
	extract(get_index_memory_by_groupid($_G['member']['groupid']));
	if(defined('FORUM_INDEX_PAGE_MEMORY') && FORUM_INDEX_PAGE_MEMORY) {
		categorycollapse();
		if(!defined('IN_ARCHIVER')) {
			include template('diy:forum/discuz');
		} else {
			include loadarchiver('forum/discuz');
		}
		dexit();
	}
}

if(!$gid && (!defined('FORUM_INDEX_PAGE_MEMORY') || !FORUM_INDEX_PAGE_MEMORY)) {
	$announcements = get_index_announcements();


	$forums = C::t('forum_forum')->fetch_all_by_status(1);
	$fids = array();
	foreach($forums as $forum) {
		$fids[$forum['fid']] = $forum['fid'];
	}

	$forum_access = array();
	if(!empty($_G['member']['accessmasks'])) {
		$forum_access = C::t('forum_access')->fetch_all_by_fid_uid($fids, $_G['uid']);
	}

	$forum_fields = C::t('forum_forumfield')->fetch_all($fids);

	foreach($forums as $forum) {
		$forum = array_merge($forum, $forum_fields[$forum['fid']]);
		if($forum_access['fid']) {
			$forum = array_merge($forum, $forum_access[$forum['fid']]);
		}
		$forumname[$forum['fid']] = strip_tags($forum['name']);
		$forum['extra'] = empty($forum['extra']) ? array() : dunserialize($forum['extra']);
		if(!is_array($forum['extra'])) {
			$forum['extra'] = array();
		}

		if($forum['type'] != 'group') {

			$threads += $forum['threads'];
			$posts += $forum['posts'];
			$todayposts += $forum['todayposts'];

			if($forum['type'] == 'forum' && isset($catlist[$forum['fup']])) {
				if(forum($forum)) {
					$catlist[$forum['fup']]['forums'][] = $forum['fid'];
					$forum['orderid'] = $catlist[$forum['fup']]['forumscount']++;
					$forum['subforums'] = '';
					$forumlist[$forum['fid']] = $forum;
				}

			} elseif(isset($forumlist[$forum['fup']])) {

				$forumlist[$forum['fup']]['threads'] += $forum['threads'];
				$forumlist[$forum['fup']]['posts'] += $forum['posts'];
				$forumlist[$forum['fup']]['todayposts'] += $forum['todayposts'];
				if($_G['setting']['subforumsindex'] && $forumlist[$forum['fup']]['permission'] == 2 && !($forumlist[$forum['fup']]['simple'] & 16) || ($forumlist[$forum['fup']]['simple'] & 8)) {
					$forumurl = !empty($forum['domain']) && !empty($_G['setting']['domain']['root']['forum']) ? 'http://'.$forum['domain'].'.'.$_G['setting']['domain']['root']['forum'] : 'forum.php?mod=forumdisplay&fid='.$forum['fid'];
					$forumlist[$forum['fup']]['subforums'] .= (empty($forumlist[$forum['fup']]['subforums']) ? '' : ', ').'<a href="'.$forumurl.'" '.(!empty($forum['extra']['namecolor']) ? ' style="color: ' . $forum['extra']['namecolor'].';"' : '') . '>'.$forum['name'].'</a>';
				}
			}

		} else {

			if($forum['moderators']) {
			 	$forum['moderators'] = moddisplay($forum['moderators'], 'flat');
			}
			$forum['forumscount'] 	= 0;
			$catlist[$forum['fid']] = $forum;

		}
	}

	foreach($catlist as $catid => $category) {
		$catlist[$catid]['collapseimg'] = 'collapsed_no.gif';
		if($catlist[$catid]['forumscount'] && $category['forumcolumns']) {
			$catlist[$catid]['forumcolwidth'] = (floor(100 / $category['forumcolumns']) - 0.1).'%';
			$catlist[$catid]['endrows'] = '';
			if($colspan = $category['forumscount'] % $category['forumcolumns']) {
				while(($category['forumcolumns'] - $colspan) > 0) {
					$catlist[$catid]['endrows'] .= '<td width="'.$catlist[$catid]['forumcolwidth'].'">&nbsp;</td>';
					$colspan ++;
				}
				$catlist[$catid]['endrows'] .= '</tr>';
			}
		} elseif(empty($category['forumscount'])) {
			unset($catlist[$catid]);
		}
	}
	unset($catid, $category);

	if(isset($catlist[0]) && $catlist[0]['forumscount']) {
		$catlist[0]['fid'] = 0;
		$catlist[0]['type'] = 'group';
		$catlist[0]['name'] = $_G['setting']['bbname'];
		$catlist[0]['collapseimg'] = 'collapsed_no.gif';
	} else {
		unset($catlist[0]);
	}

	if(!IS_ROBOT && ($_G['setting']['whosonlinestatus'] == 1 || $_G['setting']['whosonlinestatus'] == 3)) {
		$_G['setting']['whosonlinestatus'] = 1;

		$onlineinfo = explode("\t", $_G['cache']['onlinerecord']);
		if(empty($_G['cookie']['onlineusernum'])) {
			$onlinenum = C::app()->session->count();
			if($onlinenum > $onlineinfo[0]) {
				$onlinerecord = "$onlinenum\t".TIMESTAMP;
				C::t('common_setting')->update('onlinerecord', $onlinerecord);
				savecache('onlinerecord', $onlinerecord);
				$onlineinfo = array($onlinenum, TIMESTAMP);
			}
			dsetcookie('onlineusernum', intval($onlinenum), 300);
		} else {
			$onlinenum = intval($_G['cookie']['onlineusernum']);
		}
		$onlineinfo[1] = dgmdate($onlineinfo[1], 'd');

		$detailstatus = $showoldetails == 'yes' || (((!isset($_G['cookie']['onlineindex']) && !$_G['setting']['whosonline_contract']) || $_G['cookie']['onlineindex']) && $onlinenum < 500 && !$showoldetails);

		$guestcount = $membercount = 0;
		if(!empty($_G['setting']['sessionclose'])) {
			$detailstatus = false;
			$membercount = C::app()->session->count(1);
			$guestcount = $onlinenum - $membercount;
		}

		if($detailstatus) {
			$actioncode = lang('action');

			$_G['uid'] && updatesession();
			$whosonline = array();

			$_G['setting']['maxonlinelist'] = $_G['setting']['maxonlinelist'] ? $_G['setting']['maxonlinelist'] : 500;
			foreach(C::app()->session->fetch_member(1, 0, $_G['setting']['maxonlinelist']) as $online){
				$membercount ++;
				if($online['invisible']) {
					$invisiblecount++;
					continue;
				} else {
					$online['icon'] = !empty($_G['cache']['onlinelist'][$online['groupid']]) ? $_G['cache']['onlinelist'][$online['groupid']] : $_G['cache']['onlinelist'][0];
				}
				$online['lastactivity'] = dgmdate($online['lastactivity'], 't');
				$whosonline[] = $online;
			}
			if(isset($_G['cache']['onlinelist'][7]) && $_G['setting']['maxonlinelist'] > $membercount) {
				foreach(C::app()->session->fetch_member(2, 0, $_G['setting']['maxonlinelist'] - $membercount) as $online){
					$online['icon'] = $_G['cache']['onlinelist'][7];
					$online['username'] = $_G['cache']['onlinelist']['guest'];
					$online['lastactivity'] = dgmdate($online['lastactivity'], 't');
					$whosonline[] = $online;
				}
			}
			unset($actioncode, $online);

			if($onlinenum > $_G['setting']['maxonlinelist']) {
				$membercount = C::app()->session->count(1);
				$invisiblecount = C::app()->session->count_invisible();
			}

			if($onlinenum < $membercount) {
				$onlinenum = C::app()->session->count();
				dsetcookie('onlineusernum', intval($onlinenum), 300);
			}

			$guestcount = $onlinenum - $membercount;

			unset($online);
		}

	} else {
		$_G['setting']['whosonlinestatus'] = 0;
	}

	if(defined('FORUM_INDEX_PAGE_MEMORY') && !FORUM_INDEX_PAGE_MEMORY) {
		$key = !IS_ROBOT ? $_G['member']['groupid'] : 'for_robot';
		memory('set', 'forum_index_page_'.$key, array(
			'catlist' => $catlist,
			'forumlist' => $forumlist,
			'sublist' => $sublist,
			'whosonline' => $whosonline,
			'onlinenum' => $onlinenum,
			'membercount' => $membercount,
			'guestcount' => $guestcount,
			'announcements' => $announcements,
			'threads' => $threads,
			'posts' => $posts,
			'todayposts' => $todayposts,
			'onlineinfo' => $onlineinfo,
			'announcepm' => $announcepm), getglobal('setting/memory/forumindex'));
	}

} else {
	require_once DISCUZ_ROOT.'./source/include/misc/misc_category.php';
}


if(defined('IN_ARCHIVER')) {
	include loadarchiver('forum/discuz');
	exit();
}
categorycollapse();

if($gid && !empty($catlist)) {
	$_G['category'] = $catlist[$gid];
	$forumseoset = array(
		'seotitle' => $catlist[$gid]['seotitle'],
		'seokeywords' => $catlist[$gid]['keywords'],
		'seodescription' => $catlist[$gid]['seodescription']
	);
	$seodata = array('fgroup' => $catlist[$gid]['name']);
	list($navtitle, $metadescription, $metakeywords) = get_seosetting('threadlist', $seodata, $forumseoset);
	if(empty($navtitle)) {
		$navtitle = $navtitle_g;
		$nobbname = false;
	} else {
		$nobbname = true;
	}
	$_G['fid'] = $gid;
}

include template('diy:forum/discuz:'.$gid);

function get_index_announcements() {
	global $_G;
	$announcements = '';
	if($_G['cache']['announcements']) {
		$readapmids = !empty($_G['cookie']['readapmid']) ? explode('D', $_G['cookie']['readapmid']) : array();
		foreach($_G['cache']['announcements'] as $announcement) {
			if(!$announcement['endtime'] || $announcement['endtime'] > TIMESTAMP && (empty($announcement['groups']) || in_array($_G['member']['groupid'], $announcement['groups']))) {
				if(empty($announcement['type'])) {
					$announcements .= '<li><span><a href="forum.php?mod=announcement&id='.$announcement['id'].'" target="_blank" class="xi2">'.$announcement['subject'].
						'</a></span><em>('.dgmdate($announcement['starttime'], 'd').')</em></li>';
				} elseif($announcement['type'] == 1) {
					$announcements .= '<li><span><a href="'.$announcement['message'].'" target="_blank" class="xi2">'.$announcement['subject'].
						'</a></span><em>('.dgmdate($announcement['starttime'], 'd').')</em></li>';
				}
			}
		}
	}
	return $announcements;
}

function get_index_page_guest_cache() {
	global $_G;
	$indexcache = getcacheinfo(0);
	if(TIMESTAMP - $indexcache['filemtime'] > $_G['setting']['cacheindexlife']) {
		@unlink($indexcache['filename']);
		define('CACHE_FILE', $indexcache['filename']);
	} elseif($indexcache['filename']) {
		@readfile($indexcache['filename']);
		$updatetime = dgmdate($indexcache['filemtime'], 'H:i:s');
		$gzip = $_G['gzipcompress'] ? ', Gzip enabled' : '';
		echo "<script type=\"text/javascript\">
			if($('debuginfo')) {
				$('debuginfo').innerHTML = '. This page is cached  at $updatetime $gzip .';
			}
			</script>";
		exit();
	}
}

function get_index_memory_by_groupid($key) {
	$enable = getglobal('setting/memory/forumindex');
	if($enable !== null && memory('check')) {
		if(IS_ROBOT) {
			$key = 'for_robot';
		}
		$ret = memory('get', 'forum_index_page_'.$key);
		define('FORUM_INDEX_PAGE_MEMORY', $ret ? 1 : 0);
		if($ret) {
			return $ret;
		}
	}
	return array('none' => null);
}

function get_index_online_details() {
	$showoldetails = getgpc('showoldetails');
	switch($showoldetails) {
		case 'no': dsetcookie('onlineindex', ''); break;
		case 'yes': dsetcookie('onlineindex', 1, 86400 * 365); break;
	}
	return $showoldetails;
}

function do_forum_bind_domains() {
	global $_G;
	if($_G['setting']['binddomains'] && $_G['setting']['forumdomains']) {
		$loadforum = isset($_G['setting']['binddomains'][$_SERVER['HTTP_HOST']]) ? max(0, intval($_G['setting']['binddomains'][$_SERVER['HTTP_HOST']])) : 0;
		if($loadforum) {
			dheader('Location: '.$_G['setting']['siteurl'].'/forum.php?mod=forumdisplay&fid='.$loadforum);
		}
	}
}

function categorycollapse() {
	global $_G, $collapse, $catlist;
	if(!$_G['uid']) {
		return;
	}
	foreach($catlist as $fid => $forum) {
		if(!isset($_G['cookie']['collapse']) || strpos($_G['cookie']['collapse'], '_category_'.$fid.'_') === FALSE) {
			$catlist[$fid]['collapseimg'] = 'collapsed_no.gif';
			$collapse['category_'.$fid] = '';
		} else {
			$catlist[$fid]['collapseimg'] = 'collapsed_yes.gif';
			$collapse['category_'.$fid] = 'display: none';
		}
	}
	if(!isset($_G['cookie']['collapse']) || strpos($_G['cookie']['collapse'], '_category_0_') === FALSE) {
		$collapse['collapseimg_0'] = 'collapsed_no.gif';
		$collapse['category_0'] = '';
	} else {
		$collapse['collapseimg_0'] = 'collapsed_yes.gif';
		$collapse['category_0'] = 'display: none';
	}
}
?>