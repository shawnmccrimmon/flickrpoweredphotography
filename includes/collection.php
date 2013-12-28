<?php

require("set.php");

/*
	Collection object Arguments
	1. the id of the collection (defaults to empty string)
	2. the title of the collection, only used if id is blank (defaults to empty string)
	3. whether or not the collections/sets in the collection should be retreived. (defaults to true)
	4. whether or not the collection/sets/images in the retreived collection/set should be retreived (defaults to false)
	4. whether or not the collection information should be updated from flickr (defaults to false)
	5. whether or not the images in the collections/sets should be updated from flickr (defaults to false)	
*/


class Collection
{
	private $id;
	private $title;
	private $description;
	private $coverImage;
	private $collections = array();
	private $exists = false;
	private $Flickr;
	private $getCollectionArray = true;
	private $getSubCollectionArray = false;
	private $updateInfo = false;
	private $updateImages = false;
	private $type = "";
	
	public function Collection( $id = "", $title = "", $getCollectionArray = true, $getSubCollectionArray = false, $updateInfo = false, $updateImages = false )
	{
		$this->getCollectionArray = $getCollectionArray;
		$this->getSubCollectionArray = $getSubCollectionArray;
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
		
		// should we update the collection infomation from flickr?
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
		
		$collectionData = $this->Flickr->Database->RunQuery("select * from collections where id='$this->id'", true);
		
		return $this->processDataFromDatabase( $collectionData );
	}
	
	public function getInfoFromTitle( $title = "" )
	{
		if( empty( $title ) == false )
		{
			$this->title = $title;
		}
		
		$this->initializeFlickr();
		
		$collectionData = $this->Flickr->Database->RunQuery("select * from collections where title LIKE '$this->id'", true);
		
		return $this->processDataFromDatabase( $collectionData );
	}
	
	private function processDataFromDatabase( $collectionData )
	{
		$processed = false;

		if ( 
				empty( $collectionData ) == false
				&&
				$collectionData != false
			)
		{
			$collectionData = $collectionData[0];
			$this->id = $collectionData['id'];
			$this->description = $collectionData['description'];
			$this->type = $collectionData['type'];
			$this->collections = $this->collectionListToArray( $collectionData['collections'] );
			$processed = true;
		}
		
		return $processed;
	}
	
	private function getInfoFromFlickr()
	{
		// get collection information
		$collectionInfo = $this->Flickr->phpFlickr->collections_getTree( $this->id, $this->Flickr->userID );
		
		$collectionInfo = $collectionInfo['collections']['collection'][0];
		//$this->id = $collectionInfo['id'];
		$this->title = $collectionInfo['title'];
		$this->description = $collectionInfo['description'];
		$this->coverImage = $collectionInfo['iconlarge'];
			
		$this->collections = array();
		$ID_List = "";
		$type = "";
		
		// add sets or collections
		if( array_key_exists( 'set', $collectionInfo ) == true )
		{
			$type = "set";
			foreach( $collectionInfo['set'] as $set )
			{
				if( empty( $ID_List ) == false )
				{
					$ID_List .= ";";
				}
				$ID_List .= $set['id'];
			}
		}
		elseif( array_key_exists( 'collection', $collectionInfo ) == true )
		{
			$type = "collection";
			foreach( $collectionInfo['collection'] as $collection )
			{
				if( empty( $ID_List ) == false )
				{
					$ID_List .= ";";
				}
				$ID_List .= $collection['id'];
			}
		}
		
			
		// add values to database
		if( $this->exists == false )
		{
			$this->Flickr->Database->RunQuery("insert into collections values( '$this->id', '$this->title', '$this->description', '$this->coverImage', '$ID_List', '$type')");
		}
		else
		{
			$this->Flickr->Database->RunQuery("update collections set title='$this->title',description='$this->description',cover='$this->coverImage',collections='$ID_List',type='$type' where id='$this->id'");
		}
		
		$this->collections = $this->collectionListToArray( $ID_List );
	}
	
	private function collectionListToArray( $collectionList )
	{
		$collections = array();
		
		if( $this->getCollectionArray == true )
		{
			$collectionArray = explode( ";", $collectionList );
			
			foreach( $collectionArray as $collectionID )
			{
				if( $this->type == "set" )
				{
					$collections[] = new Set( $collectionID, "", $this->getSubCollectionArray, $this->updateInfo, $this->updateImages );
				}
				elseif( $this->type == "collection" )
				{
					$collections[] = new Collection( $collectionID, "", $this->getSubCollectionArray, $this->updateInfo, $this->updateImages );
				}
			}
		}
		
		return $collections;
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
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getCollections()
	{
		return $this->collections;
	}
	
	public function getInfoArray()
	{
		$collectionInfo = array (
								"id"			=>	$this->id,
								"title"			=>	$this->title,
								"description"	=>	$this->description,
								"collections"	=>	$this->collections,
								"coverImage"	=>	$this->coverImage,
								"type"			=>	$this->type
							);
							
		return $collectionInfo;
	}
}
