<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: mobile.php 27452 2012-02-01 05:49:09Z monkey $
 */

define('IN_MOBILE_API', 1);

chdir('../../../');

require_once 'source/plugin/mobile/mobile.class.php';

if(empty($_GET['module']) || empty($_GET['version']) || !preg_match('/^[\w\.]+$/', $_GET['module']) || !preg_match('/^[\d\.]+$/', $_GET['version'])) {
	mobile_core::result(array('error' => 'param_error'));
}

$apifile = 'source/plugin/mobile/api/'.$_GET['version'].'/'.$_GET['module'].'.php';
if(file_exists($apifile)) {
	require_once $apifile;
} else {
	mobile_core::result(array('error' => 'module_not_exists'));
}

?>