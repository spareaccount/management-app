<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\base;


class page_elconnector extends \Page {
	public $title='Page Title';

	function init(){
		parent::init();

		if(!$this->app->auth->isLoggedIn()) return;

		$path_asset = $this->app->pathfinder->base_location->base_path.'/websites/'.$this->app->current_website_name.'/assets';
		$path_www = $this->app->pathfinder->base_location->base_path.'/websites/'.$this->app->current_website_name.'/www';
		
		\elFinder::$netDrivers['ftp'] = 'FTP';
		\elFinder::$netDrivers['dropbox'] = 'Dropbox';
		
		$opts = array(
		    'locale' => '',
		    'roots'  => array(
		        array(
		            'driver' => 'LocalFileSystem',
		            'path'   => $path_asset,
		            'URL'    => 'websites/'.$this->app->current_website_name.'/assets'
		        ),
		        array(
		            'driver' => 'LocalFileSystem',
		            'path'   => $path_www,
		            'URL'    => 'websites/'.$this->app->current_website_name.'/www'
		        )
		    )
		);

		// run elFinder
		$connector = new \elFinderConnector(new \elFinder($opts));
		$connector->run();
		exit;
	}
}
