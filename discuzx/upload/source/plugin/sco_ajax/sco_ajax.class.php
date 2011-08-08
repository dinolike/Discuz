<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include_once libfile('class/sco_dx_plugin', 'source', 'extensions/');

class plugin_sco_ajax extends _sco_dx_plugin {
	function plugin_sco_ajax() {
		$this->_init($this->_get_identifier(__METHOD__));
	}
}

class plugin_sco_ajax_forum extends plugin_sco_ajax {
	function ajax_viewthread() {
		$this->_hook('Script_forum_ajax:After_action_else', array(
				&$this,
				'_hook_ajax_viewthread'
		));
	}

	function _hook_ajax_viewthread() {
		global $_G;

		$this->_my_ajax_viewthread();

		extract($this->attr['global']);
		$plugin_self = &$this;

		include $this->_template('ajax_viewthread');

		dexit();
	}

	function _my_check_forum() {
		global $_G;

		if ($_G['forum']['status'] == 3) {
			include_once libfile('function/group');
			$status = groupperm($_G['forum'], $_G['uid']);
			if($status == 1) {
				// 'forum_group_status_off' => '該{_G/setting/navs/3/navname}已關閉',
				showmessage('forum_group_status_off');
			} elseif($status == 2) {
				// 'forum_group_noallowed' => '抱歉，您沒有權限訪問該{_G/setting/navs/3/navname}',
				showmessage('forum_group_noallowed');
			} elseif($status == 3) {
				// 'forum_group_moderated' => '請等待群主審核',
				showmessage('forum_group_moderated');
			}
		}

		if(empty($_G['forum']['allowview'])) {

			if(!$_G['forum']['viewperm'] && !$_G['group']['readaccess']) {
				showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
			} elseif($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm'])) {
				showmessagenoperm('viewperm', $_G['fid']);
			}

		} elseif($_G['forum']['allowview'] == -1) {
			showmessage('forum_access_view_disallow');
		}

		if($_G['forum']['formulaperm']) {
			formulaperm($_G['forum']['formulaperm']);
		}

		if($_G['forum']['password'] && $_G['forum']['password'] != $_G['cookie']['fidpw'.$_G['fid']]) {
			// 'forum_passwd_incorrect' => '抱歉，您輸入的密碼不正確，不能訪問這個版塊',
			showmessage('forum_passwd_incorrect', NULL);
		}
	}

	function _my_ajax_viewthread() {

	}

	/**
	 * @param array $key
	 *
	 * $key = array(
	 * 	'template' => 'forumdisplay',
	 * 	'message' => null,
	 *	'values' => null,
	 * )
	 */
	function forumdisplay_thread_output($key) {
		$this->_hook(
			'Tpl_Func_hooktags:Before',
			array(
				&$this,
				'_hook_forumdisplay_thread_output'
		));
	}

	function _hook_forumdisplay_thread_output($_EVENT, $hook_ret, $hook_id, $hook_key) {
		if ($hook_id != 'forumdisplay_thread') return;

		global $_G;

		$hook_ret = $this->_fetch_template($this->_template('forumdisplay_thread'), array(
			'_G' => &$_G,
			'thread' => &$_G['forum_threadlist'][$hook_key],

			'hook_key' => $hook_key,
		)).$hook_ret;
	}
}

?>