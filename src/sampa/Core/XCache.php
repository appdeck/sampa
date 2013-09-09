<?php
/**
*
*	XCache abstraction
*
*	@package sampa\Core\XCache
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

final class XCache {
	private static $instance = null;

	public static function singleton() {
		if ((is_null(self::$instance)) || (!(self::$instance instanceof XCache)))
			self::$instance = new XCache;
		return self::$instance;
	}

	public function flush() {
		//no operation yet
	}

	public function extended_set($index, $value, $ttl) {
		xcache_set($index, $value, $ttl);
	}

	public function __set($index, $value) {
		xcache_set($index, $value);
	}

	public function __get($index) {
		return xcache_get($index);
	}

	public function __isset($index) {
		return xcache_isset($index);
	}

	public function __unset($index) {
		xcache_unset($index);
	}
}
