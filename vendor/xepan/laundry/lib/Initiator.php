<?php

namespace xepan\laundry;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_laundry';

    function setup_admin(){
        $this->addAppFunctions();
        $this->routePages('xepan_laundry');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('../vendor/xepan/laundry/');

        if($this->app->auth->isLoggedIn()){
            if($_GET['keep_alive_signal']){
                echo "// keep-alive";
                $this->app->js()->execute();
            }
            $this->app->js(true)->univ()->setInterval($this->app->js()->univ()->ajaxec($this->api->url('.',['keep_alive_signal'=>true]))->_enclose(),120000);

            if(!$this->app->getConfig('hidden_xepan_laundry',false)){
                $m = $this->app->top_menu->addMenu('Laundry Management');
                $m->addItem(['Register','icon'=>' fa fa-file-excel-o'],$this->app->url('xepan_laundry_register'));                
                $m->addItem(['Report','icon'=>'fa fa-dashboard'],$this->app->url('xepan_laundry_report'));                
                // $m->addItem(['Configuration','icon'=>'fa fa-cog'],$this->app->url('xepan_laundry_configuration'));                
            }
        }
            
        return $this;
    }

    function setup_frontend(){
        $this->routePages('xepan_laundry');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('./vendor/xepan/laundry/');
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
