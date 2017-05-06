<?php

namespace xepan\laundry;

class page_register extends \xepan\base\Page{
	public $title = "Register Events";

	function init(){
		parent::init();

		$register_m = $this->add('xepan\laundry\Model_Register');
		$register_m->addCondition('created_at','>=',$this->app->today);
		
		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false],'crud',['view\register-crud']);		
		$crud->setModel($register_m);
		
		$crud->grid->addHook('formatRow',function($g){			
			$g->current_row_html['quilt_info'] = ' :: '.$g->model['quilt_amount'].' Quilt';
		});

		$form = $this->add('Form',null,'form');
		$form->setLayout('form\register-form');

		$event_field = $form->addField('DropDown','event')
							->setValueList(['Use for bedding'=>'Use for bedding',
											'Dispatch for cleaning'=>'Dispatch for cleaning',
											'Receive in Stock'=>'Receive in Stock'
										])
							->setEmptyText('Please select a value');	

		$form->addField('amount');
		$form->addField('quilt')->set(0);
		$form->addField('text','narration');
		$form->addField('DatePicker','date')->set($this->app->today);
		$form->addField('TimePicker','time');
		
		$given_by_field = $form->addField('dropdown','given_by');
		$given_by_field->setEmptyText('Please select a value');
		$given_by_field->setModel('xepan\hr\Model_Employee');
		
		$received_by_field = $form->addField('dropdown','received_by');
		$received_by_field->setEmptyText('Please select a value');
		$received_by_field->setModel('xepan\hr\Model_Employee');

		$form->addSubmit('Save')->addClass('btn btn-primary btn-block');
	
		if($form->isSubmitted()){
			
			$created_at = $form['date'].' '.$form['time'];
			$register_m['created_at'] = $created_at;
			
			$this->validateFields($form);
			$this->singleEntryConstraint($form,$created_at);
			$this->saveInDB($form,$created_at);

			$js_array = [$form->js()->reload(),$crud->js()->reload()];
			$form->js(null,$js_array)->univ()->successMessage('Value Saved')->execute();
		}
	}

	function validateFields($form){
		if($form['date'] > $this->app->today)
			$form->displayError('date','Date cannot be greater then today');
			
		if(!$form['event'])
			$form->displayError('event','Event field cannot left empty');

		if(!$form['given_by'])
			$form->displayError('given_by','Please fill this field');

		if(!$form['received_by'])
			$form->displayError('received_by','Please fill this field');

		if($form['amount'] < 0)
			$form->displayError('amount','Please enter positive number of set');

		if($form['quilt'] < 0)
			$form->displayError('quilt','Please enter a positive number');
	}

	function singleEntryConstraint($form, $created_at){		
		$field = '';
		switch ($form['event']) {
			case 'Use for bedding':
				$field = 'bedding';
				break;			
			case 'Dispatch for cleaning':
				$field = 'laundry';
				break;			
			default:
				$field = 'store';
				break;
		}

		$created_at = date('Y-m-d', strtotime($created_at));
		
		$register_m = $this->add('xepan\laundry\Model_Register');
		$register_m->addCondition('created_date',$created_at);
		$register_m->addCondition($field,'<>',null);
		
		if($register_m->count()->getOne()){
			throw new \Exception("Entry of this type already present, you can delete it and re-enter updated values.");
		}
	}

	function saveInDB($form, $created_at){		
		$register_m = $this->add('xepan\laundry\Model_Register');
			
		switch ($form['event']) {
			case 'Use for bedding':
				$register_m['bedding'] = true;
				break;			
			case 'Dispatch for cleaning':
				$register_m['laundry'] = true;
				break;			
			default:
				$register_m['store'] = true;
				break;
		}


		$register_m['set_amount'] = $form['amount'];
		$register_m['quilt_amount'] = $form['quilt'];
		$register_m['narration'] = $form['narration'];
		$register_m['given_by_id'] = $form['given_by'];
		$register_m['received_by_id'] = $form['received_by'];
		$register_m['created_at'] = $created_at;
		$register_m->save();
	}

	function defaultTemplate(){
		return ['page\register'];
	}
}