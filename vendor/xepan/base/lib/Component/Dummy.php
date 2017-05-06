<?php


namespace xepan\base;

class Component_Dummy extends \View{
	public $options = [];
	
	function init(){
		parent::init();
		$this->add('View')->set(($this->options['text']?:"Button").' '. rand(100,999))->js('click')->reload();
	}
}
