<?php

namespace xepan\base;

class Initiator extends \Controller_Addon {
    public $addon_name = 'xepan_base';

    function init(){
        parent::init();        
                  
        // $this->app->forget($this->app->current_website_name.'_epan');
        
        $this->addAppDateFunctions();

        if(!($this->app->epan = $this->app->recall($this->app->current_website_name.'_epan',false))){
            $this->app->epan = $this->add('xepan\base\Model_Epan')->tryLoadBy('name',$this->app->current_website_name);
            $this->app->memorize($this->app->current_website_name.'_epan', $this->app->epan);
        }
                                
        if(!$this->app->epan->loaded()){
            $this->app->forget($this->app->current_website_name.'_epan');
            die('No site found, forwarding to 404 service');
        }  

        $this->app->epan->config = $this->app->epan->ref('Configurations');
        $misc_m = $this->add('xepan\base\Model_ConfigJsonModel',
            [
                'fields'=>[
                            'time_zone'=>'DropDown'
                            ],
                    'config_key'=>'Miscellaneous_Technical_Settings',
                    'application'=>'base'
            ]);
        $misc_m->tryLoadAny();

        date_default_timezone_set($misc_m['time_zone']?:'UTC');
        $this->app->today = date('Y-m-d');
        $this->app->now   = date('Y-m-d H:i:s');
        //Todo load default location of a customer from browser or 
        $this->app->country = $this->app->recall('xepan-customer-current-country');
        $this->app->state = $this->app->recall('xepan-customer-current-state');

        $event_cont = $this->add('xepan\base\Controller_PointEventManager');
        $this->app->addHook('pointable_event',[$event_cont,'handleEvent']);
        $this->app->addHook('widget_collection',[$this,'exportWidgets']);
        $this->app->addHook('entity_collection',[$this,'exportEntities']);
    }

