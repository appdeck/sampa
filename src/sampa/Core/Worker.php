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
	const CONCURRENT = false;

	abstract public function run(array $params);

}
