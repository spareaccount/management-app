<?php

namespace xepan\hr;

class Model_Employee_Attandance extends \xepan\base\Model_Table{
	public $table = "employee_attandance";
	public $acl = false;
	
	function init(){
		parent::init();

		$this->hasOne('xepan\hr\Employee','employee_id');
		
		$this->addField('shift1')->type('datetime');		
		$this->addField('shift2')->type('boolean');		
		$this->addField('shift3')->type('boolean');		
		$this->addField('rest')->type('boolean');		
		$this->addField('created_date')->type('date');		
	}

	function isHoliday($today){
	}
	
	function insertAttendanceFromCSV($present_employee_list){
	}
}