    function setup_admin(){
        
        $this->routePages('xepan_base');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('../vendor/xepan/base/')
        ;

        $tinymce_addon_base_path=$this->app->locatePath('addons','tinymce\tinymce');
        $this->addLocation(array('js'=>'.','css'=>'skins'))
        ->setBasePath($tinymce_addon_base_path)
        ->setBaseURL('../vendor/tinymce/tinymce/');


        $elfinder_addon_base_path=$this->app->locatePath('addons','studio-42\elfinder');
        $this->addLocation(array('js'=>'js','css'=>'css','image'=>'img'))
        ->setBasePath($elfinder_addon_base_path)
        ->setBaseURL('../vendor/studio-42/elfinder/');

        $auth = $this->app->add('BasicAuth',['login_layout_class'=>'xepan\base\Layout_Login']);
        $auth->allowPage(['xepan_base_forgotpassword','xepan_base_resetpassword','xepan_base_registration']);
        if(in_array($this->app->page, $auth->getAllowedPages())){
            $this->app->layout->destroy();
            $this->app->add('xepan\base\Layout_Centered');
            $this->app->top_menu = new \Dummy;
            $this->app->side_menu = new \Dummy;
            $this->app->user_menu = new \Dummy;
        }else{
            $this->app->top_menu = $this->app->layout->add('xepan\base\Menu_TopBar',null,'Main_Menu');
            $this->app->side_menu = $this->app->layout->add('xepan\base\Menu_SideBar',null,'Side_Menu');
            $m = $this->app->layout->add('xepan\base\Menu_TopRightBar',null,'User_Menu');
            $this->app->user_menu = $m->addMenu('My Menu');
               
        }
        $auth->addHook('createForm',function($a,$p){
            $this->app->loggingin=true;            
            $f = $p->add('Form',null,null,['form/minimal']);
            $f->setLayout(['layout/xepanlogin','form_layout']);
            $f->addField('Line','username','Email address');
            $f->addField('Password','password','Password');
            // $cc = $f->add('Columns');
            // $cc->addColumn()->add('Button')->set('Log in')->addClass('atk-size-milli atk-swatch-green');
            // $cc->addColumn()->addClass('atk-align-right')->addField('Checkbox','remember_me','Remember me');
            $this->breakHook($f);

        });
        
        $auth->addHook('loggedIn',function($auth,$user,$pass){            
            $this->app->memorize('user_loggedin', $auth->model);
            $auth->model['last_login_date'] = $this->app->now;
            $auth->model->save();
        });

        $auth->add('xepan\base\Controller_Cookie');

        $this->api->addHook('post-init',function($app){
            if(!$this->app->getConfig('developer_mode',false) && !isset($this->app->loggingin) && !$app->page_object instanceof \xepan\base\Page && !in_array($app->page, $app->auth->getAllowedPages())){
                throw $this->exception('Admin Page Must extend \'xepan\base\Page\'')
                            ->addMoreInfo('page',$app->page)
                            ->addMoreInfo('page_object_class',get_class($app->page_object))
                            ;
            }
        });

        $user = $this->add('xepan\base\Model_User_Active');
        // $user->addCondition('epan_id',$this->app->epan->id);
        $user->addCondition('scope',['AdminUser','SuperUser']);

        $auth->usePasswordEncryption('md5');
        $auth->setModel($user,'username','password');
        
        $auth->check();
               
        if(!$this->app->isAjaxOutput()) {
            $this->app->jui->addStaticInclude('pace.min');
            $this->app->jui->addStaticInclude('elfinder.full');
            $this->app->jui->addStaticStyleSheet('elfinder.full');
            $this->app->jui->addStaticStyleSheet('elfindertheme');
            $this->app->jui->addStaticStyleSheet('elfindertheme');
            $this->app->jui->addStaticInclude('pnotify.custom.min');
            $this->app->jui->addStaticInclude('xepan.pnotify');
            $this->app->jui->addStaticStyleSheet('pnotify.custom.min');
            $this->app->jui->addStaticStyleSheet('animate');
            $this->app->jui->addStaticInclude('xepan_jui');
            
            $this->app->js(true,'PNotify.prototype.options.styling = "fontawesome"');
            $this->app->js(true)->_library('PNotify.desktop')->permission();
            $this->app->js(true)->_load('jquery.bootstrap-responsive-tabs.min')->_selector('.responsive-tabs')->responsiveTabs("accordionOn: ['xs', 'sm']");
        }
       
        $this->app->addHook('post-init',function($app){
            if($app->layout->template->hasTag('quick_search_form'))
                $app->layout->add('xepan\base\View_QuickSearch',null,'quick_search_form');
        });

        // Adding all other installed applications
        $this->setup_xepan_apps('admin');
        // throw new \Exception($this->app->employee->id, 1);

        return $this;
	} 


    function setup_frontend(){
        $this->routePages('xepan_base');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('./vendor/xepan/base/')
        ;
        
    

        $auth = $this->app->add('BasicAuth',['login_layout_class'=>'xepan\base\Layout_Login']);
        $auth->usePasswordEncryption('md5');

        $user = $this->add('xepan\base\Model_User_Active');
        $user->addCondition('scope',['WebsiteUser','SuperUser','AdminUser']);
        $auth->setModel($user,'username','password');
        if(strpos($this->app->page,'_admin_')!==false){
            $user->addCondition('scope',['SuperUser','AdminUser']);
            $auth->setModel($user,'username','password');
            $auth->check();
        }


        $this->app->addMethod('exportFrontEndTool',function($app,$tool, $group='Basic'){
            if(!isset($app->fronend_tool)) $app->fronend_tool=[];
            $app->fronend_tool[$group][] = $tool;
        });

        $this->app->addMethod('getFrontEndTools',function($app){
            if(!isset($app->fronend_tool)) $app->fronend_tool=[];
            return $app->fronend_tool;
        });

        // $this->app->jui->addStaticStyleSheet('xepan-base');

        if($_SERVER['SERVER_ADDR']){
            
            if(!$this->app->recall('xepan-customer-current-country',false)){
                $ip=str_replace('.',"", $_SERVER['SERVER_ADDR']);
                $s=$this->app->db->dsql()
                                ->table('ip2location-lite-db11')
                                // ->where('ip_from','<=','16777216')
                                // ->where('ip_to','>=','16777471')
                                ->where('ip_from','<=',$ip)
                                ->where('ip_to','>=',$ip)
                                ->del('fields')->get();
                // throw new \Exception(var_dump($s), 1);
                // exit;
                $ip_country= $this->add('xepan\base\Model_Country')->tryLoadBy('name',$s['country']); 
                $ip_state = $this->add('xepan\base\Model_State')->tryLoadBy('name',$s['state']);
                $this->app->memorize('xepan-customer-current-country',$ip_country);
                $this->app->memorize('xepan-customer-current-state',$ip_state);
            }
            $this->app->country=$this->app->recall('xepan-customer-current-country');
            $this->app->state=$this->app->recall('xepan-customer-current-state');
        }
        
        // Adding all other installed applications
        $this->setup_xepan_apps('frontend');

        return $this;
    }


