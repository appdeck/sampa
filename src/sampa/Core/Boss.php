<?php
/**
*
*	Framework boss (handles worker jobs)
*
*	@package sampa\Core\Boss
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class Boss {
	private $time;
	private $config;
	private $boot = false;

	public function __construct($environment = null) {
		$this->time = microtime(true);
		//defines the environment name
		if (is_null($environment))
			define('__ENVIRONMENT__', '');
		else
			define('__ENVIRONMENT__', $environment);
	}

	public function __destruct() {
		printf("TIME: %s\n", Formater::msec(microtime(true) - $this->time));
		printf("RAM: %s\n", Formater::size(memory_get_peak_usage(true)));
	}

	public function boot($config = null) {
		//overrides the default config folder
		if (!is_null($config)) {
			$config = realpath($config);
			if ($config !== false) {
				if (substr_compare($config, '/', -1, 1) != 0)
					$config .= '/';
				define('__CFG__', $config);
			}
		}
		//defines the base path to framework
		define('__SAMPA__', dirname(dirname(__FILE__)));
		foreach (array('cfg', 'tpl') as $folder) {
			$key = '__' . strtoupper($folder) . '__';
			$path = realpath(__SAMPA__ . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR);
			if ($path === false)
				$path = __SAMPA__;
			if (!defined($key))
				define($key, $path . DIRECTORY_SEPARATOR);
		}
		//loads the framework's configuration
		$this->config = Config::singleton();
		//sets output and internal encoding
		$encoding = $this->config->read('framework/main/encoding', 'UTF-8');
		mb_internal_encoding($encoding);
		//sets the error display
		ini_set('display_errors', 1);
		error_reporting(1);
		//sets the proper include path for shared hosting
		$include = $this->config->read('framework/main/include_path', '');
		if (!empty($include))
			set_include_path($include);
		//sets the default timezone
		date_default_timezone_set($this->config->read('framework/main/timezone', 'UTC'));
		$this->boot = true;
	}

	public function dispatch($argc, $argv) {
		if (!$this->boot)
			$this->boot();
		if ($argc < 2)
			throw new Exception\Worker('Missing worker name');
		if (!preg_match('/[a-zA-Z0-9-_]+/', $argv[1]))
			throw new Exception\Worker("Invalid worker name '{$argv[1]}'");
		if ($argc == 2) {
			$argc++;
			$argv[2] = 'start';
		}
		$class = 'Worker\\' . ucfirst($argv[1]) . 'Worker';
		if (!class_exists($class))
			throw new Exception\Worker("Worker not found ({$class})");
		switch ($argv[2]) {
			case 'start':
				echo 'worker started (' . date('d/m/Y H:i:s') . ")\n";
				echo "worker name: {$argv[1]}\n";
				$pid = getmypid();
				echo "worker pid: {$pid}\n";
				$pidf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "{$argv[1]}.pid";
				@file_put_contents($pidf, $pid);
				$time = microtime(true);
				$worker = new $class;
				$worker->run(array_slice($argv, 3));
				$time = (microtime(true) - $time);
				echo 'worker finished (' . Formater::msec($time) . ")\n";
				@unlink($pidf);
				break;
			case 'stop':
				$pidf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "{$argv[1]}.pid";
				if (is_file($pidf)) {
					echo "worker stop\n";
					$pid = @file_get_contents($pidf);
					echo "worker pid: {$pid}\n";
					exec("kill -9 {$pid}");
				} else
					echo "worker not running\n";
				break;
			default:
				throw new Exception\Worker("Invalid operation '{$argv[2]}'");
		}
	}

}
