<?php
/**
*
*	Routing class
*
*	@package sampa\Core\Router
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

final class Router {
	private $config;

	private function rewrite($uri) {
		$this->config->load('rewrite');
		$rules = $this->config->read('rewrite', array());
		$this->config->unload('rewrite');
		foreach ($rules as $regex => $rule)
			if (preg_match($regex, $uri))
				return preg_replace($regex, $rule, $uri);
		return $uri;
	}

	private function route($uri, &$module, &$action, &$params) {
		if (strncmp($uri, '/', 1) == 0)
			$uri = substr($uri, 1);
		if (substr_compare($uri, '/', -1, 1) == 0)
			$uri = substr($uri, 0, (strlen($uri) - 1));
		$slice = explode('/', $uri);
		$module = $slice[0];
		if ($this->config->read('framework/app/rest', false)) {
			$action = strtolower($_SERVER['REQUEST_METHOD']);
			$params = array_slice($slice, 1);
		} else {
			if (empty($slice[1]))
				$action = 'index';
			else
				$action = $slice[1];
			$params = array_slice($slice, 2);
		}
	}

	public function __construct(&$config) {
		$this->config = $config;
	}

	public function parse(&$module, &$action, &$params) {
		$uri = filter_var(trim($_SERVER['REQUEST_URI']), FILTER_SANITIZE_URL);
		//removes base path from request uri
		$base = $this->config->read('framework/app/web_path', '/');
		$len = strlen($base);
		if (strncmp($uri, $base, $len) == 0)
			$uri = substr($uri, ($len - 1));
		else
			throw new Exception\Router("Web path doesn't match ({$uri})");
		//removes query string from request uri
		$pos = strpos($uri, '?');
		if ($pos !== false)
			$uri = substr($uri, 0, $pos);
		//parses the rewrite rules
		if ($this->config->has('rewrite'))
			$uri = $this->rewrite($uri);
		if (($uri === '/') || ($uri === '')) {
			$module = $this->config->read('framework/app/default_module', 'main');
			if ($this->config->read('framework/app/rest', false))
				$action = strtolower($_SERVER['REQUEST_METHOD']);
			else
				$action = 'index';
			$params = array();
		} else {
			//request is complex /module/action/...
			$this->route($uri, $module, $action, $params);
			if ((!preg_match('/^[a-z_][a-z0-9_]*$/i', $action)) || (strncmp($action, '__', 2) == 0))
				throw new Exception\Router("Invalid action name: {$action}");
		}
		if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $module))
			throw new Exception\Router("Invalid module name: {$module}");
	}
}
