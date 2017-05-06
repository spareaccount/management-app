<?php
namespace xepan\base;
class View_User_VerifyAgain extends \View{
	public $options = [];
	function init(){
		parent::init();

		$form=$this->add('Form',null,null,['form/empty']);
		$form->setLayout('view/tool/userpanel/form/xepanverifyagain');
		$form->addField('line','email');

		$form->onSubmit(function($f){
			try {
				$user=$this->add('xepan\base\Model_User');
				$user->addCondition('username',$f['email']);
				$user->tryLoadAny();
				
				if(!$user->loaded()) throw $this->exception('Email Id is not registered','ValidityCheck')->setField('email');

				$contact=$user->ref('Contacts');
				$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
				$mail = $this->add('xepan\communication\Model_Communication_Email');
				$frontend_config_m = $this->add('xepan\base\Model_ConfigJsonModel',
				[
					'fields'=>[
								'user_registration_type'=>'DropDown',
								'reset_subject'=>'xepan\base\RichText',
								'reset_body'=>'xepan\base\RichText',
								'update_subject'=>'Line',
								'update_body'=>'xepan\base\RichText',
								'registration_subject'=>'Line',
								'registration_body'=>'xepan\base\RichText',
								'verification_subject'=>'Line',
								'verification_body'=>'xepan\base\RichText',
								'subscription_subject'=>'Line',
								'subscription_body'=>'xepan\base\RichText',
								],
						'config_key'=>'FRONTEND_LOGIN_RELATED_EMAIL',
						'application'=>'communication'
				]);
				$frontend_config_m->tryLoadAny();

				// $reg_model=$this->app->epan->config;
				// $email_subject=$reg_model->getConfig('REGISTRATION_SUBJECT');
				// $email_body=$reg_model->getConfig('REGISTRATION_BODY');
				$email_subject = $frontend_config_m['registration_subject'];				
				$email_body    = $frontend_config_m['registration_body'];				
				
				$merge_model_array=[];
				$merge_model_array = array_merge($merge_model_array,$user->get());
				$merge_model_array = array_merge($merge_model_array,$contact->get());	

				// $email_body=str_replace("{{name}}",$employee['name'],$email_body);
				$temp=$this->add('GiTemplate');
				$temp->loadTemplateFromString($email_body);
				$url=$this->api->url(null,
											[
											'secret_code'=>$user['hash'],
											'activate_email'=>$f['email'],
											'layout'=>'verify_account',
											]
									)->useAbsoluteURL();

				$tag_url="<a href=\"".$url."\">Click Here to Activate </a>"	;
						
				$subject_temp=$this->add('GiTemplate');
				$subject_temp->loadTemplateFromString($email_subject);
				$subject_v=$this->add('View',null,null,$subject_temp);
				$subject_v->template->trySet($merge_model_array);

				$body_v=$this->add('View',null,null,$temp);
				$body_v->template->trySet($merge_model_array);					
				$t=$body_v->template->trySetHTML('click_here',$tag_url);		
				$t=$body_v->template->trySetHTML('url',$url);	
				$t=$body_v->template->trySetHTML('otp',$user['hash']);		
				$t=$body_v->template->trySetHTML('url',$url);	

				// echo $temp->render();
				// exit;		
				$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
				$mail->addTo($f['email']);
				$mail->setSubject($subject_v->getHtml());
				$mail->setBody($body_v->getHtml());
				$mail->send($email_settings);

				return $f->js(null,$f->js()->redirect($this->app->url('login',['layout'=>'login_view', 'message'=>$this->options['reactive_message']])))->univ()->successMessage('Secret Code Sent');
			} catch (Exception $e) {
				return $this->js()->univ()->errorMessage('Error');	

			}
		});
	}			
}			