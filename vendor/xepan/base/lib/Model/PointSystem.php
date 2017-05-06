<?php

namespace xepan\base;

/**
* 
*/
class Model_PointSystem extends \xepan\base\Model_Table
{
	public $table='point_system';
	
	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Rules','rule_id');
		$this->hasOne('xepan\base\RulesOption','rule_option_id');
		$this->addField('contact_id')->defaultValue(0);
		$this->addField('score')->defaultValue(0);
		$this->addField('landing_campaign_id')->defaultValue(0);
		$this->addField('landing_content_id')->defaultValue(0);
		$this->addField('created_at')->type('datetime')->defaultValue(@$this->app->now);

		$this->addHook('afterSave',$this);
		$this->addHook('beforeDelete',$this);
	}

	function afterSave($m){
		$contact_m = $this->add('xepan\base\Model_Contact');
		$contact_m->addCondition('id',$m['contact_id']);
		$contact_m->tryLoadAny();

		if($contact_m->loaded()){
			$contact_m['score'] = $contact_m['score'] + $m['score'];
			$contact_m->save();
		}
	}

	function beforeDelete($m){
		$contact_m = $this->add('xepan\base\Model_Contact');
		$contact_m->addCondition('id',$m['contact_id']);
		$contact_m->tryLoadAny();

		if($contact_m->loaded()){
			$contact_m['score'] = $contact_m['score'] - $m['score'];
			$contact_m->save();
		}
	}
}