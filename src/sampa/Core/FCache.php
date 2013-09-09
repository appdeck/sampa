<?php
/**
*
*	FileCache abstraction
*
*	@package sampa\Core\FCache
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class FCache {
	private static $instance = null;
	private $path;
	private $control = array();

	public function __construct($path) {
		$this->path = $path;
		//ensure that cache dir exists and has the right permissions
		if ((!is_dir($this->path)) && (!mkdir($this->path, 0777, true)))
			throw new Exception\Cache("Can't create cache dir at {$this->path}");
		//if cache control is found, loads it, else, cleans cache dir
		$file = "{$this->path}cache.control";
		if (is_file($file))
			$this->control = unserialize(file_get_contents($file));
		else
			$this->flush();
	}

	public function __destruct() {
		//saves cache control
		file_put_contents("{$this->path}cache.control", serialize($this->control));
	}

	public static function singleton($path) {
		if ((is_null(self::$instance)) || (!(self::$instance instanceof FCache)))
			self::$instance = new FCache($path);
		return self::$instance;
	}

	public function flush() {
		if (is_dir($this->path)) {
			$dir = new \DirectoryIterator($this->path);
			foreach ($dir as $file)
				if (($file->isFile()) && (preg_match('/\.(control|cache)$/i', $file->getBasename())))
					@unlink($file->getPathname());
		}
	}

	public function extended_set($index, $value, $ttl) {
		$this->control[$index] = (time() + $ttl);
		file_put_contents("{$this->path}{$index}.cache", serialize($value));
	}

	public function __set($index, $value) {
		$this->extended_set($index, $value, 3600);
	}

	public function __get($index) {
		$file = "{$this->path}{$index}.cache";
		if (is_file($file)) {
			if ((isset($this->control[$index])) && ($this->control[$index] > time()))
				return unserialize(file_get_contents($file));
		}
		return '';
	}

	public function __isset($index) {
		$file = "{$this->path}{$index}.cache";
		if (is_file($file)) {
			if ((isset($this->control[$index])) && ($this->control[$index] > time()))
				return true;
		}
		return false;
	}

	public function __unset($index) {
		if (isset($this->control[$index]))
			unset($this->control[$index]);
		$file = "{$this->path}{$index}.cache";
		if (is_file($file))
			@unlink($file);
	}
}
