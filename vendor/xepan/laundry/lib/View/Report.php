<?php

namespace xepan\laundry;

class View_Report extends \View{
	public $report_format;
	public $start_date;
	public $end_date;
	public $grid;
	public $count = 0 ;

	function init(){
		parent::init();

		
		if($this->report_format == 'format1'){
			$register_m  = $this->add('xepan\laundry\Model_Register');
			$this->grid = $this->add('xepan\hr\Grid',null,null,['view\report-format1']);
			$this->grid->setModel($register_m);
			
			$this->grid->addHook('formatRow',function($g){			
				$g->current_row_html['quilt_info'] = ' :: '.$g->model['quilt_amount'].' Quilt';
			});			
		}
		
		if($this->report_format == 'format2'){
			$report_m = $this->add('xepan\laundry\Model_Report',['start_date'=>$this->start_date,'end_date'=>$this->end_date]);

			$this->grid = $this->add('xepan\hr\Grid',null,null,['view\report-format2']);
			$this->grid->setModel($report_m);	
			
			$report_m->tryLoadAny();
			$this->grid->template->trySet('quilt_total',$report_m['total_quilt_dispatched']);
			$this->grid->template->trySet('set_total',$report_m['total_set_dispatched']);
		}

		if($this->grid){
			$this->grid->addHook('formatRow',function($g){
				$this->count++;
				$g->current_row_html['sno'] = $this->count; 				
			});

			$this->count = 0;
		}
	}
}