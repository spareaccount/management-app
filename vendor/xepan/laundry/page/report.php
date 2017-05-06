<?php

namespace xepan\laundry;

class page_report extends \xepan\base\Page{
	public $title = "Report";

	function init(){
		parent::init();
		
		$from_date = $this->app->stickyGET('from_date');
		$to_date = $this->app->stickyGET('to_date');
		$report_format = $this->app->stickyGET('report_format');

		$view = $this->add('xepan\laundry\View_Report',['report_format'=>$report_format,'start_date'=>$from_date,'end_date'=>$to_date],'view');

		$form = $this->add('Form',null,'form');
		$report_format_field = $form->addField('dropdown','report_format')
			 						->setValueList(['format1'=>'Format 1','format2'=>'Format 2'])
			 						->setEmptyText('please select a value');
			 						
		$date_range_field = $form->addField('DateRangePicker','date_range')
								 ->setStartDate($this->app->now)
								 ->setEndDate($this->app->now)
								 ->getBackDatesSet();

	    $date_range_field->addClass('xepan-push-small');
	 	$form->addSubmit('Get Report')->addClass('btn btn-primary');
		
		if($form->isSubmitted()){			
			if(!$form['report_format'])
				$form->displayError('report_format','Report Format field cannot left empty');

			$_from_date = $date_range_field->getStartDate();
        	$_to_date = $date_range_field->getEndDate();		

			$form->js(null,$view->js()
								->reload(
								[
									'from_date'=>$_from_date,
									'to_date'=>$_to_date,
									'report_format'=>$form['report_format']
								]))->univ()->successMessage('wait ... ')->execute();
		}
	}

	function defaultTemplate(){
		return ['page\report'];
	}
}