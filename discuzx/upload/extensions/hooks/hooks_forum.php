<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!discuz_core::$plugin_support['Scorpio_Event']) return false;

Scorpio_Hook::add('Script_forum_post:Before_action_include', '_eScript_forum_post_Before_action_include');

function _eScript_forum_post_Before_action_include($_EVENT, $_conf) {
	extract($_conf, EXTR_REFS);
}

?>