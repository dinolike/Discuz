<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!discuz_core::$plugin_support['Scorpio_Event']) return false;

Scorpio_Hook::add('Class_logging_ctl::on_login:Before_setloginstatus', '_eClass_logging_ctl__on_login_Before_setloginstatus');

function _eClass_logging_ctl__on_login_Before_setloginstatus($_EVENT, $_conf) {

}

?>