    function setup_xepan_apps($side){
        
         foreach ($this->add('xepan\base\Model_Epan_InstalledApplication')->setOrder('application_id') as $apps) {
            $this->app->xepan_addons[] = $apps['application_namespace'];   
        }

        foreach ($this->app->xepan_addons as $addon) {
            if($addon == 'xepan\base') continue;
            $this->app->xepan_app_initiators[$addon] = $app_initiators[$addon] = $this->add("$addon\Initiator");
        }

        // Pre setup call

        foreach ($this->app->xepan_app_initiators as $addon_name=>$addon_obj) {
            if($addon == 'xepan\base') continue;
            $func = 'setup_pre_'.$side;
            if($addon_obj->hasMethod($func)){
                $addon_obj->$func();
            }
        }

        // Setup call
        foreach ($this->app->xepan_app_initiators as $addon_name=>$addon_obj) {
            if($addon == 'xepan\base') continue;
            $func = 'setup_'.$side;
            $addon_obj->$func();
        }

        // Post Setup Call

        foreach ($this->app->xepan_app_initiators as $addon_name=>$addon_obj) {
            if($addon == 'xepan\base') continue;
            $func = 'setup_post_'.$side;
            if($addon_obj->hasMethod($func)){
                $addon_obj->$func();
            }
        }
    }

    function exportWidgets($app,&$array){
        $array[] = ['xepan\base\Widget','level'=>'Global','title'=>'Basic Widget'];
        $array[] = ['xepan\base\Widget_MyActivity','level'=>'Individual','title'=>'Personal Activities'];
        $array[] = ['xepan\base\Widget_GlobalActivity','level'=>'Global','title'=>'Companies Activities'];
        $array[] = ['xepan\base\Widget_SubordinateActivity','level'=>'Sibling','title'=>'Subordinates Activities'];
        $array[] = ['xepan\base\Widget_RecentContacts','level'=>'Global','title'=>'Recent Contacts'];
        $array[] = ['xepan\base\Widget_EpanValidity','level'=>'Global','title'=>'Epan Information'];
        $array[] = ['xepan\base\Widget_EmployeeSpecificActivities','level'=>'Global','title'=>'Employee Activities'];
        $array[] = ['xepan\base\Widget_EmployeeContacts','level'=>'Global','title'=>'Contacts Added'];
    }

    function exportEntities($app,&$array){
        $array['date_range'] = ['caption'=>'Date Range', 'type'=>'DateRangePicker'];
        $array['contact'] = ['caption'=>'Contact','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_Contact'];
        $array['User'] = ['caption'=>'User','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_User'];
        $array['Country'] = ['caption'=>'Country','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_Country'];
        $array['Miscellaneous_Technical_Settings'] = ['caption'=>'Miscellaneous_Technical_Settings','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_Miscellaneous_Technical_Settings'];
        $array['Email_Duplication_Allowed_Settings'] = ['caption'=>'Email_Duplication_Allowed_Settings','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_Email_Duplication_Allowed_Settings'];
        $array['contact_no_duplication_allowed_settings'] = ['caption'=>'contact_no_duplication_allowed_settings','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_contact_no_duplication_allowed_settings'];
        $array['GraphicalReport'] = ['caption'=>'GraphicalReport','type'=>'xepan\base\Basic','model'=>'xepan\base\Model_GraphicalReport'];
        $array['report_type'] = ['caption'=>'Type','type'=>'DropDown'];

    }

