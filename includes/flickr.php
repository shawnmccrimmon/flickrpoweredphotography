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
	public $userID;
	public $flickrInitialized = false;
	public $databaseInitialized = false;
	public $debug = false;
	
	public function Flickr( $initializeFlickr = false, $initializeDatabase = false, $debug = false )
	{
		global $config;
		
		$this->debug = $debug;
		$this->cacheDir = $config['cacheDir'];
		$this->flickrSizes = $config['flickrSizes'];
		$this->cropSizes = $config['cropSizes'];
		$this->userID = $config['userID'];
		
		// only initialize necessary objects
		if( $initializeFlickr == true )
		{
			$this->initializeFlickr();
		}
		if( $initializeDatabase == true )
		{
			$this->initializeDatabase();
		}
	}	
	
	public function initializeFlickr()
	{
		if( $this->flickrInitialized == false )
		{
			global $config;
			
			$this->phpFlickr = new phpFlickr( $config['api_key'], $config['secret'], $this->debug );
			$this->flickrInitialized = true;
		}
	}
	
	public function initializeDatabase()
	{
		if( $this->databaseInitialized == false )
		{
			global $config;
			
			$this->Database = new Database( $config['dbHost'], $config['dbUsername'], $config['dbPassword'], $config['dbName'] );
			$this->databaseInitialized = true;
		}
	}
}
?>	