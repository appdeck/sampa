<?php
/**
*
*	Base controller
*
*	@package sampa\Core\Controller
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

class Controller {
	//response dispatcher instance
	protected $response;
	//log instance
	protected $log;
	//config instance
	protected $config;
	//web path
	protected $path;
	//domain
	protected $domain;
	//sets the aliases for this controller
	protected $alias = array();
	//sets the cacheable actions
	protected $cacheable = array();

	public function __construct(&$response, &$config, &$log) {
		$this->response = $response;
		$this->log = $log;
		$this->config = $config;
		$this->domain = $config->read('framework/app/domain', $_SERVER['HTTP_HOST']);
		$this->path = $config->read('framework/app/web_path', '/');
	}

	//changes an alias for a given action
	final public function check_alias(&$action) {
		if (!empty($this->alias[$action]))
			$action = $this->alias[$action];
	}

	//checks if action is cacheable
	final public function cacheable($action) {
		if (empty($this->cacheable))
			return false;
		//if action is just cacheable, with no timeout defined
		if (in_array($action, $this->cacheable))
			return true;
		//if action is cacheable, but has a defined timeout
		if (isset($this->cacheable[$action]))
			return $this->cacheable[$action];
		return false;
	}

	//returns real client ip address
	final protected function get_ip_address($no_proxy = false) {
		if ($no_proxy)
			return $_SERVER['REMOTE_ADDR'];
		foreach (array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
			if (array_key_exists($key, $_SERVER) === true)
				foreach (explode(',', $_SERVER[$key]) as $ip)
					if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
						return $ip;
	}

	//return cliente accept language
	final protected function get_language() {
		if (preg_match('/[a-z]{2}-[a-z]{2}/', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches))
			return $matches[0];
		return false;
	}

	//checks if request is made via an ajax request
	final protected function is_ajax() {
		if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'))
			return true;
		return false;
	}

	//checks if request is made via https
	final protected function is_secure() {
		if ((!empty($_SERVER['HTTPS'])) && ((strtolower($_SERVER['HTTPS']) === 'on') || (intval($_SERVER['HTTPS']) == 1)))
			return true;
		if ((!empty($_SERVER['HTTP_HTTPS'])) && ((strtolower($_SERVER['HTTP_HTTPS']) === 'on') || (intval($_SERVER['HTTP_HTTPS']) == 1)))
			return true;
		if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'))
			return true;
		if ((!empty($_SERVER['HTTP_X_FORWARDED_SSL'])) && ((strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') || (intval($_SERVER['HTTP_X_FORWARDED_SSL']) == 1)))
			return true;
		if ((!empty($_SERVER['SERVER_PORT'])) && (intval($_SERVER['SERVER_PORT']) == 443))
			return true;
		return false;
	}

	//redirects the request to https scheme
	final protected function make_secure() {
		$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		if (!empty($_SERVER['QUERY_STRING']))
			$url .= "?{$_SERVER['QUERY_STRING']}";
		$this->response->redirect($url);
	}

	//retrieves request method
	final protected function request_method() {
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}

	//checks if request comes from expected referer
	final protected function check_referer() {
		if ((!empty($_SERVER['HTTP_REFERER'])) && (!preg_match('/^https?:\/\/' . preg_quote($this->domain, '/') . '/', $_SERVER['HTTP_REFERER'])))
			return false;
		return true;
	}

	//returns current url
	final protected function get_url($qs = false) {
		if ($this->is_secure())
			$schema = 'https://';
		else
			$schema = 'http://';
		$uri = $this->get_uri($qs);
		return "{$schema}{$_SERVER['HTTP_HOST']}{$uri}";
	}

	//returns current uri
	final protected function get_uri($qs = false) {
		$mark = strpos($_SERVER['REQUEST_URI'], '?');
		if ($mark === false)
			$uri = $_SERVER['REQUEST_URI'];
		else
			$uri = substr($_SERVER['REQUEST_URI'], 0, $mark);
		if ($qs) {
			if (empty($_SERVER['QUERY_STRING']))
				$qs = '';
			else
				$qs = "?{$_SERVER['QUERY_STRING']}";
		} else
			$qs = '';
		return "{$uri}{$qs}";
	}

	//loads a model
	final public function load_model($name) {
		if (substr_compare($name, 'Model', -5) != 0)
			$name = "{$name}Model";
		if (!class_exists($name))
			throw new Exception\ModelNotFound($name);
		return new $name($this->config, $this->log);
	}

	//loads a view
	final public function load_view($name) {
		if (substr_compare($name, 'View', -4) != 0)
			$name = "{$name}View";
		if (!class_exists($name))
			throw new Exception\ViewNotFound($name);
		return new $name($this->response, $this->config, $this->log);
	}

	//lazy loading
	final public function __get($index) {
		switch ($index) {
			case 'cache':
				$this->cache = new Cache(
					$this->config->read('framework/cache/driver', Cache::DISABLED),
					array(
						'host' => $this->config->read('framework/cache/host', null),
						'port' => $this->config->read('framework/cache/port', null)
					)
				);
				return $this->cache;
			case 'cookie':
				$this->cookie = Cookie::singleton();
				return $this->cookie;
			case 'model':
				$class = str_replace('Controller', 'Model', get_class($this));
				$this->model = $this->load_model($class);
				return $this->model;
			case 'session':
				$this->session = Session::singleton($this->config);
				return $this->session;
			case 'view':
				$class = str_replace('Controller', 'View', get_class($this));
				$this->view = $this->load_view($class);
				return $this->view;
			default:
				return null;
		}
	}
}
