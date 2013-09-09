<?php
/**
*
*	Worker base
*
*	@package sampa\Core\Log
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

abstract class Worker extends Plugin {
	private $lock = null;

	private function lock() {
		$name = str_replace('\\', '-', get_class($this));
		$this->lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "{$name}.lock", 'w');
		if (is_resource($this->lock))
			return flock($this->lock, LOCK_EX | LOCK_NB);
		return false;
	}

	private function unlock() {
		if (is_resource($this->lock))
			flock($this->lock, LOCK_UN);
	}

	abstract public function run(array $params);

}
