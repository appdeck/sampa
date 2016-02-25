<?php
/**
*
*	Basic Cryptography abstraction
*
*	@package sampa\Core\Secure
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

final class Secure {
	private $seed;

	public function __construct($seed) {
		$this->seed = $seed;
	}

	public function md5($data) {
		return hash('md5', $this->seed . $data);
	}

	public function sha256($data) {
		return hash('sha256', $this->seed . $data);
	}

	public function sha512($data) {
		return hash('sha512', $this->seed . $data);
	}

	public function encrypt_3des($key, $data, $hash = 'sha256') {
		return base64_encode($this->encrypt($key, $data, MCRYPT_3DES, $hash));
	}

	public function decrypt_3des($key, $data, $hash = 'sha256') {
		return $this->decrypt($key, base64_decode($data), MCRYPT_3DES, $hash);
	}

	public function encrypt_blowfish($key, $data, $hash = 'sha256') {
		return base64_encode($this->encrypt($key, $data, MCRYPT_BLOWFISH, $hash));
	}

	public function decrypt_blowfish($key, $data, $hash = 'sha256') {
		return $this->decrypt($key, base64_decode($data), MCRYPT_BLOWFISH, $hash);
	}

	public function encrypt_rijndael($key, $data, $hash = 'sha256') {
		return base64_encode($this->encrypt($key, $data, MCRYPT_RIJNDAEL_256, $hash));
	}

	public function decrypt_rijndael($key, $data, $hash = 'sha256') {
		return $this->decrypt($key, base64_decode($data), MCRYPT_RIJNDAEL_256, $hash);
	}

	private function encrypt($key, $data, $cipher = MCRYPT_3DES, $hash = 'sha256') {
		$td = mcrypt_module_open($cipher, '', MCRYPT_MODE_ECB, '');
		if ($td === false)
			return false;
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		if ($iv === false)
			return false;
		$ks = mcrypt_enc_get_key_size($td);
		switch ($hash) {
			case 'md5':
				$key = substr($this->md5($key), 0, $ks);
			case 'sha256':
				$key = substr($this->sha256($key), 0, $ks);
			case 'sha512':
				$key = substr($this->sha512($key), 0, $ks);
			case 'none':
			default:
				$key = substr($key, 0, $ks);
		}
		mcrypt_generic_init($td, $key, $iv);
		$ret = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $iv . $ret;
	}

	private function decrypt($key, $data, $cipher = MCRYPT_3DES, $hash = 'sha256') {
		$td = mcrypt_module_open($cipher, '', MCRYPT_MODE_ECB, '');
		if ($td === false)
			return false;
		$size = mcrypt_enc_get_iv_size($td);
		$iv = substr($data, 0, $size);
		$data = substr($data, $size);
		$ks = mcrypt_enc_get_key_size($td);
		switch ($hash) {
			case 'md5':
				$key = substr($this->md5($key), 0, $ks);
			case 'sha256':
				$key = substr($this->sha256($key), 0, $ks);
			case 'sha512':
				$key = substr($this->sha512($key), 0, $ks);
			case 'none':
			default:
				$key = substr($key, 0, $ks);
		}
		mcrypt_generic_init($td, $key, $iv);
		$ret = mdecrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $ret;
	}

}
