<?php

namespace xepan\hr;

class Model_Employee extends \xepan\base\Model_Contact{
	
	public $status=[
		'Active',
		'InActive'
	];

	public $actions=[
		'Active'=>['view','edit','delete','deactivate'],
		'InActive'=>['view','edit','delete','activate']
	];

	function init(){
		parent::init();

		$this->getElement('post')->destroy();
		$this->getElement('created_by_id')->defaultValue(@$this->app->employee->id);
		
		$emp_j = $this->join('employee.contact_id');

		$emp_j->hasOne('xepan\hr\Department','department_id')->sortable(true)->display(array('form' => 'xepan\base\DropDown'));
		$emp_j->hasOne('xepan\hr\Post','post_id')->display(array('form' => 'xepan\base\DropDown'));
		
		$emp_j->addField('offer_date')->type('date')->sortable(true);
		$emp_j->addField('doj')->caption('Date of Joining')->type('date')->defaultValue(@$this->app->now)->sortable(true);
		$emp_j->addField('contract_date')->type('date');
		$emp_j->addField('leaving_date')->type('date');

		$emp_j->hasMany('xepan\hr\Employee_Attandance','employee_id',null,'Attendances');
		$emp_j->hasMany('xepan\hr\Employee_Qualification','employee_id',null,'Qualifications');
		$emp_j->hasMany('xepan\hr\Employee_Experience','employee_id',null,'Experiences');
		$emp_j->hasMany('xepan\hr\Employee_Document','employee_id',null,'EmployeeDocuments');
		$emp_j->hasMany('xepan\hr\EmployeeDepartmentalAclAssociation','employee_id');
		
		$this->addExpression('posts')->set(function($m){
            return $m->refSQL('post_id')->fieldQuery('name');
        });
		
		$this->getElement('status')->defaultValue('Active');
		$this->addCondition('type','Employee');
	
		$this->addHook('afterSave',[$this,'throwEmployeeUpdateHook']);
		$this->addHook('beforeDelete',[$this,'deleteQualification']);
		$this->addHook('beforeDelete',[$this,'deleteExperience']);
		$this->addHook('beforeDelete',[$this,'deleteEmployeeDocument']);
	}
	function logoutHook() {

	}

	function afterLoginCheck() {

	}
	
	function getActiveEmployeeIds(){
		$emp = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active');
		$emp_ids = [];
		foreach ($emp->getRows() as $emp){
			$emp_ids [] = $emp['id'];
		}

		return $emp_ids;
	}

	function throwEmployeeUpdateHook(){
		$this->app->hook('employee_update',[$this]);
	}

	function deleteQualification(){
		$this->ref('Qualifications')->deleteAll();	
	}
	function deleteExperience(){
		$this->ref('Experiences')->deleteAll();	
	}
	function deleteEmployeeDocument(){
		$this->ref('EmployeeDocuments')->deleteAll();	
	}

	function deactivate(){
		$this['status']='InActive';
		$this->save();
		if(($user = $this->ref('user_id')) && $user->loaded()) $user->deactivate();
	}

	function activate(){
		$this['status']='Active';
		$this->save();
		if(($user = $this->ref('user_id')) && $user->loaded()) $user->activate();
	}
}
