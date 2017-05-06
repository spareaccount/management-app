<?php

namespace xepan\hr;

class page_employeedetail extends \xepan\base\Page {
	public $title='Employee Details';
	public $employee;
	public $breadcrumb=['Home'=>'index','Employee'=>'xepan_hr_employee','Detail'=>'#'];

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';

		$this->employee = $employee= $this->add('xepan\hr\Model_Employee')->tryLoadBy('id',$this->api->stickyGET('contact_id'));

		if($action=="add"){
			$this->template->tryDel('details');

			$base_validator = $this->add('xepan\base\Controller_Validator');

			$form = $this->add('Form',['validator'=>$base_validator],'contact_view_full_width',['form/empty']);
			$form->setLayout(['page/employeeprofile-compact']);			
			$form->setModel($employee,['first_name','last_name','address','city','country_id','state_id','pin_code','organization','post_id','website','remark','department_id']);
			$form->addField('line','email_1')->validate('email');
			$form->addField('line','email_2');
			$form->addField('line','email_3');
			$form->addField('line','email_4');
			
			$dept_field = $form->getElement('department_id');
			$post_field = $form->getElement('post_id');
			$country_field =  $form->getElement('country_id');
			$state_field = $form->getElement('state_id');

			if($dept_id = $this->app->stickyGET('dept_id')){			
				$post_field->getModel()->addCondition('department_id',$dept_id);
			}
		
			if($cntry_id = $this->app->stickyGET('country_id')){			
				$state_field->getModel()->addCondition('country_id',$cntry_id);
			}

			$dept_field->js('change',$post_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$post_field->name]),'dept_id'=>$dept_field->js()->val()]));
			$country_field->js('change',$state_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$state_field->name]),'country_id'=>$country_field->js()->val()]));

			$form->addField('line','contact_no_1');
			$form->addField('line','contact_no_2');
			$form->addField('line','contact_no_3');
			$form->addField('line','contact_no_4');
			$form->addField('Checkbox','want_to_add_next_employee')->set(true);
			
			$form->addSubmit('Add');
			if($form->isSubmitted()){			
				try{
					$this->api->db->beginTransaction();
					$form->save();
					$new_employee_model = $form->getModel();

					if($form['post_id']){
						$post = $this->add('xepan\hr\Model_Post');
						$post->tryload($form['post_id']);
						$new_employee_model->save();
					}

					if($form['email_1']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_employee_model->id;
						$email['head'] = "Official";
						$email['value'] = trim($form['email_1']);
						$this->checkEmail($email->id,$form['email_1'],$new_employee_model->id,$form);
						$email->save();
					}

					if($form['email_2']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_employee_model->id;
						$email['head'] = "Official";
						$email['value'] = trim($form['email_2']);
						$this->checkEmail($email->id,$form['email_2'],$new_employee_model->id,$form);
						$email->save();
					}

					if($form['email_3']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_employee_model->id;
						$email['head'] = "Personal";
						$email['value'] = trim($form['email_3']);
						$this->checkEmail($email->id,$form['email_3'],$new_employee_model->id,$form);
						$email->save();
					}
					if($form['email_4']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_employee_model->id;
						$email['head'] = "Personal";
						$email['value'] = trim($form['email_4']);
						$this->checkEmail($email->id,$form['email_4'],$new_employee_model->id,$form);
						$email->save();
					}

					// Contact Form
					if($form['contact_no_1']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_employee_model->id;
						$phone['head'] = "Official";
						$phone['value'] = $form['contact_no_1'];
						$this->checkPhoneNo($phone->id,$form['contact_no_1'],$new_employee_model->id,$form);
						$phone->save();
					}

					if($form['contact_no_2']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_employee_model->id;
						$phone['head'] = "Official";
						$phone['value'] = $form['contact_no_2'];
						$this->checkPhoneNo($phone->id,$form['contact_no_2'],$new_employee_model->id,$form);
						$phone->save();
					}

					if($form['contact_no_3']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_employee_model->id;
						$phone['head'] = "Personal";
						$phone['value'] = $form['contact_no_3'];
						$this->checkPhoneNo($phone->id,$form['contact_no_3'],$new_employee_model->id,$form);
						$phone->save();
					}
					if($form['contact_no_4']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_employee_model->id;
						$phone['head'] = "Personal";
						$phone['value'] = $form['contact_no_4'];
						$this->checkPhoneNo($phone->id,$form['contact_no_4'],$new_employee_model->id,$form);
						$phone->save();
					}				
					$this->api->db->commit();
				}catch(\Exception_StopInit $e){

		        }catch(\Exception $e){
		            $this->api->db->rollback();
		            throw $e;
		        }	

		        if($form['want_to_add_next_employee']){
		        	$form->js(null,$form->js()->reload())->univ()->successMessage('Employee Created Successfully')->execute();
		        }
				$form->js(null,$form->js()->univ()->successMessage('Employee Created Successfully'))->univ()->redirect($this->app->url(null,['action'=>"edit",'contact_id'=>$new_employee_model->id]))->execute();
			}

		}else{
			$contact_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\hr\Model_Employee','view_document_class'=>'xepan\hr\View_Document'],'contact_view');
			$contact_view->setModel($employee);
		}
		
		if($employee->loaded()){
			$portfolio_view = $this->add('xepan\hr\View_Document',['action'=> $action],'portfolio_view',['page/employee/portfolio']);
			$portfolio_view->setIdField('contact_id');
			$portfolio_view->setModel($employee,['department','post','user','remark','salary_template'],['department_id','post_id','user_id','remark']);
			$f=$portfolio_view->form;

			if($f->isSubmitted()){
				$f->save();
				$f->js(null,$f->js()->univ()->successMessage('Updated'))->reload()->execute();				
			}

			$q = $portfolio_view->addMany('Qualification',['no_records_message'=>'No qualifications found'],'Qualification',['view/employee/qualification-grid']);
			$q->setModel($employee->ref('Qualifications'));

			$e = $portfolio_view->addMany('Experiences',['no_records_message'=>'No experience found'],'Experiences',['view/employee/experience-grid']);
			$e->setModel($employee->ref('Experiences'));

			$official_view = $this->add('xepan\hr\View_Document',['action'=> $action],'official_info',['view/employee/official-details']);
			$official_view->setIdField('contact_id');
			
			$official_view->setModel($employee,['offer_date','doj','contract_date','leaving_date'],
											   ['offer_date','doj','contract_date','leaving_date']);
			
			$document_view = $this->add('xepan\hr\View_Document',['action'=> $action],'document_view',['page/employee/emp-document']);
			$document_view->setIdField('contact_id');
			$q = $document_view->addMany('EmployeeDocument',['no_records_message'=>'No document found'],'Document',['view/employee/emp-document-grid']);
			$q->setModel($employee->ref('EmployeeDocuments'));

		}

	}

	function defaultTemplate(){
		return ['page/employee-profile'];
	}

	function checkPhoneNo($phone_id,$phone_value,$contact_id,$form){

		 $contact = $this->add('xepan\base\Model_Contact');
        
        if($contact_id)
	        $contact->load($contact_id);

		$contactconfig_m = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'contact_no_duplcation_allowed'=>'DropDown'
							],
					'config_key'=>'contact_no_duplication_allowed_settings',
					'application'=>'base'
			]);
		$contactconfig_m->tryLoadAny();	

		if($contactconfig_m['contact_no_duplcation_allowed'] != 'duplication_allowed'){
	        $contactphone_m = $this->add('xepan\base\Model_Contact_Phone');
	        $contactphone_m->addCondition('id','<>',$phone_id);
	        $contactphone_m->addCondition('value',$phone_value);
			
			if($contactconfig_m['contact_no_duplcation_allowed'] == 'no_duplication_allowed_for_same_contact_type'){
				$contactphone_m->addCondition('contact_type',$contact['contact_type']);
		        $contactphone_m->tryLoadAny();
		 	}

	        $contactphone_m->tryLoadAny();
	        
	        if($contactphone_m->loaded())
	        	for ($i=1; $i <=4 ; $i++){ 
	        		if($phone_value == $form['contact_no_'.$i])
			        	$form->displayError('contact_no_'.$i,'Contact No. Already Used');
	        	}
		}	
    }

    function checkEmail($email_id,$email_value,$contact_id,$form){

    	$contact = $this->add('xepan\base\Model_Contact');
        
        if($contact_id)
	        $contact->load($contact_id);

		$emailconfig_m = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'email_duplication_allowed'=>'DropDown'
							],
					'config_key'=>'Email_Duplication_Allowed_Settings',
					'application'=>'base'
			]);
		$emailconfig_m->tryLoadAny();

		if($emailconfig_m['email_duplication_allowed'] != 'duplication_allowed'){
	        $email_m = $this->add('xepan\base\Model_Contact_Email');
	        $email_m->addCondition('id','<>',$email_id);
	        $email_m->addCondition('value',$email_value);
			
			if($emailconfig_m['email_duplication_allowed'] == 'no_duplication_allowed_for_same_contact_type'){
				$email_m->addCondition('contact_type',$contact['contact_type']);
			}
	        
	        $email_m->tryLoadAny();
	        
	        if($email_m->loaded())
	        	for ($i=1; $i <=4 ; $i++){ 
	        		if($email_value == $form['email_'.$i])
			        	$form->displayError('email_'.$i,'Email Already Used');
	        	}
		}	
    }
}
