<?php

namespace xepan\hr;

class Model_Post extends \xepan\hr\Model_Document{

	public $status=['Active','InActive'];
	public $actions = [
						'Active'=>['view','edit','deactivate'],
						'InActive' => ['view','edit','delete','activate']
					];

	public $title_field = "name_with_dept";
	function init(){
		parent::init();

		$post_j = $this->join('post.document_id');
		
		$post_j->hasOne('xepan\hr\Department','department_id')->sortable(true);
		$post_j->hasOne('xepan\hr\ParentPost','parent_post_id')->sortable(true);

		$post_j->addField('name')->sortable(true);
		
		$post_j->hasMany('xepan\hr\Post','parent_post_id',null,'ParentPosts');
		$post_j->hasMany('xepan\hr\Employee','post_id',null,'Employees');
		
		$this->addCondition('type','Post');
		$this->getElement('status')->defaultValue('Active');

		$this->addExpression('employee_count')->set($this->refSQL('Employees')->count())->sortable(true);


		$this->addExpression('name_with_dept')
			->set($this->dsql()->expr('CONCAT([0]," :: ",[1])',
				[
				$this->getElement('department'),$this->getElement('name')]))->sortable(true);

		$this->is([
			'department_id|required'
			]);
		
		$this->addHook('beforeDelete',$this);
	}

	function descendantPosts($include_self = true){		
		if(!$this->loaded()) throw $this->exception('PLease call on loaded model');

		$descendants = [];

		if($include_self)
			$descendants[] = $this->id;

		$sub_posts = $this->add('xepan\hr\Model_Post');
		$sub_posts->addCondition('parent_post_id',$this->id);
		$sub_posts->addCondition('id','<>',$this->id);
		
		foreach ($sub_posts as $sub_post){
			$descendants = array_merge($descendants, $sub_post->descendantPosts(true));
		}

		return $descendants;
	}

	function activate(){
		$this['status']='Active';
		$this->saveAndUnload();
	}

	function deactivate(){
		$this['status']='InActive';
		$this->saveAndUnLoad();
	}

	function beforeDelete(){
		if($this->ref('Employees')->count()->getOne())
			throw new \Exception("Can not Delete, First delete Employees", 1);
	}
}
