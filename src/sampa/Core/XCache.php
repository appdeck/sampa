<?php
/**
*
*	XCache abstraction
*
*	@package sampa\Core\XCache
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
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
		if (function_exists('xcache_set'))
			xcache_set($index, $value, $ttl);
	}

	public function __set($index, $value) {
		if (function_exists('xcache_set'))
			xcache_set($index, $value);
	}

	public function __get($index) {
		if (function_exists('xcache_get'))
			return xcache_get($index);
		return null;
	}

	public function __isset($index) {
		if (function_exists('xcache_isset'))
			return xcache_isset($index);
		return false;
	}

	public function __unset($index) {
		if (function_exists('xcache_unset'))
			xcache_unset($index);
	}
}
