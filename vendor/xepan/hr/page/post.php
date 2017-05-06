<?php

namespace xepan\hr;

class page_post extends \xepan\base\Page {
	public $title='Post';

	function init(){
		parent::init();

		$this->api->stickyGET('department_id');

		$post=$this->add('xepan\hr\Model_Post');
		$post->add('xepan\hr\Controller_SideBarStatusFilter');
		
		if($status = $this->api->stickyGET('status'))
			$post->addCondition('status',$status);

		if($_GET['department_id']){
			$post->addCondition('department_id',$_GET['department_id']);
		}

		$crud=$this->add('xepan\hr\CRUD',null,null,['view/post/post-grid']);
		$crud->grid->addPaginator(50);
		$crud->form->setLayout('form\post');
		$crud->setModel($post);
		
		if(!$crud->isEditing()){
			$crud->grid->controller->importField('department_id');
			
			$f=$crud->grid->addQuickSearch(['name']);

			$d_f =$f->addField('DropDown','department_id')->setEmptyText("All Department");
			$d_f->setModel('xepan\hr\Department');
			$d_f->js('change',$f->js()->submit());
		
		}
		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-post-employees')->univ()->frameURL('Post Employees',[$this->api->url('xepan_hr_employee'),'post_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}
	}

}
