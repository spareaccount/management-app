<?php



namespace xepan\base;

class Model_Contact_Relation extends Model_Contact_Info{

	function init(){
		parent::init();
			
		$this->getElement('head')->enum(['Father','Mother','Other']);
		$this->addCondition('type','Relation');
	}
}
