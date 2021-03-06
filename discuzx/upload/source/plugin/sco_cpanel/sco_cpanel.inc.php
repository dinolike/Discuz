<?php

/**
 * @author bluelovers
 * @copyright 2011
 */

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//error_reporting(E_ALL ^E_NOTICE ^E_STRICT);

include_once dirname(__FILE__).'/./class_sco_cpanel_admincp.php';

if (empty($_G['gp_cpmod'])) {

	$_cpanel = new plugin_sco_cpanel_admincp();
	$_cpanel
		->init($plugin['identifier'])
		->set(array(
			'cpmod' => $_G['gp_cpmod'],
			'module' => &$module,
		))
		->run()
	;

} else {

	$_cpanel = plugin_sco_cpanel_admincp::mod($_G['gp_cpmod'], $plugin['identifier']);

	$_cpanel
		->set(array(
			'op' => $_G['gp_op'],
			'module' => &$module,
		))
		->run()
	;

}

?>