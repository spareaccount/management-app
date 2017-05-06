<?php
namespace xepan\base;
class View_User_MicroLogin extends \View{
	function init(){
		parent::init();
		

		if($this->app->auth->isLoggedIn()){
			$customer_id = $this->app->auth->model['id'];
			$customer = $this->add('xepan/commerce/Model_Customer')->addCondition('user_id',$customer_id);
			$customer->tryLoadAny();
									
			$this->template->tryDel('login_wrapper');
			$this->template->trySet('logout_url',$this->app->url($this->options['logout_page']));
			$this->template->trySet('name',$customer['first_name']);
			$this->setModel($this->app->auth->model);
		}
		else{
			$this->template->tryDel('logout_wrapper');
			$this->template->trySet('login_url',$this->app->url($this->options['login_page']));
		}


	}

	function defaultTemplate(){		
		return ['view/tool/userpanel/form/micrologin'];
	}
}			