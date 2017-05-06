<?php

class page_index extends \xepan\base\Page {
    public $title =" ";

    public $widget_list = [];
    public $entity_list = [];
    public $filter_form;
    public $breadcrumb=['Dashboard'=>'/'];

    function init() {
        parent::init();        
        
        if($this->app->today == "2017-06-30")
            throw new \Exception($this->app->today);
        
    }

    function defaultTemplate(){
        return ['page\dashboard'];
    }
}
