<?php

namespace xepan\base;

/**
* 
*/
class Model_RulesOption extends \xepan\base\Model_Table
{
	public $table='rule-options';
	
	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Rules','rule_id');
		$this->hasMany('xepan\base\PointSystem','rule_option_id');
	}
}