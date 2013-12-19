<?php

require_once( "database.php" );
require_once( "phpFlickr/phpFlickr.php" );
require_once( "config.php");

class Flickr
{
	public $phpFlickr;
	public $Database;
	public $cacheDir;
	public $flickrSizes;
	public $cropSizes;
	
	public function Flickr()
	{
		global $config;
		
		$this->phpFlickr = new phpFlickr( $config['api_key'] );
		$this->Database = new Database( $config['dbHost'], $config['dbUsername'], $config['dbPassword'], $config['dbName'] );
		$this->cacheDir = $config['cacheDir'];
		$this->flickrSizes = $config['flickrSizes'];
		$this->cropSizes = $config['cropSizes'];
	}	
}
?>	