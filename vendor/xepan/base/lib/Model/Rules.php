<?php

namespace xepan\base;

/**
* 
*/
class Model_Rules extends \xepan\base\Model_Table
{
	public $table='rules';
	
	function init()
	{
		parent::init();

		$this->hasMany('xepan\base\RuleOption','rule_id');
		$this->hasMany('xepan\base\PointSystem','rule_id');
	}

}