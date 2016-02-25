<?php
/**
*
*	Worker base
*
*	@package sampa\Core\Log
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

abstract class Worker extends Plugin {
	const CONCURRENT = false;

	protected $name;

	public function name() {
		if (empty($this->name))
			return get_class($this);
		return $this->name;
	}

	abstract public function run(array $params);

}
