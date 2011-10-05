<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!$_G['uid'] || $_G['adminid'] != 1) {
	showmessage('admin_nopermission', NULL);
}

$authoridnew = $_G['gp_authoridnew'];

if (!submitcheck('modsubmit')) {

	include_once libfile('class/sco_dx_plugin', 'source', 'extensions/');

	$_p = new _sco_dx_plugin();
	$_p->identifier = 'sco_cpanel';

	discuz_core::$tpl['forum']['topicadmin_action'][$_G['gp_action']] = $_p->_fetch_template($_p->_template('forum/topicadmin_action_author'));

	include template('forum/topicadmin_action');
}

?>