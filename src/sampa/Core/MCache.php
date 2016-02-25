<?php
/**
*
*	Memcache abstraction
*
*	@package sampa\Core\MCache
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

final class MCache {
	private static $instance = null;
	private $memcache = null;

	public function __construct($host, $port) {
		if (class_exists('\\Memcache')) {
			$this->memcache = new \Memcache;
			if (!$this->memcache->connect($host, $port))
				throw new Exception\Cache("Can't connect to memcache server at {$host}:{$port}");
		}
	}

	public static function singleton($host = 'localhost', $port = 11211) {
		if (is_null($host))
			$host = 'localhost';
		if (is_null($port))
			$port = 11211;
		if ((is_null(self::$instance)) || (!(self::$instance instanceof MCache)))
			self::$instance = new MCache($host, $port);
		return self::$instance;
	}

	public function flush() {
		if (is_null($this->memcache))
			return;
		$this->memcache->flush();
	}

	public function extended_set($index, $value, $ttl) {
		if (is_null($this->memcache))
			return;
		$this->memcache->set($index, $value, MEMCACHE_COMPRESSED, $ttl);
	}

	public function __set($index, $value) {
		if (is_null($this->memcache))
			return;
		$this->memcache->set($index, $value, MEMCACHE_COMPRESSED, 0);
	}

	public function __get($index) {
		if (is_null($this->memcache))
			return null;
		return $this->memcache->get($index);
	}

	public function __isset($index) {
		if (is_null($this->memcache))
			return false;
		$flag = false;
		if (($this->memcache->get($index, $flag) === false) && ($flag === false))
			return false;
		return true;
	}

	public function __unset($index) {
		if (is_null($this->memcache))
			return;
		$this->memcache->delete($index, 0);
	}
}
