<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_cloudaddons.php 27542 2012-02-03 08:40:44Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('CLOUDADDONS_WEBSITE_URL', 'http://addon.discuz.com');
define('CLOUDADDONS_DOWNLOAD_URL', 'http://addon.discuz.com/index.php');
define('CLOUDADDONS_DOWNLOAD_IP', '');
define('CLOUDADDONS_CHECK_URL', 'http://addon1.discuz.com/md5');
define('CLOUDADDONS_CHECK_IP', '');

function cloudaddons_md5($file) {
	return dfsockopen(CLOUDADDONS_CHECK_URL.'/'.$file, 0, '', '', false, CLOUDADDONS_CHECK_IP);
}

function cloudaddons_url($extra) {
	global $_G;

	require_once DISCUZ_ROOT.'./source/discuz_version.php';

	$uniqueid = C::t('common_setting')->fetch('siteuniqueid');
	$data = 'siteuniqueid='.rawurlencode($uniqueid).'&siteurl='.rawurlencode($_G['siteurl']).'&sitever='.DISCUZ_VERSION.'/'.DISCUZ_RELEASE.'&sitecharset='.CHARSET.'&mysiteid='.$_G['setting']['my_siteid'];
	$param = 'data='.rawurlencode(base64_encode($data));
	$param .= '&md5hash='.substr(md5($data.TIMESTAMP), 8, 8).'&timestamp='.TIMESTAMP;
	return CLOUDADDONS_DOWNLOAD_URL.'?'.$param.$extra;
}

function cloudaddons_open($extra, $post = '') {
	return dfsockopen(cloudaddons_url('&from=s').$extra, 0, $post, '', false, CLOUDADDONS_DOWNLOAD_IP);
}

function cloudaddons_pluginlogo_url($id) {
	return CLOUDADDONS_WEBSITE_URL.'?_'.$id;
}

function cloudaddons_installlog($addonid) {
	$array = cloudaddons_getmd5($addonid);
	if($array['RevisionID']) {
		cloudaddons_open('&mod=app&ac=installlog&rid='.$array['RevisionID']);
	}
}

function cloudaddons_downloadlog($addonid) {
	$array = cloudaddons_getmd5($addonid);
	if($array['RevisionID']) {
		cloudaddons_open('&mod=app&ac=downloadlog&rid='.$array['RevisionID']);
	}
}

function cloudaddons_faillog($rid, $type) {
	$rid = intval($rid);
	$type = intval($type);
	cloudaddons_open('&mod=app&ac=faillog&rid='.$rid.'&type='.$type.'&serverinfo='.urlencode($_SERVER['SERVER_SOFTWARE']));
}

function cloudaddons_removelog($rid) {
	cloudaddons_open('&mod=app&ac=removelog&rid='.$rid);
}

function cloudaddons_validator($addonid) {
	$array = cloudaddons_getmd5($addonid);
	if(cloudaddons_open('&mod=app&ac=validator&addonid='.$addonid.($array !== false ? '&rid='.$array['RevisionID'].'&sn='.$array['SN'].'&rd='.$array['RevisionDateline'] : '')) === '0') {
		cpmsg('cloudaddons_genuine_message', '', 'error', array('addonid' => $addonid));
	}
}

function cloudaddons_upgradecheck($addonids) {
	$post = array();
	foreach($addonids as $addonid) {
		$array = cloudaddons_getmd5($addonid);
		$post[] = 'rid['.$addonid.']='.$array['RevisionID'].'&sn['.$addonid.']='.$array['SN'].'&rd['.$addonid.']='.$array['RevisionDateline'];
	}
	return cloudaddons_open('&mod=app&ac=validator', implode('&', $post));
}

