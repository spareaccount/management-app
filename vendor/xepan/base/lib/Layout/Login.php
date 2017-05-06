<?php


namespace xepan\base;

class Layout_Login extends \Layout_Basic {
	function init(){
		parent::init();
		$company_m = $this->add('xepan\base\Model_ConfigJsonModel',
				[
					'fields'=>[
								'company_name'=>"Line",
								'company_owner'=>"Line",
								'mobile_no'=>"Line",
								'company_email'=>"Line",
								'company_address'=>"Line",
								'company_pin_code'=>"Line",
								'company_description'=>"xepan\base\RichText",
								'company_logo_absolute_url'=>"Line",
								'company_twitter_url'=>"Line",
								'company_facebook_url'=>"Line",
								'company_google_url'=>"Line",
								'company_linkedin_url'=>"Line",
								],
					'config_key'=>'COMPANY_AND_OWNER_INFORMATION',
					'application'=>'communication'
				]);
		
		// $company_m->add('xepan\hr\Controller_ACL');
		$company_m->tryLoadAny();

		$this->template->trySet('company_name',$company_m['company_name']);
	}

	function defaultTemplate(){
		return ['layout/xepanlogin'];
	}
}
