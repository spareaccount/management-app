<?php

namespace xepan\laundry;

class Model_Register extends \xepan\base\Model_Table{
	public $table = "laundry_register";
	public $acl = false;

	function init(){
		parent::init();
		
		$this->hasOne('xepan\hr\Employee','created_by_id')
			 ->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\hr\Employee','given_by_id');
		$this->hasOne('xepan\hr\Employee','received_by_id');

		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
									 
		$this->addField('laundry')->type('boolean');
		$this->addField('bedding')->type('boolean');
		$this->addField('store')->type('boolean');
		$this->addField('set_amount');
		$this->addField('quilt_amount');
		$this->addField('extra_info')->type('text');
		$this->addField('narration')->type('text');

		$this->addExpression('created_date')->set('DATE(created_at)');
		
		$this->addExpression('event')->set(function($m,$q){
			return $q->expr(
					"IF([laundry] = '1','laundry',
						if([bedding] = '1','bedding','store'))",
					[
						'laundry'=> $m->getElement('laundry'),
						'bedding'=> $m->getElement('bedding')
					]);
		});
	}
}