function cloudaddons_getmd5($md5file) {
	$array = array();
	if(preg_match('/^[a-z0-9_\.]+$/i', $md5file) && file_exists(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml')) {
		require_once libfile('class/xml');
		$xml = implode('', @file(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml'));
		$array = xml2array($xml);
	} else {
		return false;
	}
	return $array;
}

function cloudaddons_uninstall($md5file, $dir) {
	$array = cloudaddons_getmd5($md5file);
	if($array === false) {
		return;
	}
	if(!empty($array['RevisionID'])) {
		cloudaddons_removelog($array['RevisionID']);
	}
	@unlink(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml');
	cloudaddons_cleardir($dir);
}

function cloudaddons_savemd5($md5file, $end, $md5) {
	global $_G;
	parse_str($end, $r);
	$array = array(
	    'Title' => 'Discuz! Addon MD5',
	    'ID' => $r['ID'],
	    'SN' => $r['SN'],
	    'RevisionID' => $r['RevisionID'],
	    'RevisionDateline' => $r['RevisionDateline'],
	    'Data' => $md5,
	);
	require_once libfile('class/xml');
	if(!isset($_G['siteftp'])) {
		dmkdir(DISCUZ_ROOT.'./data/addonmd5/', 0777, false);
		$fp = fopen(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml', 'w');
		fwrite($fp, array2xml($array));
		fclose($fp);
	} else {
		$localfile = DISCUZ_ROOT.'./data/'.random(5);
		$fp = fopen($localfile, 'w');
		fwrite($fp, array2xml($array));
		fclose($fp);
		dmkdir(DISCUZ_ROOT.'./data/addonmd5/', 0777, false);
		siteftp_upload($localfile, 'data/addonmd5/'.$md5file.'.xml');
		@unlink($localfile);
	}
}

function cloudaddons_comparetree($new, $old, $basedir, $md5file, $first = 0) {
	global $_G;
	if($first && file_exists(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml')) {
		require_once libfile('class/xml');
		$xml = implode('', @file(DISCUZ_ROOT.'./data/addonmd5/'.$md5file.'.xml'));
		$array = xml2array($xml);
		$_G['treeop']['md5old'] = $array['Data'];
	}

	$dh = opendir($new);
	while(($file = readdir($dh)) !== false) {
		if($file != '.' && $file != '..') {
			$newfile = $new.'/'.$file;
			$oldfile = $old.'/'.$file;
			if(is_file($newfile)) {
				$oldfile = preg_replace('/\._addons_$/', '', $oldfile);
				$md5key = str_replace($basedir, '', preg_replace('/\._addons_$/', '', $newfile));
				$newmd5 = md5_file($newfile);
				$oldmd5 = file_exists($oldfile) ? md5_file($oldfile) : '';
				if(isset($_G['treeop']['md5old'][$md5key]) && $_G['treeop']['md5old'][$md5key] != $oldmd5) {
					$_G['treeop']['oldchange'][] = $md5key;
				}
				if($newmd5 != $oldmd5) {
					$_G['treeop']['copy'][] = $newfile;
				}
				$_G['treeop']['md5'][$md5key] = $newmd5;
			} else {
				cloudaddons_comparetree($newfile, $oldfile, $basedir);
			}
		}
	}
}

function cloudaddons_copytree($from, $to) {
	global $_G;
	$dh = opendir($from);
	while(($file = readdir($dh)) !== false) {
		if($file != '.' && $file != '..') {
			$readfile = $from.'/'.$file;
			$writefile = $to.'/'.$file;
			if(is_file($readfile)) {
				if(!in_array($readfile, $_G['treeop']['copy'])) {
					continue;
				}
				if(!isset($_G['siteftp'])) {
					$content = -1;
					if($fp = @fopen($readfile, 'r')) {
						$startTime = microtime();
						do {
							$canRead = flock($fp, LOCK_SH);
							if(!$canRead) {
								usleep(round(rand(0, 100) * 1000));
							}
						} while ((!$canRead) && ((microtime() - $startTime) < 1000));

						if(!$canRead) {
							cpmsg('cloudaddons_file_read_error', '', 'error');
						}
						$content = fread($fp, filesize($readfile));
						flock($fp, LOCK_UN);
						fclose($fp);
					}
					if($content < 0) {
						cpmsg('cloudaddons_file_read_error', '', 'error');
					}
					dmkdir(dirname($writefile), 0777, false);
					$writefile = preg_replace('/\._addons_$/', '', $writefile);
					if($fp = fopen($writefile, 'w')) {
						$startTime = microtime();
						do {
							$canWrite = flock($fp, LOCK_EX);
							if(!$canWrite) {
								usleep(round(rand(0, 100) * 1000));
							}
						} while ((!$canWrite) && ((microtime() - $startTime) < 1000));

						if(!$canWrite) {
							cpmsg('cloudaddons_file_write_error', '', 'error');
						}
						fwrite($fp, $content);
						flock($fp, LOCK_UN);
						fclose($fp);
					}
					if(!$canWrite) {
						cpmsg('cloudaddons_file_write_error', '', 'error');
					}
				} else {
					$writefile = preg_replace('/\._addons_$/', '', $writefile);
					siteftp_upload($readfile, preg_replace('/^'.preg_quote(DISCUZ_ROOT).'/', '', $writefile));
				}
				if(md5_file($readfile) != md5_file($writefile)) {
					cpmsg('cloudaddons_file_write_error', '', 'error');
				}
			} else {
				cloudaddons_copytree($readfile, $writefile);
			}

		}
	}
}

function cloudaddons_deltree($dir) {
	if($directory = @dir($dir)) {
		while($entry = $directory->read()) {
			if($entry == '.' || $entry == '..') {
				continue;
			}
			$filename = $dir.'/'.$entry;
			if(is_file($filename)) {
				@unlink($filename);
			} else {
				cloudaddons_deltree($filename);
			}
		}
		$directory->close();
		@rmdir($dir);
	}
}

function cloudaddons_cleardir($dir) {
	if(is_dir($dir)) {
		cloudaddons_deltree($dir);
	}
}

function cloudaddons_dirwriteable($basedir, $dir, $sourcedir) {
	$checkdirs = array($dir);
	cloudaddons_getsubdirs($sourcedir, $dir, $checkdirs);
	$return = array();
	foreach($checkdirs as $k => $dir) {
		$writeable = false;
		$checkdir = $basedir.'/'.$dir;
		if(!is_dir($checkdir)) {
			@mkdir($checkdir, 0777);
		}
		if(is_dir($checkdir)) {
			$fp = fopen($checkdir.'/test.txt', 'w');
			if($fp) {
				fclose($fp);
				unlink($checkdir.'/test.txt');
				$writeable = true;
			} else {
				$writeable = false;
			}
		}
		if(!$writeable && $dir) {
			$return[] = $dir;
		}
	}
	return $return;
}

function cloudaddons_getsubdirs($dir, $root, &$return) {
	static $prefix = false;
	if($prefix === false) {
		$prefix = strlen($dir) + 1;
	}
	$dh = opendir($dir);
	while(($file = readdir($dh)) !== false) {
		if($file != '.' && $file != '..') {
			$readfile = $dir.'/'.$file;
			if(is_dir($readfile)) {
				$return[] = $root.'/'.substr($readfile, $prefix);
				cloudaddons_getsubdirs($readfile, $root, $return);
			}
		}
	}
}

?>