    function resetDB($write_sql=false,$install_apps=true){
        // $this->app->old_epan = clone $this->app->epan;

        // Clear DB
        $truncate_models = ['Epan_Category','Epan','User','Epan_Configuration','Epan_InstalledApplication','Application','Country','State'];
        foreach ($truncate_models as $t) {
            $this->add('xepan\base\Model_'.$t)->deleteAll();
        }

        // orphan contact_info and contacts
        
        $this->app->db->dsql()->table('contact_info')->where('epan_id',null)->delete();
        $this->app->db->dsql()->table('contact')->where('epan_id',null)->delete();
        
        $d = $this->app->db->dsql();
        $d->sql_templates['delete'] = "delete [table] from  [table] [join] [where]";
        $d->table('contact_info')->where('contact.id is null')->join('contact',null,'left')->delete();

        // orphan document_attachements
        $d = $this->app->db->dsql();
        $d->sql_templates['delete'] = "delete [table] from  [table] [join] [where]";
        $d->table('attachment')->where('document.id is null')->join('document',null,'left')->delete();

        // Create default Epan_Category and Epan

        $epan_category = $this->add('xepan\base\Model_Epan_Category')
            ->set('name','default')
            ->save();

        $epan = $this->add('xepan\base\Model_Epan')
                    ->set('epan_category_id',$epan_category->id)
                    ->set('name','www')
                    ->save();

        $this->app->epan = $epan;
        $this->app->epan->config = $this->app->epan->ref('Configurations');
        // $this->app->new_epan = clone $this->app->epan;

        // Create Default User
        $user = $this->add('xepan\base\Model_User_SuperUser');
        $this->app->auth->addEncryptionHook($user);
        $user=$user->set('username','admin@epan.in')
             ->set('scope','SuperUser')
             ->set('password','admin')
             ->set('epan_id',$epan->id)
             ->saveAs('xepan\base\Model_User_Active');

        // $this->app->auth->login($user);

        // Create Default Applications and INstall with all with root application
        
        $addons = ['xepan\\communication', 'xepan\\hr','xepan\\projects','xepan\\marketing','xepan\\accounts','xepan\\commerce','xepan\\production','xepan\\crm','xepan\\cms','xepan\\blog','xepan\\epanservices'];

        foreach ($addons as $ad) {
            $ad_array = explode("\\", $ad);
            $app = $this->add('xepan\base\Model_Application')
                ->set('name',array_pop($ad_array))
                ->set('namespace',$ad)
                ->save();
            if($install_apps)
                $epan->installApp($app);
        }

        // create default filestore volume
        
        $fv = $this->add('xepan\filestore\Model_Volume');
        $fv->addCondition('name','upload');
        $fv->tryLoadAny();
        $fv['dirname']='upload';
        $fv['total_space'] = '1000000000';
        $fv['used_space'] = '0';
        $fv['stored_files_count'] = '0';
        $fv['enabled'] = '1';
        $fv->save();


        // if($write_sql){
        //     $dump = new \MySQLDump(new \mysqli('localhost', 'root', 'winserver', 'xepan2'));
        //     $dump->save(getcwd().'/../vendor/'.str_replace("\\",'/',__NAMESPACE__).'/install.sql');
        // }

        // Insert default country and states
        $this->api->db->dsql()->expr(file_get_contents(realpath(getcwd().'/vendor/xepan/base/countriesstates.sql')))->execute();
        
        //Set Epan config 
        $config_m = $this->add('xepan\base\Model_ConfigJsonModel',
        [
            'fields'=>[
                        'reset_subject'=>'Line',
                        'reset_body'=>'xepan\base\RichText',
                        'update_subject'=>'Line',
                        'update_body'=>'xepan\base\RichText',
                        ],
                'config_key'=>'ADMIN_LOGIN_RELATED_EMAIL',
                'application'=>'communication'
        ]);
        $config_m->tryLoadAny();

        // $admin_config = $this->app->epan->config;
        $file_reset_subject_admin = file_get_contents(realpath(getcwd().'/vendor/xepan/base/templates/default/reset_subject_admin.html'));
        $file_reset_body_admin = file_get_contents(realpath(getcwd().'/vendor/xepan/base/templates/default/reset_body_admin.html'));
        
        $config_m['reset_subject'] = $file_reset_subject_admin;
        $config_m['reset_body'] = $file_reset_body_admin;
        // $config_m->save();
        // $admin_config->setConfig('RESET_PASSWORD_SUBJECT_FOR_ADMIN',$file_reset_subject_admin,'communication');
        // $admin_config->setConfig('RESET_PASSWORD_BODY_FOR_ADMIN',$file_reset_body_admin,'communication');

        $file_update_subject_admin = file_get_contents(realpath(getcwd().'/vendor/xepan/base/templates/default/update_subject_admin.html'));
        $file_update_body_admin = file_get_contents(realpath(getcwd().'/vendor/xepan/base/templates/default/update_body_admin.html'));
        $config_m['update_subject'] = $file_update_subject_admin;
        $config_m['update_body'] = $file_update_body_admin;
        $config_m->save();
        // $admin_config->setConfig('UPDATE_PASSWORD_SUBJECT_FOR_ADMIN',$file_update_subject_admin,'communication');
        // $admin_config->setConfig('UPDATE_PASSWORD_BODY_FOR_ADMIN',$file_update_body_admin,'communication');
      
        // Do other tasks needed
        // Like empting any folder etc
    }


