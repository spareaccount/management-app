<?php


namespace xepan\base;

class Model_Contact_Event extends Model_Contact_Info{

	function init(){
		parent::init();
			
		$this->getElement('head')->enum(['DOB','Anniversary']);
		$this->addCondition('type','Event');
	}
}
