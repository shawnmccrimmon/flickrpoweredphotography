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
			$mysqlQuery = mysql_real_escape_string( $mysqlQuery );
			$queryReturn = $this->mysqli->query( $mysqlQuery );
			
			if( $returnData == true )
			{
				while ( $queryArray[] = $queryReturn->fetch_assoc() );
				/* free result set */
				$queryReturn->free();
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