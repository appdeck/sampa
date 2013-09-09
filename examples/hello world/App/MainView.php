<?php

namespace App;

use sampa\Core;

class MainView extends Core\View {

	public function index() {
		$this->tpl->load('template.xml');
		$this->tpl->load('index.html', 'content');
		$this->tpl->set_var('time', date('H:i:s'));
		$this->render();
	}

}
