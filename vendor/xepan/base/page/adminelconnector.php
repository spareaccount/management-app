<?php

namespace xepan\base;


class page_adminelconnector extends \xepan\base\Page {
	public $title='Page Title';

	function init(){
		parent::init();

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
