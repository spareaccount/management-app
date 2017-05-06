<?php
namespace xepan\base;

class View_User_LoginPanel extends \View{
	public $options = [];
	public $reload_object;

	function init(){
		parent::init();

        $f = $this->add('Form',null,null,['form/minimal']);
        $f->setLayout('view/tool/userpanel/form/login');
        if($message = $this->app->stickyGET('message'))
   	 		$f->layout->template->trySet('message',$message);

        if(!$this->options['show_footer']){
			$f->layout->template->del('footer_wrapper');        	
        }
        
        $f->addField('Line','username','Email address');
        $f->addField('Password','password','Password');
    	$auth=$this->app->auth;
 		
        if($f->isSubmitted()){
			if(!$credential = $this->app->auth->verifyCredentials($f['username'],$f['password'])){
				$f->displayError($f->getElement('password'),'wrong credentials');
			}
					
			$user = $this->add('xepan\base\Model_User')->load($credential);
			
			if($user['status']=='Inactive')
				$f->displayError('username','Please Activate Your Account First');
			
			$auth->login($f['username']);
			$this->app->hook('login_panel_user_loggedin',[$auth->model]);

			if($next_url = $this->app->recall('next_url'))
				$this->app->redirect($this->api->url($next_url))->execute();

			if($this->reload_object){
				$object = $this->reload_object;
				$this->js(null,$object->js()->reload())->univ()->successMessage('wait ... ')->execute();
			}
			else{
				$success_url = $this->options['login_success_url'];
				$this->app->redirect($this->app->url($success_url));
			}
        }
	}
}