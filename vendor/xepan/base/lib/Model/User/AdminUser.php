<?php


namespace xepan\base;

class Model_User_AdminUser extends Model_User{

	function init(){
		parent::init();
		
		$this->addCondition('type','AdminUser');

	}
}
