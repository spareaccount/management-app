<?php

namespace xepan\laundry;

class page_configuration extends \xepan\base\Page{
	public $title="Configuration";

	function init(){
		parent::init();

		$laundry_configuration_model = $this->add('xepan\base\Model_ConfigJsonModel',
						[
							'fields'=>[
										'total_sets'=>"line",
										'total_quilts'=>"line",
										],
							'config_key'=>'LAUNDRY_CONFIGURATION',
							'application'=>'laundry'
						]);
		$laundry_configuration_model->tryLoadAny();

		$view = $this->add('View')->addClass('panel panel-default panel-body');
		$form = $view->add('Form');
		$form->setModel($laundry_configuration_model);
		$form->addSubmit("Save")->addClass("btn btn-primary");
	
		if($form->isSubmitted()){			
			$laundry_configuration_model['total_sets'] = $form['total_sets'];
			$laundry_configuration_model['total_quilts'] = $form['total_quilts'];
			$laundry_configuration_model->save();
			
			$form->js(null,$form->js()->univ()->successMessage('Values saved'))->reload()->execute();
		}
	}
}