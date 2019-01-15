<?php

// CouchDB as map tile server

require_once (dirname(__FILE__) . '/couchsimple.php');

// tile request will supply x,y and z (zoom level)


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

$path = '[]';

if (isset($_GET['path']))
{
	$path = urldecode($_GET['path']);
}

// Find all points in this tile
	
if ($path == '[]')
{
	$startkey = array($zoom, $x, $y);
	$endkey = array($zoom, $x, $y, "zzz","zzz", "zzz", 256);
}
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
	
	
$url = '_design/geo/_view/tile_taxon?startkey=' . urlencode(json_encode($startkey))
	. '&endkey=' .  urlencode(json_encode($endkey))
	. '&group_level=8';
	
	
if ($config['stale'])
{
	$url .= '&stale=ok';
}	
	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$response_obj = json_decode($resp);

//echo $resp;

// Create SVG tile
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="256" height="256px">
   <style type="text/css">
      <![CDATA[     
      ]]>
   </style>
 <g>';
 
foreach ($response_obj->rows as $row)
{
	if (0)
	{
		$x_pos = $row->key[3];
		$y_pos = $row->key[4];
	}
	else
	{
		$x_pos = $row->key[6];
		$y_pos = $row->key[7];
	}
	
	$x_pos = floor($x_pos/4) * 4;
	$y_pos = floor($y_pos/4) * 4;
	
	$xml .= '<rect id="dot" x="' . ($x_pos - 2) . '" y="' . ($y_pos - 2) . '" width="4" height="4" style="stroke-width:1;"';
	
	// Colours
	
	if (1)
	{
		// black
		//$fill = 'rgba(0,0,0,0.5)';

		// purple
		$fill="rgba(128,0,64,0.5)";
	}
	else
	{
		// colours
		$fill = "rgba(255,255,0 ,0.5)";
		if ($row->value > 5)
		{
			$fill="rgba(255,127,0,0.5)";
		}
		if ($row->value > 10)
		{
			$fill="rgba(255,0,0,0.5)";
		}
		if ($row->value > 20)
		{
			$fill="rgba(128,0,64,0.5)";
		}
		
	}		
	$xml .= ' fill="'. $fill . '"';
	$xml .= ' stroke="rgb(128,0,64)"';
	
	$xml .= '/>';
} 
 
$xml .= '
      </g>
	</svg>';
	

// Serve up tile	
header("Content-type: image/svg+xml");
header("Cache-control: max-age=3600");

echo $xml;

?>