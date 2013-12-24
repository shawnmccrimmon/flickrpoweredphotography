<?php

/*  Database class - connect to mysql database	*/

class Database {

	private $mysqli;
	private $connected = false;
	public $lastError;
	
	public function Database( $mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase )
	{
		$this->mysqli = new mysqli( $mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase );
		
		if ( mysqli_connect_errno() ) 
		{
			$this->lastError = mysqli_connect_error();
		}
		else
		{
			$this->connected = true;
		}
	}
	
	public function RunQuery( $mysqlQuery, $returnData = false )
	{
		$queryArray = array();
		/* Only run query if we're connected */
		if( $this->connected == true )
		{
			//$mysqlQuery = $this->mysqli->real_escape_string( $mysqlQuery );
			$queryReturn = $this->mysqli->query( $mysqlQuery );
			$this->lastError = $this->mysqli->error;
			
			if ( 
					$returnData == true 
					&&
					$queryReturn->num_rows > 0
				)
			{
				while ( $queryArray[] = $queryReturn->fetch_assoc() );
				/* free result set */
				$queryReturn->free();
			}
			elseif( is_bool( $queryReturn ) == true )
			{
				$queryArray = $queryReturn;
			}
			elseif( $queryReturn->num_rows == 0 )
			{
				$queryArray = false;
			}
			else
			{
				$queryArray = $queryReturn;
			}
			/* return all the results */
			return $queryArray;
		}
		else
		{
			return false;
		}
	}
	
	public function Close()
	{
		$this->mysqli->close();
	}
}
?>