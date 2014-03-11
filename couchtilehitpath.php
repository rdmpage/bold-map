<?php

// CouchDB hit lookup

// Query is tile key [zoom, x, y, rx, ry] where x and y are tile numbers, and rx and ry
// are the locations of hit within that tile (i.e., rx and ry range from 0-256 for 256 pixel tiles)

// Return a list of CouchDB documents corresponding to the location on map

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

$callback = '';

// If no query parameters 
if (count($_GET) == 0)
{
	//default_display();
	echo 'hi';
	exit(0);
}

if (isset($_GET['callback']))
{	
	$callback = $_GET['callback'];
}


if (isset($_GET['x']))
{
	$x = (Integer)$_GET['x'];
}

if (isset($_GET['y']))
{
	$y = (Integer)$_GET['y'];
}

if (isset($_GET['z']))
{
	$zoom = (Integer)$_GET['z'];
}

if (isset($_GET['rx']))
{
	$rx = (Integer)$_GET['rx'];
}

if (isset($_GET['ry']))
{
	$ry = (Integer)$_GET['ry'];
}

$path = '[]';

if (isset($_GET['path']))
{
	$path = urldecode($_GET['path']);
}

// Find all points in this tile
	
if ($path == '[]')
{
	$startkey = array($zoom, $x, $y, $rx, $ry);
	$endkey = array($zoom, $x, $y, $rx, $ry, "zzz");
}
else
{
	$taxa = json_decode($path);

	$startkey = array($zoom, $x, $y, $rx, $ry);
	
	foreach ($taxa as $t)
	{
		$startkey[] = $t;
	}

	$endkey = array($zoom, $x, $y, $rx, $ry);
	foreach ($taxa as $t)
	{
		$endkey[] = $t;
	}
	$endkey[] = 'zzz';
}

/*
else
{
	$taxa = json_decode($path);
	
	$startkey = array($zoom, $x, $y);
	foreach ($taxa as $t)
	{
		$startkey[] = $t;
	}
	
	$endkey = array($zoom, $x, $y);
	foreach ($taxa as $t)
	{
		$endkey[] = $t;
	}
	
	// extra
	$to_add = 3 - count($taxa);
	for ($i = 0; $i < $to_add; $i++)
	{
		$endkey[] = 'zzz';
	}
	$endkey[] = (Integer)256;
}
*/	

/*	
$url = '_design/geo/_view/tile_taxon?key=' . urlencode($key)
	. '&reduce=false'
	. '&include_docs=true';
*/

$url = '_design/geo/_view/tile_taxon_hit?startkey=' . urlencode(json_encode($startkey))
	. '&endkey=' .  urlencode(json_encode($endkey))
	. '&reduce=false'
	. '&include_docs=true';
	
//echo $url;exit();

		
if ($config['stale'])
{
	$url .= '&stale=ok';
}	
	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$response_obj = json_decode($resp);

$obj = new stdclass;
$obj->status = 200;
$obj->results = array();

foreach ($response_obj->rows as $row)
{
	$obj->results[] = $row->doc;
}

api_output($obj, $callback);

?>