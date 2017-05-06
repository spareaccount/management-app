<?php
namespace xepan\base;

class CRUD extends \CRUD{
	
	public $grid_class='xepan\base\Grid';
	public $action_page=null;
	public $edit_page=null;
	public $pass_acl=false;

	function initializeTemplate($template_spot = null, $template_branch = null){
		if(!$this->grid_options) $this->grid_options=[];
			$this->grid_options['defaultTemplate']= $template_branch;
		parent::initializeTemplate($template_spot, null);
	}

	protected function configureAdd($fields){
		if($this->add_button)
			$this->add_button->addClass(' btn btn-primary pull-right');
		if($this->action_page){
			$this->add_button->setHTML('<i class="icon-plus"></i> Add '.htmlspecialchars($this->entity_name));
			$this->add_button->js('click')->univ()->location($this->api->url($this->action_page,['action'=>'add']));
		}else
			parent::configureAdd($fields);
	}

	protected function configureEdit($fields){
		if($this->action_page || $this->edit_page){
			$this->grid->addColumn('template','edit')->setTemplate(' ');
			$this->grid->on('click','.pb_edit')->univ()->location(
				[
					$this->api->url($this->edit_page?:$this->action_page),
					[
						'action'=>'edit',
						$this->model->table.'_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')
					]
				]
			);
		}else{
			parent::configureEdit($fields);
		}
	}

	function formSubmit($form){
		try {
			$hook_value = $this->hook('formSubmit',array($form));
			if($hook_value[0]){
	            $self = $this;
	            $this->api->addHook('pre-render', function () use ($self) {
	                $self->formSubmitSuccess()->execute();
	            });
				return;	
			}else{
				
				return parent::formSubmit($form);
			}
        } catch (Exception_ValidityCheck $e) {
            $form->displayError($e->getField(), $e->getMessage());
        }
		return false;		
	}
	

}