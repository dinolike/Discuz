<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: register.php 27451 2012-02-01 05:48:47Z monkey $
 */

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

$_GET['mod'] = 'register';
include_once 'member.php';

class mobile_api {

	function common() {
	}

	function output() {
		global $_G;
		$variable = array(
			'setting/bbrulestxt' => $_G['setting']['bbrulestxt'],
			'setting/bbrules' => $_G['setting']['bbrules'],
			'setting/reginput' => $_G['setting']['reginput'],
			'setting/regstatus' => $_G['setting']['regstatus'],
		);
		mobile_core::result(mobile_core::variable($variable));
	}

}

?>