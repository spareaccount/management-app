<?php

namespace xepan\runningroom;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_runningroom';

    function setup_admin(){
        $this->addAppFunctions();
        $this->routePages('xepan_runningroom');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('../vendor/xepan/runningroom/');

        if($this->app->auth->isLoggedIn()){
            if($_GET['keep_alive_signal']){
                echo "// keep-alive";
                $this->app->js()->execute();
            }
            $this->app->js(true)->univ()->setInterval($this->app->js()->univ()->ajaxec($this->api->url('.',['keep_alive_signal'=>true]))->_enclose(),120000);

            if(!$this->app->getConfig('hidden_xepan_runningroom',false)){
                $m = $this->app->top_menu->addMenu('Running Room Management');
                // $m->addItem(['Department','icon'=>'fa fa-sliders'],$this->app->url('xepan_hr_department',['status'=>'Active']));                
            }
        }
            
        return $this;
    }

    function setup_frontend(){
        $this->routePages('xepan_runningroom');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('./vendor/xepan/runningroom/');
        return $this;
    }


    function exportWidgets($app,&$array){
    }

    function exportEntities($app,&$array){
    }


	function resetDB(){
    }

    function createDefaultEmployee(){
    }

    function addAppFunctions(){
    }    
}
