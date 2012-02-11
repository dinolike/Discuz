<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: OAuth.php 26976 2011-12-28 13:43:13Z zhouxiaobo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Cloud_Service_Client_OAuth {

	protected $_appKey = '';
	protected $_appSecret = '';
	protected $_apiIp = '';

	private $_tokenSecret = '';
	private $_boundary = '';

	const OAUTH_VERSION = '1.0';
	const OAUTH_SIGNATURE_METHOD = 'HMAC-SHA1';

	protected static $_instance;

	public static function getInstance() {

		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function getRequest($requestURL, $extra = array(), $oauthMethod = 'GET', $multi = array()) {

		if($multi) {
			$imageFile = $this->_getImageBinary($multi);
			$extra = array_merge($extra, $imageFile['binary']);
		}

		$params = $this->_getOAuthSignatureParams($extra);
		$params['oauth_signature'] = $this->_getOAuthSignature($requestURL, $params, $oauthMethod, $multi ? TRUE : FALSE);
		$queryString = $this->_httpBuildQuery($params, $imageFile['fileInfo']);
		if($oauthMethod == 'GET') {
			$requestURL = $requestURL.'?'.$queryString;
			$queryString = '';
		} else {
			$requestURL = $requestURL.'?';
		}

		$response = dfsockopen($requestURL, 0, $queryString, '', false, $this->_apiIp, 15, TRUE, !$multi ? 'URLENCODE' : 'FORMDATA');
		return $response;
	}

	public function getTimestamp() {
		return time();
	}

	public function getOAuthNonce() {
		return time().(time() % $this->_appKey);
	}

	protected function setAppKey($appKey, $appSecret) {
		$this->_appKey = $appKey;
		$this->_appSecret = $appSecret;
	}

	protected function setTokenSecret($tokenSecret) {
		$this->_tokenSecret = $tokenSecret;
	}

	protected function setApiIp($apiIp) {
		$this->_apiIp = $apiIp;
	}

	public function customHmac($str, $key) {
		$signature = '';
		if(function_exists('hash_hmac')) {
			$signature = base64_encode(hash_hmac("sha1", $str, $key, true));
		} else {
			$blocksize = 64;
			$hashfunc = 'sha1';
			if(strlen($key) > $blocksize) {
				$key = pack('H*', $hashfunc($key));
			}
			$key = str_pad($key,$blocksize,chr(0x00));
			$ipad = str_repeat(chr(0x36),$blocksize);
			$opad = str_repeat(chr(0x5c),$blocksize);
			$hmac = pack('H*',$hashfunc(($key^$opad).pack('H*',$hashfunc(($key^$ipad).$str))));
			$signature = base64_encode($hmac);
		}

		return $signature;
	}

	private function _getOAuthSignatureParams($extra = array()) {

		$params = array(
			'oauth_consumer_key' => $this->_appKey,
			'oauth_nonce' => $this->getOAuthNonce(),
			'oauth_signature_method' => self::OAUTH_SIGNATURE_METHOD,
			'oauth_timestamp' => $this->getTimestamp(),
			'oauth_version' => self::OAUTH_VERSION,
		);

		if($extra) {
			$params = array_merge($params, $extra);
		}
		uksort($params, 'strcmp');

		return $params;
	}

	private function _httpBuildQuery($params, $multi = array()) {

		if(!$params) {
			return '';
		}

		$multiPartBody = '';
		if($multi) {
			$this->_boundary = uniqid('------------------');
			$bodyBoundary = '--'.$this->_boundary;
			$endBodyBoundary = $bodyBoundary.'--';

			foreach($params as $param => $value) {
				if(array_key_exists($param, $multi)) {
					$ext = strtolower(substr(strrchr($multi[$param]['file'], '.'), 1, 10));
					$fileName = 'picture.'.$ext;
					$mime = $multi[$param]['mime'];
					$multiPartBody .= $bodyBoundary."\r\n";
					$multiPartBody .= 'Content-Disposition: form-data; name="'.$param.'"; filename="'.$fileName.'"'."\r\n";
					$multiPartBody .= 'Content-Type: '.$mime."\r\n\r\n";
					$multiPartBody .= $value. "\r\n";
				} else {
					$multiPartBody .= $bodyBoundary . "\r\n";
					$multiPartBody .= 'content-disposition: form-data; name="'.$param."\"\r\n\r\n";
					$multiPartBody .= $value."\r\n";
				}
			}
			$multiPartBody .= $endBodyBoundary."\r\n";
		} else {
			foreach($params as $param => $value) {
				$multiPartBody .= $comma.$this->rawurlencode($param).'='.$this->rawurlencode($value);
				$comma = '&';
			}
		}

		return $multiPartBody;
	}

	private function _getOAuthSignature($url, $params, $method = 'POST', $multi = FALSE) {

		$method = strtoupper($method);
		if(!in_array($method, array ('GET', 'POST'))) {
			throw new Exception('Request Method Invlid');
		}

		foreach($params as $name => $val) {
			$param_str .= $comma.$name.'='.$val;
			$comma = '&';
		}

		if($multi) {
			$base_string = $method.'&'.$url.'&'.$param_str;
		} else {
			$base_string = $method.'&'.$this->rawurlencode($url).'&'.$this->rawurlencode($param_str);
		}

		$key = $this->_appSecret.'&'.$this->_tokenSecret;
		$signature = $this->customHmac($base_string, $key);

		return $signature;
	}

	public function rawurlencode($input) {
		if(is_array($input)) {
			return array_map(array(__CLASS__, 'rawurlencode'));
		} elseif(is_scalar($input)) {
			return str_replace('%7E', '~', rawurlencode($input));
		} else {
			return '';
		}
	}

	private function _getImageBinary($files) {

		$keys = array_keys($files);
		$fileInfo = array();
		foreach($keys as $key) {
			if($key != 'remote') {
				$fileInfo[$key]['file'] = $files[$key];
				$imgInfo = getimagesize($files[$key]);
				$fileInfo[$key]['mime'] = $imgInfo['mime'];

				$contents = $use_include_path = null;
				if($files['remote']) {
					$opt = array(
						'http' => array(
							'timeout' => 10,
						)
					);
					$contents = stream_context_create($opt);
				}
				$files[$key] = file_get_contents($files[$key], $use_include_path, $contents);
			}
		}

		unset($files['remote']);
		return array('binary' => $files, 'fileInfo' => $fileInfo);
	}

}


?>