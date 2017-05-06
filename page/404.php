<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

class page_404 extends \Page {
	public $title='404- No page found';

	function init(){
		parent::init();
		header('HTTP/1.0 404 Not Found');
		echo "404- Page not found";
		exit();
	}
}
