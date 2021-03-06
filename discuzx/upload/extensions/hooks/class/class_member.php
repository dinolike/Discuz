<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!discuz_core::$plugin_support['Scorpio_Event']) return false;

Scorpio_Hook::add('Class_logging_ctl::on_login:After_setloginstatus', '_eClass_logging_ctl__on_login_After_setloginstatus');

function _eClass_logging_ctl__on_login_After_setloginstatus($_EVENT, $_conf) {
	extract($_conf, EXTR_REFS);

	$_member = $result['member'];

	space_merge($_member, 'profile');

	include_once libfile('function/profile');
	$_space = &$_member;

	$_uid = $_space['uid'];

	$_user_updated = false;

	$_setarr = array();
	$_setarr_member = array();

	if ($_space['birthmonth'] && $_space['birthday']) {
		/**
		 * 登入時自動更新星座資訊
		 */
		$_setarr['constellation'] = get_constellation($_space['birthmonth'], $_space['birthday']);

		if ($_setarr['constellation'] == $_space['constellation']) unset($_setarr['constellation']);
	}

	if ($_space['birthyear']) {
		/**
		 * 登入時自動更新生肖資訊
		 */
		$_setarr['zodiac'] = get_zodiac($_space['birthyear']);

		if ($_setarr['zodiac'] == $_space['zodiac']) unset($_setarr['zodiac']);
	}

	if (empty($_space['avatarstatus'])) {
		@loaducenter();

		if (uc_check_avatar($_uid, 'middle')) {
			$_setarr_member['avatarstatus'] = 1;
		}
	}

	if ($_setarr_member) {
		DB::update('common_member', $_setarr_member, array('uid' => $_uid));

		foreach ($_setarr_member as $_k => $_v) {
			$_space[$_k] = $_v;
		}

		$_user_updated = true;
	}

	if($_setarr) {
		DB::update('common_member_profile', $_setarr, array('uid' => $_uid));

		/**
		 * 登入時如果個人資料產生變動自動生成動態
		 */
		$operation = 'base';

		include_once libfile('function/feed');
		feed_add('profile', 'feed_profile_update_'.$operation, array('hash_data'=>'profile'));

		$_user_updated = true;
	}

	if ($_user_updated) {
		manyoulog('user', $_uid, 'update');
	}
}

?>