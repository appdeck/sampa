<?php
/**
*
*	Output cache
*
*	@package sampa\Core\Cache
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

final class Cache {
	private $enabled;
	private $driver;

	const DISABLED = 0x01;
	const MCACHE = 0x02;
	const XCACHE = 0x04;
	const FCACHE = 0x08;

	public function __construct($driver, $conf = array()) {
		try {
			$this->enabled = true;
			switch (strtolower($driver)) {
				case self::MCACHE:
					if (!isset($conf['host']))
						$conf['host'] = null;
					if (!isset($conf['port']))
						$conf['port'] = null;
					$this->driver = MCache::singleton($conf['host'], $conf['port']);
					break;
				case self::XCACHE:
					$this->driver = XCache::singleton();
					break;
				case self::FCACHE:
					$this->driver = FCache::singleton(__SAMPA__ . '/cache/');
					break;
				case self::DISABLED:
					$this->enabled = false;
					break;
				default:
					throw new Exception\Cache("Invalid cache driver: '{$driver}'");
			}
		} catch (\Exception $e) {
			trigger_error($e->getMessage());
			$this->enabled = false;
		}
	}

	public function clean() {
		if ($this->enabled)
			$this->driver->flush();
	}

	public function has($id) {
		if ($this->enabled)
			return isset($this->driver->{$id});
		return false;
	}

	public function set($id, $data, $timeout = 3600) {
		if ($this->enabled)
			$this->driver->extended_set($id, $data, $timeout);
	}

	public function del($id) {
		if ($this->enabled)
			unset($this->driver->{$id});
	}

	public function get($id, $default = null) {
		if (($this->enabled) && ($this->has($id)))
			return $this->driver->{$id};
		return $default;
	}

}
