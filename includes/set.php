<?php

include("image.php");

/*
	Set object Arguments
	1. the id of the set (defaults to empty string)
	2. the title of the set, only used if id is blank (defaults to empty string)
	3. whether or not the images in the set should be retreived. (defaults to true)
	4. whether or not the set information should be updated from flickr (defaults to false)
	5. whether or not the images in the set should be updated from flickr (defaults to false)	
*/

class Set
{
	private $id;
	private $title;
	private $description;
	private $images = array();
	private $imageCount = 0;
	private $Flickr;
	public $exists = false;
	private $getImageArray = true;
	private $updateInfo = false;
	private $updateImages = false;
	private $coverImage;
	
	public function Set( $id = "", $title = "", $getImageArray = true, $updateInfo = false, $updateImages = false )
	{
	
		$this->getImageArray = $getImageArray;
		$this->updateInfo = $updateInfo;
		$this->updateImages = $updateImages;
		
		// try to get data from database
		if( empty( $id ) == false )
		{
			$this->id = $id;
			$this->exists = $this->getInfoFromID();
		}
		elseif( empty( $title ) == false )
		{
			$this->title = $title;
			$this->exists = $this->getInfoFromTitle();
		}
		
		// should we update the set infomation from flickr?
		if ( 
				$updateInfo == true 
				&&
				empty( $this->id ) == false
			)
		{
			$this->getInfoFromFlickr();
		}
	}
	
	private function initializeFlickr()
	{
		if( isset( $Flickr ) == false )
		{
			$this->Flickr = new Flickr();
		}
	}
	
	public function getInfoFromID( $id = "" )
	{
		if( empty( $id ) == false )
		{
			$this->id = $id;
		}
		
		$this->initializeFlickr();
		
		$setData = $this->Flickr->Database->RunQuery("select * from sets where id='$this->id'", true);
		
		return $this->processDataFromDatabase( $setData );
	}
	
	public function getInfoFromTitle( $title = "" )
	{
		if( empty( $title ) == false )
		{
			$this->title = $title;
		}
		
		$this->initializeFlickr();
		
		$setData = $this->Flickr->Database->RunQuery("select * from sets where title LIKE '$this->id'", true);
		
		return $this->processDataFromDatabase( $setData );
	}
	
	private function processDataFromDatabase( $setData )
	{
		$processed = false;

		if ( 
				empty( $setData ) == false
				&&
				$setData != false
			)
		{
			$setData = $setData[0];
			$this->id = $setData['id'];
			$this->description = $setData['description'];
			$this->coverImage = $setData['cover'];
			$this->images = $this->imageListToArray( $setData['images'] );
			$processed = true;
		}
		
		return $processed;
	}
	
	private function getInfoFromFlickr()
	{
		// get set information
		$setInfo = $this->Flickr->phpFlickr->photosets_getInfo( $this->id );
		$this->title = $setInfo['title'];
		$this->description = $setInfo['description'];
		$coverImage = $setInfo['primary'];
		$this->imageCount = $setInfo['count_photos'];
		$this->coverImage = new Image( $coverImage, true, true );
		
		if( $this->imageCount > 0 ) 
		{
			// get photos in set
			$setPhotos = $this->Flickr->phpFlickr->photosets_getPhotos( $this->id );
			
			$this->images = array();
			$ID_List = "";
			
			foreach( $setPhotos['photoset']['photo'] as $image )
			{
				if( empty( $ID_List ) == false )
				{
					$ID_List .= ";";
				}
				$ID_List .= $image['id'];
			}
			
			// add values to database
			if( $this->exists == false )
			{
				$this->Flickr->Database->RunQuery("insert into sets values( '$this->id', '$this->title', '$this->description', '$coverImage', '$ID_List')");
			}
			else
			{
				$this->Flickr->Database->RunQuery("update sets set title='$this->title',description='$this->description',cover='$coverImage',images='$ID_List' where id='$this->id'");
			}
			
			$this->images = $this->imageListToArray( $ID_List );
		}
	}
	
	private function imageListToArray( $imageList )
	{
		$images = array();
		
		if( $this->getImageArray == true )
		{			
			$imageArray = explode( ";", $imageList );
			
			foreach( $imageArray as $imageID )
			{
				$images[] = new Image( $imageID, true, $this->updateImages );
			}
		}

		return $images;
	}
		
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function getCoverImage()
	{
		return $this->coverImage;
	}
	
	public function getImages()
	{
		return $this->images;
	}
	
	public function getImageCount()
	{
		return $this->imageCount;
	}
	
}