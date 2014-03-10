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

$key = $_GET['key'];
	
$url = '_design/geo/_view/tile?key=' . urlencode($key)
	. '&reduce=false'
	. '&include_docs=true';
	
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