<?php

/**
* description: Contact serves as Base model for all models that relates to any human contact
* Let it be lead, customer, supplier or any other contact in any application.
* This contact model stores all basic possible details in this table and leave specific implementation
* for Model extending this Model by joining other tables
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\base;

class Model_Contact extends \xepan\base\Model_Table{
	public $table='contact';

	public $status=[];
	public $actions=[];

	function init(){
		parent::init();

		// $this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\base\ContactCreatedBY','created_by_id');
		$this->hasOne('xepan\base\ContactAssignedTo','assign_to_id');
		$this->hasOne('xepan\base\User',null,'username');
		$this->hasOne('xepan\base\Country','country_id')->display(array('form' => 'xepan\base\DropDown'));
		$this->hasOne('xepan\base\State','state_id')->display(array('form' => 'xepan\base\DropDown'));

		$this->addField('type');
		$this->getElement('type')->defaultValue('Contact');
		
		$this->addField('first_name');
		$this->addField('last_name');

		$this->addField('address')->type('text');
		$this->addField('city');
		$this->addField('pin_code');
		$this->addField('status')->enum($this->status)->mandatory(true)->system(true);

		$this->addField('organization');
		$this->addField('post')->caption('Post');
		$this->addField('website');
		$this->addField('code')->system(true);
		
		$this->addField('source');
		$this->addField('remark')->type('text');
		$this->addField('score')->defaultValue(0)->sortable(true);
		
		$this->addField('created_at')->type('datetime')->defaultValue(@$this->app->now);
		$this->addField('updated_at')->type('datetime')->defaultValue(@$this->app->now);

		$this->addField('search_string')->type('text')->system(true)->defaultValue(null);
		$this->addField('freelancer_type')->enum(['Public','Company','Not Applicable'])->defaultValue('Not Applicable');
		$this->addField('related_with');
		$this->addField('related_id')->type('int');

		$this->add('xepan/filestore/Field_Image',['name'=>'image_id','deref_field'=>'thumb_url'])->allowHTML(true);

		$this->addExpression('name')->set($this->dsql()->expr('CONCAT([0]," ",[1])',[$this->getElement('first_name'),$this->getElement('last_name')]))->sortable(true);

		$this->addExpression('effective_name',function($m,$q){
			return $q->expr('IF(ISNULL([organization_name]) OR trim([organization_name])="" ,[contact_name],[organization_name])',
						[
							'contact_name'=>$m->getElement('name'),
							'organization_name'=>$m->getElement('organization')
						]
					);
		});

		$this->hasMany('xepan\base\Contact_Email',null,null,'Emails');
		$this->hasMany('xepan\base\Contact_Phone',null,null,'Phones');
		$this->hasMany('xepan\base\Contact_Relation',null,null,'Relations');
		$this->hasMany('xepan\base\Contact_IM',null,null,'IMs');
		$this->hasMany('xepan\base\Contact_Event',null,null,'Events');
		$this->hasMany('xepan\base\Contact_CommunicationReadEmail','contact_id',null,'UnreadEmails');		
		
		$this->addExpression('emails_str')->set(function($m,$q){
			$x = $m->add('xepan\base\Model_Contact_Email',['table_alias'=>'emails_str']);
			return $x->addCondition('contact_id',$q->getField('id'))
						->addCondition('is_active',true)
						->addCondition('is_valid',true)
						->_dsql()->del('fields')->field($q->expr('group_concat([0] SEPARATOR "<br/>")',[$x->getElement('value')]));
		})->allowHTML(true)->sortable(true);

		$this->addExpression('unique_name',function($m,$q){
			return $q->expr("CONCAT([0],' : [',IFNULL([1],''),'] - [',[2],'] - [', IFNULL([3],''),']')",
					[
						$m->getElement('name'),
						$m->getElement('organization'),
						$m->getElement('type'),
						$m->getElement('code')
					]);
		
		});

		$this->addExpression('contacts_str')->set(function($m,$q){
			$x = $m->add('xepan\base\Model_Contact_Phone',['table_alias'=>'contacts_str']);
			return $x->addCondition('contact_id',$q->getField('id'))->_dsql()->del('fields')->field($q->expr('group_concat([0] SEPARATOR "<br/>")',[$x->getElement('value')]));
		})->allowHTML(true);

		$this->addExpression('contacts_comma_seperated')->set(function($m,$q){
			$x = $m->add('xepan\base\Model_Contact_Phone',['table_alias'=>'contacts_str']);
			return $x->addCondition('contact_id',$q->getField('id'))->_dsql()->del('fields')->field($q->expr('group_concat([0] SEPARATOR ", ")',[$x->getElement('value')]));
		})->allowHTML(true);

		$this->addExpression('online_status')->set(function($m,$q){
			return '"online"'; // or ideal or offline
		});

		$this->addExpression('scope')->set(function($m,$q){
			return $m->refSQL('user_id')->fieldQuery('scope');
		});

		$this->addHook('beforeSave',function($m){$m['updated_at']=$this->app->now;});

		$this->addHook('beforeDelete',[$this,'deleteContactEmails']);
		$this->addHook('beforeDelete',[$this,'deleteContactPhones']);
		$this->addHook('beforeDelete',[$this,'deleteContactRelations']);
		$this->addHook('beforeDelete',[$this,'deleteContactIMs']);
		$this->addHook('beforeDelete',[$this,'deleteContactEvents']);
		$this->addHook('afterSave',[$this,'updateContactCode']);
		
		$this->addHook('beforeSave',function($m){$m['updated_at'] = $m->app->now;});
		
		$this->addHook('afterSave',[$this,'contact_category_association']);

		$this->is([
				// 'epan_id|required',
				'first_name|to_trim|to_upper_words|required',
				'last_name|to_trim|to_upper_words',
				'user_id|unique_in_epan',
				'type|to_trim|required'
			]);
	}

	function contact_category_association(){		
		$this->app->hook('contact_save',[$this]);
	}

	function updateContactCode(){
		if(!$this->loaded()) throw new \Exception($this['type'] ." Model Must be Loaded", 1);
			$type = $this['type'];
			$company_info = $this->app->epan['name'];
			$owner_code = substr($company_info, 0,3);

			$code = "CON";
			switch ($type) {
					case 'Employee':
							$code = $owner_code."EMP";
						break;
					case 'Customer':
							$code = $owner_code."CUS";
						break;
					case 'Supplier':
							$code = $owner_code."SUP";
						break;
					case 'OutsourceParty':
							$code = $owner_code."OUT";
						break;
					case 'Lead':
							$code = $owner_code."LEA";
						break;
					case 'Warehouse':
							$code = $owner_code."WAR";
						break;
					case 'Affiliate':
							$code = $owner_code."AFF";
						break;
					default:
						//Code...							
						break;
			}			
			$this['code'] = $code.$this->id;
			$this->save();

	}

	function deleteContactEmails(){
		$this->ref('Emails')->deleteAll();
	}
	function deleteContactPhones(){
		$this->ref('Phones')->deleteAll();
	}
	function deleteContactRelations(){
		$this->ref('Relations')->deleteAll();
	} 
	function deleteContactIMs(){
		$this->ref('IMs')->deleteAll();
	}
	function deleteContactEvents(){
		$this->ref('Events')->deleteAll();
	}

	function deactivateContactEmails($contact_id){		
		$contact_info = $this->add('xepan\base\Model_Contact_Info');
		$contact_info->addCondition('contact_id',$contact_id);
		$contact_info->addCondition('type','Email');

		foreach ($contact_info as $info){
			$info['is_active'] = false;
			$info->save();
		}
	}

	function page_communication($page){	
		$this->app->stickyGET('comm_type');
		
		$tabs = $page->add('Tabs');
        $communication_tab = $tabs->addTab('Communication');
        $followup_tab = $tabs->addTab('Followups');

		$communication = $page->add('xepan\communication\Model_Communication');
		$communication->addCondition(
						$communication->dsql()->orExpr()
						->where('from_id',$this->id)
						->where('to_id',$this->id)
					);

		$communication->setOrder('created_at','desc');
		$contact_id = $this->id;
		
		$lister=$communication_tab->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id],null,null);
		if($_GET['comm_type']){
			$communication->addCondition('communication_type',explode(",", $_GET['comm_type']));
		}

		if($search = $this->app->stickyGET('search')){
			$communication->addExpression('Relevance')->set('MATCH(title,description,communication_type) AGAINST ("'.$search.'")');
			$communication->addCondition('Relevance','>',0);
 			$communication->setOrder('Relevance','Desc');
		}

		$lister->setModel($communication)->setOrder(['created_at desc','id desc']);
		$p = $lister->add('Paginator',null,'Paginator');
		$p->setRowsPerPage(10);
		
		$form = $lister->add('Form',null,'form');
		$form->setLayout('view\communication\filterform');
		$type_field = $form->addField('xepan\base\DropDown','communication_type');
		$type_field->setAttr(['multiple'=>'multiple']);
		$type_field->setValueList(['Email'=>'Email','Support'=>'Support','Call'=>'Call','Newsletter'=>'Newsletter','SMS'=>'SMS','Personal'=>'Personal','Comment'=>'Comment','TeleMarketing'=>'TeleMarketing']);
		$form->addField('search')->set($_GET['search']);
		$form->addSubmit('Filter')->addClass('btn btn-primary btn-block');
		
		$temp = ['Email','Support','Call','Newsletter','SMS','Personal','Comment','TeleMarketing'];
		$type_field->set($_GET['comm_type']?explode(",", $_GET['comm_type']):$temp)->js(true)->trigger('changed');
		
		if($form->isSubmitted()){			
			$lister->js()->reload(['comm_type'=>$form['communication_type'],'search'=>$form['search']])->execute();
		}
		
		$this->app->hook('communication_rendered',[$contact_id,$followup_tab]);
	}

	//load Logged In check for the user of contact loaded or not, 
	//mainly used  for online contact account
	function loadLoggedIn($type=null){
		if($this->loaded()) $this->unload();
		if(!$this->api->auth->isLoggedIn()) return false;
		
		$this->addCondition('user_id',$this->api->auth->model->id);
		if($type)
			$this->addCondition('type',$type);

		$this->tryLoadAny();
		if(!$this->loaded()) return false;
		return true;
	}

	function addEmail($email,$head='Official',$active=true,$valid=true){
		return $this->add('xepan\base\Model_Contact_Phone')
			->set('contact_id',$this->id)
			->set('head',$head)
			->set('value',$email)
			->set('is_active',$active)
			->set('is_valid',$valid)
			->save();

	}

	function getEmails($all=false){
		if(!$this->loaded())
			return [];
		$emails = $this->ref('Emails')->_dsql();
		
		if(!$all) $emails->where('is_active',true)->where('is_valid',true);

		$emails = $emails->del('fields')->field('value')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($emails)),false);
	}

	function addPhone($number,$head='Official',$active=true,$valid=true){
		return $this->add('xepan\base\Model_Contact_Phone')
			->set('contact_id',$this->id)
			->set('head',$head)
			->set('value',$number)
			->set('is_active',$active)
			->set('is_valid',$valid)
			->save();

	}

	function getPhones(){
		if(!$this->loaded())
			return [];

		$emails = $this->ref('Phones')
								->_dsql()->del('fields')->field('value')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($emails)),false);	
	}

	function user(){
		return $this->ref('user_id');
	}

	function page_manage_score($p){		
		$score_view = $p->add('View_Info')->set('Current Score : '.$this['score'])->addClass('panel panel-default panel-heading xepan-push');
		$form = $p->add('Form')->addClass('xepan-push-small');
		$form->addField('score');
		$form->addField('DropDown','do_what')->setValueList(['increase'=>'increase','decrease'=>'decrease']);
		$form->addSubmit('Update Score')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$this->manage_score($form['do_what'],$form['score']);
			return $this->app->page_action_result = $this->app->js(true,$p->js()->univ()->closeDialog())->univ()->successMessage('Done');
		}
	}

	function manage_score($do_what,$score){		
		if($score % 10 != 0)
			throw new \Exception("Score Should Be Multiple Of 10");

		$model_point_system = $this->add('xepan\base\Model_PointSystem');
		$model_point_system->addCondition('contact_id',$this->id);

		if($do_what == 'increase')
			$model_point_system['score'] += $score;
		else
			$model_point_system['score'] -= $score;
		
		$model_point_system->save();
	}
}