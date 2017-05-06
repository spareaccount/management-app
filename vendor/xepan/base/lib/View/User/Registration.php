<?php
namespace xepan\base;
class View_User_Registration extends \View{
	public $options = [];

	function init(){
		parent::init();
			$f=$this->add('Form',null,null,['form/empty']);
			$f->setLayout('view/tool/userpanel/form/registration');
			$f->addField('line','first_name');
			$f->addField('line','last_name');
			$f->addField('line','username','email_id')->validate('required|email');
			$f->addField('password','password')->validate('required');
			$f->addField('password','retype_password');
			$f->addField('checkbox','tnc','');

			if($this->options['show_tnc'] == false){				
				$f->layout->template->tryDel('tnc_wrapper');
			}else{
				$f->layout->template->trySet('tnc_page_url',$this->options['tnc_page_url']);
			}

			$f->onSubmit(function($f){
				if(!$f['tnc']){
					$f->js()->univ()->alert('Accept TnC')->execute();
				}

				if($f['password']!= $f['retype_password']){
					$f->displayError($f->getElement('retype_password'),'Password did not match');			
				}
				if( ! filter_var(trim($f['username']), FILTER_VALIDATE_EMAIL))
					$f->displayError($f->getElement('username'),'not a valid email address');			
				
				$user=$this->add('xepan\base\Model_User');
				$this->add('BasicAuth')
				->usePasswordEncryption('md5')
				->addEncryptionHook($user);

				$user['epan_id']=$this->app->epan->id;
				$user['username']=$f['username'];
				$user['password']=$f['password'];
				
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

				// $frontend_config = $this->app->epan->config;
				$reg_type = $frontend_config_m['user_registration_type'];
				if($reg_type =='default_activated'){
					$user['status'] = 'Active';
					$user->save();
				}elseif($reg_type =='admin_activated'){
					$user['status'] = 'InActive';
					$user->save();
				
				}else{

					$user['status'] = 'InActive';
					$user['hash']=rand(9999,100000);
					$user->save();
					$contact=$user->ref('Contacts')->tryLoadAny();
					$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
					$mail = $this->add('xepan\communication\Model_Communication_Email');

					$merge_model_array=[];
					$merge_model_array = array_merge($merge_model_array,$user->get());
					$merge_model_array = array_merge($merge_model_array,$contact->get());

					// $reg_model=$this->app->epan->config;
					// $email_subject=$reg_model->getConfig('REGISTRATION_SUBJECT');
					// $email_body=$reg_model->getConfig('REGISTRATION_BODY');
					// $email_body=str_replace("{{name}}",$employee['name'],$email_body);
					$email_subject = $frontend_config_m['registration_subject'];
					$email_body = $frontend_config_m['registration_body'];
					$temp=$this->add('GiTemplate');
					$temp->loadTemplateFromString($email_body);
					$url=$this->api->url(null,
												[
												'secret_code'=>$user['hash'],
												'activate_email'=>$f['username'],
												'layout'=>'verify_account',
												]
										)->useAbsoluteURL();

					$tag_url="<a href=\"".$url."\">Click Here </a>"	;

					$subject_temp=$this->add('GiTemplate');
					$subject_temp->loadTemplateFromString($email_subject);
					$subject_v=$this->add('View',null,null,$subject_temp);
					$subject_v->template->trySet($merge_model_array);

					$body_v=$this->add('View',null,null,$temp);
					$body_v->template->trySet($merge_model_array);					
					$t=$body_v->template->trySetHTML('click_here',$tag_url);		
					$t=$body_v->template->trySetHTML('url',$url);		
					$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
					$mail->addTo($f['username']);
					$mail->setSubject($subject_v->getHtml());
					$mail->setBody($body_v->getHtml());
					$mail->send($email_settings);						
				}
				
				$this->app->hook('userCreated',[$f['first_name'],$f['last_name'],$user]);
			
			return $f->js(null,$f->js()->redirect($this->app->url('login',['layout'=>'login_view','message'=>$this->options['registration_message']])))->univ()->successMessage('Account Verification Mail Sent');
			});
	}
}