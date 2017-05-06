<?php

namespace xepan\hr;

class page_employeeattandance extends \xepan\base\Page{
	public $title ="Employee Attandance";

	function init(){
		parent::init();

		$employee = $this->add('xepan\hr\Model_Employee');
		$employee->addCondition('status','Active');
		$form=$this->add('Form',null,null,['form/empty']);

		$header= $form->add('Columns')->addClass('row');
		$c00=$header->addColumn(4)->addClass('col-md-4')->add('H4')->set('Employee');
		$c11=$header->addColumn(6)->addClass('col-md-6')->add('H4')->set('Shift');
		$c22=$header->addColumn(2)->addClass('col-md-1')->add('H4')->set('Rest');

		foreach ($employee as $emp) {
			$col = $form->add('Columns')->addClass('row');
			$c0 = $col->addColumn(3)->addClass('col-md-3');
			$cnull = $col->addColumn(1)->addClass('col-md-1');
			$c1 = $col->addColumn(2)->addClass('col-md-2');
			$c2 = $col->addColumn(2)->addClass('col-md-2');
			$c3 = $col->addColumn(2)->addClass('col-md-2');
			$c4 = $col->addColumn(2)->addClass('col-md-1');

			$name_field = $c0->addField('line','name_'.$emp->id)->set($emp['name'])->setAttr('disabled','disabled');
			$shift1_field = $c1->addField('checkbox','shift1_'.$emp->id,'Shift 1')->setAttr(['class'=>'employee-shift shift'.$emp->id, 'data-name'=> 'shift'.$emp->id]);
			$shift2_field = $c2->addField('checkbox','shift2_'.$emp->id,'Shift 2')->setAttr(['class'=>'employee-shift shift'.$emp->id, 'data-name'=> 'shift'.$emp->id]);
			$shift3_field = $c3->addField('checkbox','shift3_'.$emp->id,'Shift 3')->setAttr(['class'=>'employee-shift shift'.$emp->id, 'data-name'=> 'shift'.$emp->id]);
			$rest_field = $c4->addField('checkbox','rest_'.$emp->id,'Rest')->setAttr(['id'=>'shift'.$emp->id,'data-id'=>$emp->id, 'class'=>'employee-rest-checkbox']);
		}

		$form->addSubmit('Take Attendance')->addClass('btn btn-info');		
		
		if($form->isSubmitted()){											
			foreach ($employee as $emp) {								
				$attandance_m = $this->add('xepan\hr\Model_Employee_Attandance');
				$attandance_m->addCondition('employee_id',$emp->id);
				$attandance_m->addCondition('created_date',$this->app->today);
				$attandance_m->tryLoadAny();

				if($form['shift1_'.$emp->id])	
					$attandance_m['shift1'] = $form['shift1_'.$emp->id];
				
				if($form['shift2_'.$emp->id])	
					$attandance_m['shift2'] = $form['shift2_'.$emp->id];

				if($form['shift3_'.$emp->id])	
					$attandance_m['shift3'] = $form['shift3_'.$emp->id];
				
				if($form['rest_'.$emp->id])	
					$attandance_m['rest'] = $form['rest_'.$emp->id];
				
				$attandance_m->save();
			}

			$form->js(null,$form->js()->univ()->successMessage('Attandance Updated'))->reload()->execute();	
		}
	}
}

// <script type="text/javascript">
// 	$(document).ready(function(){
// 		$(".employee-rest-checkbox").click(function(){
// 			var unchk = '.shift' + $(this).attr("data-id");
// 			$(unchk).attr("checked",false);
// 		});
// 		$(".employee-shift").click(function(){
// 			var id = $(this).attr("data-name");
// 			$("#"+id).attr("checked",false);
// 		});		
// 	});
// </script> 