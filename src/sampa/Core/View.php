<?php
/**
*
*	Base view
*
*	@package sampa\Core\View
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

abstract class View {
	//sets the response content
	protected $response;
	protected $config;
	protected $log;

	final public function __construct(&$response, &$config, &$log) {
		$this->response = $response;
		$this->config = $config;
		$this->log = $log;
	}

	final public function render($media = 'all') {
		if ((isset($this->tpl)) && (!is_null($this->response))) {
			$this->response->headers = array_merge($this->response->headers, $this->tpl->render_headers(false));
			$this->response->html($this->tpl->render_html(false, $media));
		} else
			throw new Exception\View('Trying to render view without TEMPLATE and/or RESPONSE instances');
	}

	//lazy loading
	final public function __get($index) {
		switch ($index) {
			case 'tpl':
				$domain = $this->config->read('framework/app/domain', $_SERVER['HTTP_HOST']);
				$webpath = $this->config->read('framework/app/web_path', '/');
				$base = $this->config->read('framework/app/base', '');
				$cache = new Cache(
					$this->config->read('framework/cache/driver', Cache::DISABLED),
					array(
						'host' => $this->config->read('framework/cache/host', null),
						'port' => $this->config->read('framework/cache/port', null)
					)
				);
				$app = $this->config->read('framework/app/id', '');
				$tpl = $this->config->read('framework/app/templates', '');
				if (empty($tpl))
					$tpl = __TPL__;
				else {
					$tpl = realpath($tpl);
					if (substr_compare($tpl, '/', -1, 1) != 0)
						$tpl .= '/';
				}
				if (empty($app))
					$this->tpl = new Template($tpl, $cache, $base, $this->response->language);
				else
					$this->tpl = new Template("{$tpl}{$app}" . DIRECTORY_SEPARATOR, $cache, $base, $this->response->language);

				if ((!empty($_SERVER['HTTPS'])) && ((strtolower($_SERVER['HTTPS']) === 'on') || (intval($_SERVER['HTTPS']) == 1)))
					$protocol = 'https://';
				else if ((!empty($_SERVER['HTTP_HTTPS'])) && ((strtolower($_SERVER['HTTP_HTTPS']) === 'on') || (intval($_SERVER['HTTP_HTTPS']) == 1)))
					$protocol = 'https://';
				else if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'))
					$protocol = 'https://';
				else if ((!empty($_SERVER['HTTP_X_FORWARDED_SSL'])) && ((strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') || (intval($_SERVER['HTTP_X_FORWARDED_SSL']) == 1)))
					$protocol = 'https://';
				else if ((!empty($_SERVER['SERVER_PORT'])) && (intval($_SERVER['SERVER_PORT']) == 443))
					$protocol = 'https://';
				else
					$protocol = 'http://';
				$this->tpl->set_environment('PROTOCOL', $protocol);
				$this->tpl->set_environment('DOMAIN', $domain);
				$this->tpl->set_environment('WEBPATH', $webpath);
				$this->tpl->set_environment('URI', $_SERVER['REQUEST_URI']);
				$this->tpl->set_environment('URL', "{$protocol}{$domain}{$webpath}");
				$this->tpl->set_environment('SELF', "{$protocol}{$domain}{$_SERVER['SCRIPT_NAME']}");
				if (empty($_SERVER['QUERY_STRING']))
					$this->tpl->set_environment('FULLURL', "{$protocol}{$domain}{$_SERVER['SCRIPT_NAME']}");
				else
					$this->tpl->set_environment('FULLURL', "{$protocol}{$domain}{$_SERVER['SCRIPT_NAME']}?{$_SERVER['QUERY_STRING']}");
				if (empty($_SERVER['QUERY_STRING']))
					$this->tpl->set_environment('BASEURL', "{$protocol}{$domain}{$_SERVER['REQUEST_URI']}");
				else
					$this->tpl->set_environment('BASEURL', "{$protocol}{$domain}" . substr($_SERVER['REQUEST_URI'], 0, (strlen($_SERVER['REQUEST_URI']) - (strlen($_SERVER['QUERY_STRING']) + 1))));
				return $this->tpl;
			default:
				return null;
		}
	}

}
