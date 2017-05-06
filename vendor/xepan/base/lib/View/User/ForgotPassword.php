<?php
namespace xepan\base;
class View_User_ForgotPassword extends \View{
	public $options = [];
	function init(){
		parent::init();
		$form=$this->add('Form');
		$form->setLayout('view/tool/userpanel/form/xepanforgotpassword');
		$form->addField('line','email')->validate('required');

		if($form->isSubmitted()){
			$user=$this->add('xepan\base\Model_User');
			$user->addCondition('username',$form['email']);
			$user->tryLoadAny();
			
			if(!$user->loaded()){
				$form->displayError('email','This E-Mail Id is not registered');
			}else{
				$user['hash']=rand(9999,100000);
				$user->update();
				$contact=$user->ref('Contacts')->tryLoadAny();
				$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
				$mail = $this->add('xepan\communication\Model_Communication_Email');
				// $reset_pass = $this->app->epan->config;
				// $email_subject=$reset_pass->getConfig('RESET_PASSWORD_SUBJECT');
				// $email_body=$reset_pass->getConfig('RESET_PASSWORD_BODY');
				// $email_body=str_replace("{{name}}",$employee['name'],$email_body);
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
				$email_subject = $frontend_config_m['reset_subject'];
				$email_body = $frontend_config_m['reset_body'];


				$merge_model_array=[];
				$merge_model_array = array_merge($merge_model_array,$user->get());
				$merge_model_array = array_merge($merge_model_array,$contact->get());
				
				$url=$this->api->url(null,
												[
												'secret_code'=>$user['hash'],
												'activate_email'=>$form['email'],
												'layout'=>'reset_form'
												]
												)->useAbsoluteURL();

				$tag_url="<a href=\"".$url."\">Click Here to Activate </a>"	;
			
				$subject_temp=$this->add('GiTemplate');
				$subject_temp->loadTemplateFromString($email_subject);
				$subject_v=$this->add('View',null,null,$subject_temp);
				$subject_v->template->trySet($merge_model_array);

				$temp=$this->add('GiTemplate');
				$temp->loadTemplateFromString($email_body);
				$body_v=$this->add('View',null,null,$temp);
				$body_v->template->trySet($merge_model_array);					
				$t=$body_v->template->trySetHTML('click_here',$tag_url);		
				$t=$body_v->template->trySetHTML('url',$url);	

				$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
				$mail->addTo($form['email']);
				$mail->setSubject($subject_v->getHtml());
				$mail->setBody($body_v->getHtml());
				$mail->send($email_settings);

				return $form->js(null,$form->js()->univ()->successMessage('Mail sent'))->redirect($this->app->url('login',['layout'=>'login_view', 'message'=>$this->options['forgot_message']]))->execute();
			}
		}
	}
}