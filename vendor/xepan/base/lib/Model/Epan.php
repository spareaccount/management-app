<?php

/**
* description: Epan Model is the core of a system
* Epan in xEpan can be considered as a Website Hosted in cloud
* each Epan has its own isolated entities in the xEpan platform.
* for single ERP or Website developement this is a mandotory singlaton entity
* @author : Gowrav Vishwakarma
* 
*/

namespace xepan\base;

class Model_Epan extends \xepan\base\Model_Table{
	public $table='epan';

	function init(){
		parent::init();

		$this->hasOne('xepan\base\Epan_Category');
		$this->hasOne('xepan\base\Contact','created_by_id');		
		
		$this->addField('name')->hint('Identification for your epan');
		$this->addField('type')->defaultValue('Epan')->system(true);
		$this->addField('status')->defaultValue('Trial');
		$this->addField('created_at')->type('datetime')->defaultValue(isset($this->app->now)?$this->app->now:null)->system(true);
		$this->addField('is_published')->defaultValue(null);
		$this->addField('extra_info')->type('text');
		$this->addField('aliases')->type('text');
		$this->addField('xepan_template_id');	
		$this->addField('epan_dbversion');	

		$this->hasMany('xepan\base\Epan_InstalledApplication',null,null,'InstalledApplications');
		$this->hasMany('xepan\communication\Communication_EmailSetting',null,null,'EmailSettings');
		
		$this->hasMany('xepan\base\Contact');
		$this->hasMany('xepan\base\User',null,null,'Users');
		$this->hasMany('xepan\base\Epan_Configuration',null,null,'Configurations');

		// $this->addHook('beforeDelete',[$this,'deleteAllEmailSettings']);
		// $this->addHook('beforeDelete',[$this,'deleteInstallApplications']);
		// $this->addHook('beforeDelete',[$this,'deleteContacts']);
		// $this->addHook('beforeDelete',[$this,'deleteUsers']);


		$this->is([
				'epan_category_id|required',
				'name|required|to_trim|unique',
			]);
	}

	function installApp($application){
		$installed  = $this->add('xepan\base\Model_Epan_InstalledApplication')
								->addCondition('epan_id',$this->id)
								->addCondition('application_id',$application->id)
								->tryLoadAny();
		if($installed->loaded())
			throw $this->exception('Application Already Installed','ValidityCheck')
						->setField('application_id')
						->addMoreInfo('App',$application['name']);


		$installed->saveAndUnload();

	}

	function addActivity($contact_id, $activity, $related_contact_id=null, $related_document_id=null, $details=null){
		$activity = $this->add('xepan\base\Model_Activity');
		$activity['contact_id'] = $contact_id;
		$activity['activity'] = $activity;
		$activity['related_contact_id'] = $related_contact_id;
		$activity['related_document_id'] = $related_document_id;
		$activity['details'] = $details;

		$activity->save();
		return $activity;
	}


	function isApplicationInstalled($app){
		
		$installed  = $this->add('xepan\base\Model_Epan_InstalledApplication');
		$installed->join('application','application_id')
			->addField('namespace');

		$installed
					->addCondition('epan_id',$this->id)
					->addCondition('namespace',$app)
					->tryLoadAny();
		return $installed->loaded();
	}

	// function deleteAllEmailSettings(){
		
	// 	$this->ref('EmailSettings')->deleteAll();
	// }

	// function deleteInstallApplications(){
	// 	$this->ref('InstalledApplications')->deleteAll();
	// }

	// function deleteContacts(){
	// 	$this->ref('xepan\base\Contact')->deleteAll();
	// }

	// function deleteUsers(){
	// 	$this->ref('Users')->deleteAll();
	// }
}
