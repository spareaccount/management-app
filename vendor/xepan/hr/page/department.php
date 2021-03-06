<?php

namespace xepan\hr;

class page_department extends \xepan\base\Page {
	public $title='Department';

	function init(){
		parent::init();
		
		$department=$this->add('xepan\hr\Model_Department');
		$department->add('xepan\hr\Controller_SideBarStatusFilter');
		
		if($status = $this->app->stickyGET('status'))
			$department->addCondition('status',$status);


		$crud=$this->add('xepan\hr\CRUD',null,null,['view/department/department-grid']);
		$crud->grid->addPaginator(50);
		
		if(!$crud->isEditing())
			$crud->grid->template->trySet('dept-url',$this->app->url('xepan_hr_structurechart'));

		$crud->setModel($department);

		$crud->grid->addHook('formatRow',function($g){
			if($g->model['is_system']) {
				$g->current_row_html['edit']  = '<span class="fa-stack table-link"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-pencil fa-stack-1x fa-inverse"></i></span>';
				$g->current_row_html['delete']= '<span class="table-link fa-stack"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-trash-o fa-stack-1x fa-inverse"></i></span>';
				$g->current_row_html['action']='';
			}
			
		});

		$f=$crud->grid->addQuickSearch(['name']);

		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-department-post')->univ()->frameURL('Department Post',[$this->api->url('xepan_hr_post'),'department_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$crud->grid->js('click')->_selector('.do-view-department-employee')->univ()->frameURL('Department Employee',[$this->api->url('xepan_hr_employee'),'department_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id'),'status'=>'']);
		}
	}
}
