<?php
/**
*
*	Configuration handling
*
*	@package sampa\Core\Config
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class Config {
	private static $instance = null;
	private $config = array();

	public static function singleton() {
		if ((is_null(self::$instance)) || (!(self::$instance instanceof Config)))
			self::$instance = new Config;
		return self::$instance;
	}

	public function __construct() {
		$this->load('framework');
	}

	public function has($filename) {
		if (defined('__SP_CFG__'))
			$file = __SP_CFG__ . "{$filename}.config.php";
		else
			$file = "./{$filename}.config.php";
		return is_file($file);
	}

	public function load($filename) {
		if (empty($this->config[$filename])) {
			if (defined('__SP_CFG__'))
				$file = __SP_CFG__ . "{$filename}.config.php";
			else
				$file = "./{$filename}.config.php";
			if (is_file($file)) {
				require $file;
				if (isset($_sampa)) {
					if ((defined('__SP_ENV__')) && (!empty($_sampa[__SP_ENV__])))
						$this->config[$filename] = $_sampa[__SP_ENV__];
					else
						$this->config[$filename] = $_sampa;
				} else
					throw new Exception\Config("Configuration data not found in '{$file}'");
			} else
				throw new Exception\Config("Configuration file not found '{$file}'");
		}
	}

	public function unload($filename) {
		if (isset($this->config[$filename]))
			unset($this->config[$filename]);
	}

	public function set_app($domain, $path) {
		if (empty($this->config['framework']['app']))
			return false;
		if (isset($this->config['framework']['app']['domain'], $this->config['framework']['app']['web_path'])) {
			if (($this->config['framework']['app']['domain'] === $domain) &&
				(strncmp($path, $this->config['framework']['app']['web_path'], strlen($this->config['framework']['app']['web_path'])) == 0))
				return true;
			return false;
		}
		foreach ($this->config['framework']['app'] as $id => $app)
			if ((isset($app['domain'], $app['web_path'])) && ($app['domain'] === $domain) &&
				(strncmp($path, $app['web_path'], strlen($app['web_path'])) == 0)) {
				$this->config['framework']['app'] = $app;
				if (!isset($this->config['framework']['app']['id']))
					$this->config['framework']['app']['id'] = $id;
				if (!isset($this->config['framework']['app']['path']))
					$this->config['framework']['app']['path'] = $id;
				return true;
			}
		return false;
	}

	public function read($path, $default = null) {
		$fields = explode('/', $path);
		$nav = $this->config;
		foreach ($fields as $field)
			if (isset($nav[$field]))
				$nav = &$nav[$field];
			else
				return $default;
		return $nav;
	}

}
