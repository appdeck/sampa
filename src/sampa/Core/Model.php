<?php
/**
*
*	Base model
*
*	@package sampa\Core\Model
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

abstract class Model {
	protected $config;
	protected $log;

	final public function __construct(&$config, &$log) {
		$this->config = $config;
		$this->log = $log;
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
			case 'secure':
				$this->secure = new Secure($this->config->read('framework/secure/seed', 'sampa-framework'));
				return $this->secure;
			case 'sql':
				$this->config->load('db');
				$this->sql = new SQL($this->config->read('db/dsn', ''), $this->config->read('db/user', ''), $this->config->read('db/pass', ''));
				$this->config->unload('db');
				return $this->sql;
			default:
				return null;
		}
	}
}
