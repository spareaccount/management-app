<?php

namespace xepan\base;

class Model_User extends \xepan\base\Model_Table{

	public $table="user";
	public $acl=true;
	public $title_field = "username";
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
		
		$this->hasOne('xepan\base\Contact','created_by_id')->defaultValue(@$this->app->employee->id)->display(array('form'=>'xepan\base\Basic'));

		$this->addField('username')->sortable(true);
		$this->addField('password')->type('password');
		$this->addField('type');
		$this->addField('scope')->defaultValue('SuperUser');
		$this->addField('hash');
		$this->addField('last_login_date')->type('datetime');
		$this->addField('status')->enum(['Active','Inactive'])->defaultValue('Active');
		$this->addCondition('type','User');
		$this->hasMany('xepan\base\Contact','user_id',null,'Contacts');
		$this->is([
				'username|unique|to_trim|required|email',
				'status|required'
			]);

		$this->addExpression('related_contact')->set(function($m,$q){
			return $m->refSQL('Contacts')->setLimit(1)->fieldQuery('name');
		})->sortable(true);

		$this->addExpression('related_contact_type')->set(function($m,$q){
			return $m->refSQL('Contacts')->setLimit(1)->fieldQuery('type');
		})->sortable(true);
	}

	function isSuperUser(){
		return $this['scope']=='SuperUser';
	}

	function updatePassword($new_password){
		if(!$this->loaded()) return false;
			$this['password']=$new_password;
			$this->save();
			return $this;
	}

	function deactivate(){
		$this['status']='InActive';
		$this->save();
	}

	function activate(){
		$this['status']='Active';
		$this->save();
	}
}
