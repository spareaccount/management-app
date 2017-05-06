<?php

namespace xepan\laundry;

class Model_Report extends \xepan\laundry\Model_Register{
	public $start_date;
	public $end_date;

	function init(){
		parent::init();
		
		$this->addCondition('created_at','>=',$this->start_date);	
		$this->addCondition('created_at','<=',$this->app->nextDate($this->end_date));
		
		$this->_dsql()->group('created_date');
		
		$this->addExpression('dispatched_for_cleaning')->set(function($m,$q){
			return $m->addCondition('event','laundry')
					 ->fieldQuery('set_amount');
		});

		$this->addExpression('total_set_dispatched')->set(function($m,$q){
			return $m->sum('set_amount');
		});

		$this->addExpression('total_quilt_dispatched')->set(function($m,$q){
			return $m->sum('quilt_amount');
		});
	}
}