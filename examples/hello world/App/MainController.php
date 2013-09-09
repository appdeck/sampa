<?php

namespace App;

use sampa\Core;

class MainController extends Core\Controller {
	//uncomment next line to enable a 10s cache of index response
	//protected $cacheable = array('index' => 10);

	public function index() {
		$this->view->index();
	}

}
