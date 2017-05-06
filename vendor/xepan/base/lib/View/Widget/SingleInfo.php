<?php

namespace xepan\base;

class View_Widget_SingleInfo extends \View{
	
	public $icon = "fa fa-envelope";
	public $class = "emerald-bg";
	public $heading = "message";
	public $value = "";

	function setHeading($heading){
		$this->heading = $heading;
		return $this;
	}

	function setIcon($icon_class){
		$this->icon = $icon_class;
		return $this;
	}

	function setClass($class){
		$this->class = $class;
		return $this;
	}

	function makeInfo(){
		$this->setClass('emerald-bg');
		return $this;
	}

	function makeSuccess(){
		$this->setClass('green-bg');
		return $this;
	}
	
	function makeDanger(){
		$this->setClass('red-bg');
		return $this;
	}

	function makePurple(){
		$this->setClass('purple-bg');
		return $this;
	}

	function setValue($value){
		$this->value = $value;
		return $this;
	}

	function recursiveRender(){
		$this->addClass($this->class);
		$this->template->trySet('icon',$this->icon);
		$this->template->trySetHtml('heading',$this->heading);
		$this->template->trySetHtml('value',$this->value);

		parent::recursiveRender();
	}

	function defaultTemplate(){
		return ['view/widget/singleinfo'];
	}
}