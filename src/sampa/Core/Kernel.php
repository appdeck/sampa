<?php
/**
*
*	Framework kernel
*
*	@package sampa\Core\Kernel
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

final class Kernel {
	private $time;
	private $log;
	private $config;
	private $boot = false;
	private $app_id = '';
	private $module = null;
	private $action = null;
	public $response;

	public function __construct($environment = null) {
		$this->time = microtime(true);
		//defines the environment name
		if (!defined('__SP_ENV__')) {
			if (is_null($environment))
				define('__SP_ENV__', '');
			else
				define('__SP_ENV__', $environment);
		}
	}

	public function __destruct() {
		if (!is_null($this->log)) {
			if (empty($_SERVER['QUERY_STRING']))
				$this->log->debug("{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']} {$_SERVER['SERVER_PROTOCOL']}");
			else
				$this->log->debug("{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}?{$_SERVER['QUERY_STRING']} {$_SERVER['SERVER_PROTOCOL']}");
			$this->log->debug('TIME: ' . Formater::msec(microtime(true) - $this->time));
			$this->log->debug('RAM: ' . Formater::size(memory_get_peak_usage(true)));
		}
	}

	public function boot($config = null, $log = null) {
		//overrides the default config folder
		if (!is_null($config)) {
			$config = realpath($config);
			if ($config !== false) {
				if (substr_compare($config, '/', -1, 1) != 0)
					$config .= '/';
				define('__SP_CFG__', $config);
			}
		}
		//overrides the default log folder
		if (!is_null($log)) {
			$log = realpath($log);
			if ($log !== false) {
				if (substr_compare($log, '/', -1, 1) != 0)
					$log .= '/';
				define('__SP_LOG__', $log);
			}
		}
		//defines the base path to framework
		define('__SAMPA__', dirname(dirname(__FILE__)));
		foreach (array('cfg', 'log', 'tpl') as $folder) {
			$key = '__SP_' . strtoupper($folder) . '__';
			$path = realpath(__SAMPA__ . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR);
			if ($path === false)
				$path = __SAMPA__;
			if (!defined($key))
				define($key, $path . DIRECTORY_SEPARATOR);
		}
		//loads the framework's configuration
		$this->config = Config::singleton();
		$pos = strpos($_SERVER['REQUEST_URI'], '?');
		if ($pos === false)
			$uri = $_SERVER['REQUEST_URI'];
		else
			$uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
		if ($this->config->set_app($_SERVER['HTTP_HOST'], $uri) === false)
			throw new Exception\Boot('Application not found!');
		//sets locale
		setlocale(\LC_ALL, $this->config->read('framework/main/locale', 'en_US.UTF8'));
		//sets output and internal encoding
		$encoding = $this->config->read('framework/main/encoding', 'UTF-8');
		mb_http_output($encoding);
		mb_internal_encoding($encoding);
		//sets the error display
		if ($this->config->read('framework/main/debug', true))
			ini_set('display_errors', 1);
		else
			ini_set('display_errors', 0);
		error_reporting(-1);
		//sets the general error handler
		set_error_handler(array($this, 'logger'));
		//sets the proper include path for shared hosting
		$include = $this->config->read('framework/main/include_path', '');
		if (!empty($include))
			set_include_path($include);
		//sets the default timezone
		date_default_timezone_set($this->config->read('framework/main/timezone', 'UTC'));
		//loads the log handler
		$logfile = sprintf('%s%s-%s-kernel.log', __SP_LOG__, date('Ymd'), $this->config->read('framework/app/id', 'app'));
		$this->log = new Log($logfile, $this->config->read('framework/log/level', Log::DISABLED), $this->config->read('framework/log/buffered', true));
		//creates basic response object
		$this->response = new Response($encoding, $this->config->read('framework/app/web_path', '/'), $this->config->read('framework/app/language', 'en-us'));
		$this->boot = true;
	}

	public function dispatch() {
		if (!$this->boot)
			$this->boot();
		//parses the request routes
		$router = new Router($this->config);
		$router->parse($module, $action, $params);
		$this->module = $module;
		$this->app_id = $this->config->read('framework/app/id', '');
		if (empty($this->app_id))
			$class = sprintf('App\\%sController', ucfirst($module));
		else
			$class = sprintf('App\\%s\\%sController', $this->app_id, ucfirst($module));
		if (!class_exists($class))
			throw new Exception\ControllerNotFound($class);
		$controller = new $class($this->response, $this->config, $this->log);
		$controller->check_alias($action);
		$this->action = $action;
		$reflection = new \ReflectionClass($controller);
		if (($reflection->hasMethod($action)) && ($reflection->getMethod($action)->isPublic())) {
			if (($reflection->hasMethod('pre')) && ($reflection->getMethod('pre')->isPublic()))
				if ($controller->pre($action, $params) === false) {
					$this->response->output();
					exit;
				}
			$cacheable = $controller->cacheable($action);
			//sets the cache id to be used by cache driver
			if (empty($_SERVER['QUERY_STRING']))
				$cache_id = sha1("{$module}{$action}{$this->response->language}" . implode('-', $params));
			else
				$cache_id = sha1("{$module}{$action}{$this->response->language}" . implode('-', $params) . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES));
			//creates a cache instance
			$cache = new Cache(
				$this->config->read('framework/cache/driver', Cache::DISABLED),
				array(
					'host' => $this->config->read('framework/cache/host', null),
					'port' => $this->config->read('framework/cache/port', null)
				)
			);
			if (($cacheable !== false) && ($cache->has($cache_id))) {
				$this->response = unserialize($cache->get($cache_id));
				$this->response->output();
			} else {
				if ($reflection->getMethod($action)->getNumberOfRequiredParameters() > count($params)) {
					if ($this->config->read('framework/app/rest', false))
						throw new Exception\REST("Method Not Allowed ({$action})", 405);
					else
						throw new Exception\ActionNotFound("Page not found ({$action})", 404);
				}
				call_user_func_array(array($controller, $action), $params);
				if (($reflection->hasMethod('hook')) && ($reflection->getMethod('hook')->isPublic()))
					$controller->hook($action, $params);
				$this->response->output();
				if ($cacheable === true)
					$cache->set($cache_id, serialize($this->response));
				else if ($cacheable !== false)
					$cache->set($cache_id, serialize($this->response), $cacheable);
			}
			if (($reflection->hasMethod('pos')) && ($reflection->getMethod('pos')->isPublic()))
				$controller->pos($action, $params);
		} else if ($this->config->read('framework/app/rest', false))
			throw new Exception\REST("Method Not Allowed ({$action})", 405);
		else
			throw new Exception\ActionNotFound("Page not found ({$action})", 404);
	}

	public function error($code, $msg = '') {
		switch ($code) {
			case 400:
				$this->response->code(400);
				$this->response->content = "400 - Bad Request ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 401:
				$this->response->code(401);
				$this->response->content = "401 - Unauthorized ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 403:
				$this->response->code(403);
				$this->response->content = "403 - Forbidden ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 404:
				$this->response->code(404);
				$this->response->content = "404 - File not found ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 405:
				$this->response->code(405);
				$this->response->content = "405 - Method Not Allowed ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 406:
				$this->response->code(406);
				$this->response->content = "406 - Not Acceptable ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 408:
				$this->response->code(408);
				$this->response->content = "408 - Request Timeout ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 409:
				$this->response->code(409);
				$this->response->content = "409 - Conflict ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 410:
				$this->response->code(410);
				$this->response->content = "410 - Gone ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 500:
				$this->response->code(500);
				$this->response->content = "500 - Internal Server Error ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 501:
				$this->response->code(501);
				$this->response->content = "501 - Not Implemented ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 502:
				$this->response->code(500);
				$this->response->content = "502 - Bad Gateway ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			case 503:
				$this->response->code(503);
				$this->response->content = "503 - Service Unavailable ({$_SERVER['REQUEST_URI']})\n{$msg}";
				break;
			default:
				$this->response->code(500);
				$this->response->content = "Unknown code: {$code} ({$_SERVER['REQUEST_URI']})\n{$msg}";
		}
		$this->response->output();
	}

	public function log($level, $message) {
		if (!is_null($this->log))
			$this->log->log($level, $message);
	}

	public function logger($num, $str, $file, $line, $context) {
		$msg = "{$str} in {$file}:{$line}";
		if (is_null($this->log))
			return false;
		switch ($num) {
			case E_ERROR:
			case E_USER_ERROR:
				$this->log->error($msg);
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$this->log->warning($msg);
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$this->log->notice($msg);
				break;
			default:
				$this->log->alert($msg);
		}
		return true;
	}

	public function get_app() {
		return $this->app_id;
	}

	public function get_module() {
		return $this->module;
	}

	public function get_action() {
		return $this->action;
	}

}
