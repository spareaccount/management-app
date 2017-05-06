<?php

namespace xepan\base;

class page_wizard extends \Page {
	public $title='Page Title';

	function init(){
		parent::init();

		$this->add('xepan\base\View_Wizard_Executer');
	}
}
