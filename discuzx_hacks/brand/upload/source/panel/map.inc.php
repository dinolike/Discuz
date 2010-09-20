<?php

/**
 *      [品牌空間] (C)2001-2010 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: map.inc.php 4359 2010-09-07 07:58:57Z fanshengshuai $
 */

if(!defined('IN_STORE')) {
	exit('Acess Denied');
}

if(!empty($_POST['valuesubmit'])) {
	$query = DB::query('UPDATE '.tname('shopmessage')." SET mapapimark = '$_POST[inputmap]' WHERE itemid='".$_G['myshopid']."'");
	$_BCACHE->deltype('detail', 'shop', $_G['myshopid']);
	cpmsg('update_success', 'panel.php?action=map');
} else {

	$wheresql = ' i.itemid=\''.$_G['myshopid'].'\'';

	//取得信息
	$editvalue = DB::fetch_first('SELECT i.itemid,m.nid,m.mapapimark,i.address FROM '.tname('shopitems').
		' i INNER JOIN '.tname('shopmessage').' m ON i.itemid=m.itemid WHERE '.
		$wheresql.'');
	if(empty($editvalue)) {
		cpmsg('no_item', 'panel.php?action=list&m='.$mname);
	}
	//顯示導航以及表頭
	shownav($mname, $mname.'_'.$_GET['action']);
	showsubmenu($mname.'_'.$_GET['action']);
	showformheader('map');
	showtableheader();

	showmapsetting('shop', $_G['setting']['mapapikey'], $editvalue['mapapimark']); //顯示地圖設置
	showhiddenfields(array('itemid' => $editvalue['itemid']));
	showhiddenfields(array('nid' => $editvalue['nid']));
	showhiddenfields(array('valuesubmit' => 'yes'));
	echo '<tr><td colspan="15"><div class="fixsel"><input type="submit" value="'.lang('mapsubmit').'" name="settingsubmit" id="submit_settingsubmit" class="btn"> <input type="button" class="btn"  name="" value="reset" id="resm" /></div></td></tr>';
	showtablefooter();
	showformfooter();
}

?>