    function extract_domain($domain)
    {
        if(preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches))
        {
            return $matches['domain'];
        } else {
            return $domain;
        }
    }

    function extract_subdomains($domain)
    {
        $subdomains = $domain;
        $domain = $this->extract_domain($subdomains);
        $subdomains = rtrim(strstr($subdomains, $domain, true), '.');

        return $subdomains;
    }

    // function epanDashboard($app,$page){
    //     $extra_info = $this->app->recall('epan_extra_info_array',false);
    //     $valid_till = $extra_info['valid_till'];

    //     $post = $this->add('xepan\hr\Model_Post');
    //     $post->tryLoadBy('id',$this->app->employee['post_id']);
        
    //     if(!$post->loaded())
    //         return;    
        
    //     if($valid_till AND ($post['parent_post_id'] == null OR $post['parent_post_id'] == $post['id'])){
    //         $expiry_view = $page->add('xepan\base\View_Widget_SingleInfo',null,'top_bar');
    //         $expiry_view->setIcon('fa fa-clock-o')
    //                 ->setHeading('Expiring At')
    //                 ->setValue(date('d M\'y',strtotime($valid_till)))
    //                 ->makeDanger()
    //                 ->addClass('col-md-4')
    //                 ;                
    //         $expiry_view->template->trySet('expiry_date',$valid_till);
    //     }

    //     $descendants = $this->app->employee->ref('post_id')->descendantPosts();
    //     $from_date = $_GET['from_date']?:$this->app->today;
    //     $to_date = $_GET['to_date']?:$this->app->today;

    //     $activity_view = $page->add('xepan\base\View_Activity',['activity_on_dashboard'=>true,'paginator_count'=>10,'from_date'=>$from_date,'to_date'=>$to_date,'descendants'=>$descendants,'grid_title'=>'Activities']);
    //     $activity_view->addClass('col-md-4');
        
    //     $page->js(true)->univ()->setInterval($activity_view->js()->reload()->_enclose(),200000);
        
    //     $contact_model = $page->add('xepan\base\Model_Contact');
    //     $contact_model->setOrder('created_at','desc');

    //     $contact_grid = $page->add('xepan\hr\CRUD',['allow_add' =>false],null,['view\contact-grid']);
    //     $contact_grid->addClass('col-md-4');
    //     $contact_grid->setModel($contact_model,['name','type','created_by']);
    //     $contact_grid->grid->addPaginator(10);
    //     $contact_grid->grid->template->trySet('grid_title','Recent Contacts');
    // }

    function addAppdateFunctions(){
        $this->app->addMethod('nextDate',function($app,$date=null){
            
            if(!$date) $date = $this->api->today;
            $date = date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " +1 DAY"));    
            return $date;
        });

        $this->app->addMethod('setDate',function($app,$date){
            $this->api->memorize('current_date',$date);
            $this->now = date('Y-m-d H:i:s',strtotime($date));
            $this->today = date('Y-m-d',strtotime($date));
        
        });

        $this->app->addMethod('previousDate',function($app,$date=null){
            if(!$date) $date = $this->api->today;
            $date = date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " -1 DAY"));    
            return $date;
        });

        $this->app->addMethod('monthFirstDate',function($app,$date=null){
            if(!$date) $date = $this->api->now;
            return date('Y-m-01',strtotime($date));
        });

        $this->app->addMethod('monthLastDate',function($app,$date=null){
            if(!$date) $date = $this->api->now;
            return date('Y-m-t',strtotime($date));
        });

        $this->app->addMethod('isMonthLastDate',function($app,$date=null){
            if(!$date) $date = $this->api->now;
            $date = date('Y-m-d',strtotime($date));
            return strtotime($date) == strtotime($this->monthLastDate());

        });

        $this->app->addMethod('nextMonth',function($app,$date=null){
            if(!$date) $date=$this->api->today;
            return date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " +1 MONTH"));
        });

        $this->app->addMethod('previousMonth',function($app,$date=null){
            if(!$date) $date=$this->api->today;
            return date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " -1 MONTH"));
        });

        $this->app->addMethod('nextYear',function($app,$date=null){
            if(!$date) $date=$this->api->today;
            return date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " +1 YEAR"));
        });

        $this->app->addMethod('previousYear',function($app,$date=null){
            if(!$date) $date=$this->api->today;
            return date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " -1 YEAR"));
        });

        $this->app->addMethod('getFinancialYear',function($app,$date=null,$start_end = 'both'){
            if(!$date) $date = $this->api->now;
            $month = date('m',strtotime($date));
            $year = date('Y',strtotime($date));
            if($month >=1 AND $month <=3  ){
                $f_year_start = $year-1;
                $f_year_end = $year;
            }
            else{
                $f_year_start = $year;
                $f_year_end = $year+1;
            }

            if(strpos($start_end, 'start') !==false){
                return $f_year_start.'-04-01';
            }
            if(strpos($start_end, 'end') !==false){
                return $f_year_end.'-03-31';
            }

            return array(
                    'start_date'=>$f_year_start.'-04-01',
                    'end_date'=>$f_year_end.'-03-31'
                );

            });

    $this->app->addMethod('getFinancialQuarter',function ($date=null,$start_end = 'both'){
        if(!$date) $date = $this->api->today;

        $month = date('m',strtotime($date));
        $year = date('Y',strtotime($date));
        
        switch ($month) {
            case 1:
            case 2:
            case 3:
                $q_month_start='-01-01';
                $q_month_end='-03-31';
                break;
            case 4:
            case 5:
            case 6:
                $q_month_start='-04-01';
                $q_month_end='-06-30';
                break;
            case 7:
            case 8:
            case 9:
                $q_month_start='-07-01';
                $q_month_end='-09-30';
                break;
            case 10:
            case 11:
            case 12:
                $q_month_start='-10-01';
                $q_month_end='-12-31';
                break;
        }

        
        if(strpos($start_end, 'start') !== false){
            return $year.$q_month_start;
        }
        if(strpos($start_end, 'end') !== false){
            return $year.$q_month_end;
        }

        return array(
                'start_date'=>$year.$q_month_start,
                'end_date'=>$year.$q_month_end
            );

        });

        $this->app->addMethod('my_date_diff',function($app,$d1,$d2){
            $d1 = (is_string($d1) ? strtotime($d1) : $d1);
            $d2 = (is_string($d2) ? strtotime($d2) : $d2);

            $diff_secs = abs($d1 - $d2);
            $base_year = min(date("Y", $d1), date("Y", $d2));

            $diff = mktime(0, 0, $diff_secs, 1, 1, $base_year);
            return [
                    "years" => date("Y", $diff) - $base_year,
                    "months_total" => (date("Y", $diff) - $base_year) * 12 + date("n", $diff) - 1,
                    "months" => date("n", $diff) - 1,
                    "days_total" => floor($diff_secs / (3600 * 24)),
                    "days" => date("j", $diff) - 1,
                    "hours_total" => floor($diff_secs / 3600),
                    "hours" => date("G", $diff),
                    "minutes_total" => floor($diff_secs / 60),
                    "minutes" => (int) date("i", $diff),
                    "seconds_total" => $diff_secs,
                    "seconds" => (int) date("s", $diff)
                ];
        });

    }

}
