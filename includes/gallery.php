<?php

include("image.php");

class Gallery
{
	private $id;
	private $title;
	private $description;
	private $images = array();
	private $imageCount = 0;
	private $Flickr;
	public $exists = false;
	private $updateInfo = false;
	private $updateImages = false;
	private $coverImage;
	
	public function Gallery( $id = "", $title = "", $updateInfo = false, $updateImages = false )
	{
		
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
		
		$this->updateInfo = $updateInfo;
		$this->updateImages = $updateImages;
		
		// should we update the gallery infomation from flickr?
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
		
		$galleryData = $this->Flickr->Database->RunQuery("select * from galleries where id='$this->id'", true);
		
		return $this->processDataFromDatabase( $galleryData );
	}
	
	public function getInfoFromTitle( $title = "" )
	{
		if( empty( $title ) == false )
		{
			$this->title = $title;
		}
		
		$this->initializeFlickr();
		
		$galleryData = $this->Flickr->Database->RunQuery("select * from galleries where title LIKE '$this->id'", true);
		
		return $this->processDataFromDatabase( $galleryData );
	}
	
	private function processDataFromDatabase( $galleryData )
	{
		$processed = false;

		if ( 
				empty( $galleryData ) == false
				&&
				$galleryData != false
			)
		{
			$galleryData = $galleryData[0];
			$this->id = $galleryData['id'];
			$this->description = $galleryData['description'];
			$this->coverImage = $galleryData['cover'];
			$this->images = $this->imageListToArray( $galleryData['images'] );
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
				$this->Flickr->Database->RunQuery("insert into galleries values( '$this->id', '$this->title', '$this->description', '$coverImage', '$ID_List')");
			}
			else
			{
				$this->Flickr->Database->RunQuery("update galleries set title='$this->title',description='$this->description',cover='$coverImage',images='$ID_List' where id='$this->id'");
			}
			
			$this->imageListToArray( $ID_List );
		}
	}
	
	private function imageListToArray( $imageList )
	{
		// remove current image array
		$this->images = array();
		
		$imageArray = explode( ";", $imageList );
		
		foreach( $imageArray as $imageID )
		{
			$this->images[] = new Image( $imageID, true, $this->updateImages );
		}
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