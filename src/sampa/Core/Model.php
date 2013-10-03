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

	protected function get_db_config() {
		$this->config->load('db');
		$config = array(
			'dsn' => $this->config->read('db/dsn', ''),
			'user' => $this->config->read('db/user', ''),
			'pass' => $this->config->read('db/pass', '')
		);
		$this->config->unload('db');
		return $config;
	}

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
				$config = $this->get_db_config();
				$this->sql = new SQL($config['dsn'], $config['user'], $config['pass']);
				return $this->sql;
			default:
				return null;
		}
	}
}
