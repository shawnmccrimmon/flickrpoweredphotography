<?php
// test image class
require('image.php');

function displayAllSizes( $cacheArray )
{
	if( empty( $cacheArray ) ) die("Array is empty.");
	
	foreach( $cacheArray as $key => $value )
	{
	
		if( is_array($value) == true )
		{
			displayAllSizes($value);
		}
		else
		{
			$value = str_replace("/var/www/photography", "", $value);
			echo "<h3>".$key."</h3><img src=\"".$value."\" alt=\"".$key."\"><br>";
		}
	}
}

$image = new Image( "2250056368", true, true );

echo "<pre>";
//print_r( $image->getInfoArray() );
echo "</pre>";

echo "<h1>Recaching On</h1>";

echo "<h2>Largest</h2>";
echo $image->getLargestCache();
echo "<br><br>";

displayAllSizes( $image->getCache() );

echo "<h1>Recaching Off</h1>";

$image = new Image( "2250056368" );

echo "<h2>Largest</h2>";
echo $image->getLargestCache();
echo "<br><br>";

displayAllSizes( $image->getCache() );


?>