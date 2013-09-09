<?php
/**
*
*	Plugin base
*
*	@package sampa\Core\Plugin
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

class Plugin {

	//lazy loading
	final public function __get($index) {
		switch ($index) {
			case 'config':
				$this->config = Config::singleton();
				return $this->config;
			case 'cache':
				$this->cache = new Cache(
					$this->config->read('framework/cache/driver', Cache::DISABLED),
					array(
						'host' => $this->config->read('framework/cache/host', null),
						'port' => $this->config->read('framework/cache/port', null)
					)
				);
				return $this->cache;
			case 'sql':
				$this->config->load('db');
				$this->sql = new SQL($this->config->read('db/dsn', ''), $this->config->read('db/user', ''), $this->config->read('db/pass', ''));
				$this->config->unload('db');
				return $this->sql;
			case 'secure':
				$this->secure = new Secure($this->config->read('framework/secure/seed', 'sampa-framework'));
				return $this->secure;
			case 'log':
				$logfile = __LOG__ . date('Ymd') . '-' . str_replace('_', '-', strtolower(get_class($this))) . '.log';
				$this->log = new Log($logfile, $this->config->read('framework/log/level', Log::DISABLED), $this->config->read('framework/log/buffered', true));
				return $this->log;
			case 'session':
				$this->session = Session::singleton($this->config);
				return $this->session;
			default:
				return null;
		}
	}

}
