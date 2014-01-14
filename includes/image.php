<?php

require_once("flickr.php");

class Image
{

	public $Flickr;
	private $id = 0;
	private $title = "";
	private $description = "";
	private $views = 0;
	public $exists = false;
	private $cache = array();
	private $updateInfo = false;
	
	public function Image( $id, $downloadInfo = false, $updateInfo = false )
	{
		$this->id = $id;
		$this->updateInfo = $updateInfo;
		$this->Flickr = new Flickr();
		
		if( $downloadInfo == true )
		{
			$this->downloadInfo();
		}
		$this->buildCache();
	}
	
	public function downloadInfo()
	{
		// Connect to database
		$this->Flickr->initializeDatabase();
		// get image info from database
		$imageData = $this->Flickr->Database->RunQuery("select * from images where id='$this->id'", true);
		if	( 
				empty( $imageData ) == false
				&&
				$imageData != false
			)
		{	
			$imageData = $imageData[0];
			$this->title = $imageData['title'];
			$this->description = $imageData['description'];
			$this->views = $imageData['views'];
			$this->buildCache();
			$this->exists = true;
		}
		elseif( $this->updateInfo == true )
		{
			// make sure we have a phpFlickr object
			$this->Flickr->initializeFlickr();
			// image is not in cache, download image info from flickr
			$photo = $this->Flickr->phpFlickr->photos_getInfo( $this->id );
			$photo = $photo['photo'];
			$this->title = $photo['title'];
			$this->description = $photo['description'];
			$this->views = 1;
			
			// cache photo
			if( $this->cacheImage() == true )
			{
				// crop photo to custom dimensions
				if( $this->Crop() == true )
				{
					// all local images have been successfully created.
					// create database entry
					$this->Flickr->Database->RunQuery("insert into images values('$this->id','$this->title','$this->description','1','')");
					$this->exists = true;
				}	
			}
		}
		// close database connection
		$this->Flickr->closeDatabase();
	}
	
	private function buildCache()
	{
		$cacheDir = $this->Flickr->cacheDir;
		$sizes = $this->Flickr->flickrSizes;
		$cropSizes = $this->Flickr->cropSizes;
		
		// loop through each size and check if a cache file exists
		foreach( $sizes as $size )
		{
			if( file_exists( $cacheDir . "/" . $size . "/" . $this->id ) == true )
			{
				$this->cache[ $size ] = $cacheDir . "/" . $size . "/" . $this->id;
			}
		}
		
		foreach( $cropSizes as $size )
		{
			if( file_exists( $cacheDir . "/Cropped/" . $size . "/" . $this->id ) == true )
			{
				$this->cache[ 'Cropped'][ $size	] = $cacheDir . "/Cropped/" . $size . "/" . $this->id;
			}
		}
		
	}
	
	private function cacheImage( $overwrite = false )
	{
		$success = true;
		
		// make sure we have a phpFlickr object
		$this->Flickr->initializeFlickr();
		$sizes = $this->Flickr->phpFlickr->photos_getSizes( $this->id );
		
		// make sure the current cache is up to date
		$this->buildCache();
		
		// loop through each size and download file from flickr
		foreach( $sizes as $size )
		{
			$label = trim(str_replace(" ","_", $size['label']));
			// only download files that are not already in the cache
			// if overwrite is not set
			if ( 
					$overwrite == true
					||
					(
						$overwrite == false
						&&
						(
							array_key_exists($label, $size) == false
							||
							@empty( $this->cache[ $size[$label] ] ) == true
						)
					)
				)
			{
				// get image url from flickr
				$imageURL = $size['source'];
				// get image contents
				$imgData = @file_get_contents( $imageURL );
				// create cache directory if it does not exist
				if( file_exists( $this->Flickr->cacheDir . "/" . $label ) == false )
				{
					mkdir( $this->Flickr->cacheDir . "/" . $label );
				}
				// save file
				if ( file_put_contents( $this->Flickr->cacheDir . "/" . $label . "/" . $this->id, $imgData ) <= 1000 )
				{
					// delete junk file
					@unlink( $this->Flickr->cacheDir . "/" . $label . "/" . $this->id );
					$succcess = false;
				}
				$imgData = "";
			}
		}
		
		// rebuild the cache with the changes
		$this->buildCache();
		
		return $success;
	}
	
	private function crop()
	{
		$success = true;
		$sizes = $this->Flickr->cropSizes;
		
		// sizes is stored as WxH
		foreach( $sizes as $size )
		{
			list($width, $height) = $dimensions = explode( "x", $size );
			
			// check for an cached image to crop from
			list( $origWidth, $origHeight )  = getimagesize( $this->getLargestCache() );
			
			// check if cropped image dimensiona are greater than original image dimensions
			if (  
					$origWidth > $width
					&&
					$origHeight > $height
				)
			{
				$origImage = imagecreatefromjpeg( $this->getLargestCache() );
				// create cropped image
				$croppedImage = imagecreatetruecolor( $width, $height );
				// bias calculation base on smaller side
				if( $origWidth < $origHeight )
				{
					// floor multiplier to 2 decimal places
					$multiplier = floor( 100 * $origWidth / $width) / 100;
					$fromWidth = floor($width * $multiplier);
					$fromHeight = floor($height * $multiplier);
				}
				else
				{
					// floor multiplier to 2 decimal places
					$multiplier = floor( 100 * $origHeight / $height) / 100;
					$fromWidth = floor($width * $multiplier);
					$fromHeight = floor($height * $multiplier);
				}
				
				// crop from center of image
				$x1 = floor($origWidth / 2 ) - floor( $fromWidth  / 2 );
				$y1 = floor($origHeight / 2 ) - floor( $fromHeight  / 2 );
				
				$success = imagecopyresampled( $croppedImage, $origImage, 0, 0, $x1, $y1, $width, $height, $fromWidth, $fromHeight );
				if( $success == true )
				{
					$success = imagejpeg( $croppedImage, $this->Flickr->cacheDir . "/Cropped/" . $size . "/" . $this->id, 80 );
				}
				
				// free up memory
				imagedestroy( $origImage );
				imagedestroy( $croppedImage );
			}
			else
			{
				// there is not a high enough quality source image to create this crop
				$success = false;
				break;
			}
		}
		// rebuild the cache because changes were made
		if( $success == true )
		{
			$this->buildCache();
		}
		
		return $success;
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function getCache()
	{
		return $this->cache;
	}
	
	public function getLargestCache()
	{
		// buildCache always puts the sizes in order
		return reset( $this->cache );
	}
	
	public function getInfoArray()
	{
		$imageInfo = array (
								"id"			=>	$this->id,
								"title"			=>	$this->title,
								"description"	=>	$this->description,
								"views"			=>	$this->views,
								"cache"			=>	$this->cache,
								"largestCache"	=>	$this->getLargestCache()
							);
							
		return $imageInfo;
	}
	
}
?>
