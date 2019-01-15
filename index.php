<?php

// Barcode taxonomy

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/treemap.php');

// default
$path = '[]';

if (isset($_GET['path']))
{
	$path = $_GET['path'];
}


$key = json_decode($path);

// Get children of this node
$startkey = $key;
$endkey = $key;
$endkey[] = "z";
		
$url = '_design/barcode/_view/lineage?startkey=' . urlencode(json_encode($startkey))
	. '&endkey=' .  urlencode(json_encode($endkey))
	. '&group_level=' . (count($key) + 1);
				
if ($config['stale'])
{
	$url .= '&stale=ok';
}	
	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$response_obj = json_decode($resp);

$items = array();
 
foreach ($response_obj->rows as $row)
{
	$depth = count($row->key);
	
	$i = new Item(
		log10($row->value + 1), 
		$row->key[$depth-1], 
		json_encode($row->key),
		($depth == 3)
		);
	array_push($items, $i);
}


// Treemap bounds
$tm_width = 300;
$tm_height = 300;
$r = new Rectangle(0,0,$tm_width,$tm_height);

// Compute the layout
splitLayout($items, $r);

// Use a colour gradient to colour cells
$theColorBegin = 0x006600;
$theColorEnd = 0x000066;


$theR0 = ($theColorBegin & 0xff0000) >> 16;
$theG0 = ($theColorBegin & 0x00ff00) >> 8;
$theB0 = ($theColorBegin & 0x0000ff) >> 0;

$theR1 = ($theColorEnd & 0xff0000) >> 16;
$theG1 = ($theColorEnd & 0x00ff00) >> 8;
$theB1 = ($theColorEnd & 0x0000ff) >> 0;

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>BOLD DNA Barcode Map</title>

    <style type="text/css">
      body {
        margin: 0;
        padding: 0;
        font-family: sans-serif;
      }

      #map {
       	margin-right:300px;
        height: 400px;
      }
      
      #details {
		float:right;
		width:300px;
		font-size:12px;
      }
      #hit {
		width:300px;      
      	overflow: auto;
      }
      #treemap {
		width:300px;
		height:300px;
		position:relative;
		color:white;
		font-size:10px;		
      }  
      .barcode {
      	font-size:10px;
      	padding:5px;
      	height:40px;
      	border-bottom: 1px dotted rgb(240,240,240);
      }
      .barcode-thumbnail{
         width:40px;
         height:40px;
         border: 1px solid rgb(240,240,240);
         float:right;
      }
      .explain {
      	color: rgb(128,128,128);
      }
    </style>
    
    <script src="http://www.google.com/jsapi"></script>    
    
	<script async defer
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCtqKDniKtABMrEeoV32OsYCXzKe0PpehI&callback=initialize">
    </script>    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    
<script type="text/javascript">
    function mouse_over(id) 
    {
    	var e = document.getElementById(id);
    	e.style.opacity= "1.0";
	    e.style.filter = "alpha(opacity=100)";
    }
    
    function mouse_out(id) 
    {
    	var e = document.getElementById(id);
    	e.style.opacity=0.6;
        e.style.filter = "alpha(opacity=60)";
    }    

</script>
    

    <script type="text/javascript">
    
		var map;
		var path = '<?php echo $path; ?>';
		var marker = null;
	
		google.load('maps', '3', {
			other_params: 'sensor=false'
		  });
		google.setOnLoadCallback(initialize);
		  
		  
		//------------------------------------------------------------------------------------------
		// Normalizes the coords that tiles repeat across the x axis (horizontally)
		// like the standard Google map tiles.
		function getNormalizedCoord(coord, zoom) {
		  var y = coord.y;
		  var x = coord.x;
		
		  // tile range in one direction range is dependent on zoom level
		  // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
		  var tileRange = 1 << zoom;
		
		  // don't repeat across y-axis (vertically)
		  if (y < 0 || y >= tileRange) {
			return null;
		  }
		
		  // repeat across x-axis
		  if (x < 0 || x >= tileRange) {
			x = (x % tileRange + tileRange) % tileRange;
		  }
		
		  return {
			x: x,
			y: y
		  };
		}
			  
      
		//--------------------------------------------------------------------------------------------
		/** @constructor */
		function BoldMapType(tileSize) {
		  this.tileSize = tileSize;
		}
		
		//--------------------------------------------------------------------------------------------
		BoldMapType.prototype.getTile = function(coord, zoom, ownerDocument) {	
		  	var div = ownerDocument.createElement('div');
		  
			var normalizedCoord = getNormalizedCoord(coord, zoom);
			  if (!normalizedCoord) {
				return null;
			  }  
		  
		  	// Get tile from CouchDB
		  	var url = 'couchtile.php?x=' + normalizedCoord.x 
		  		+ '&y=' + normalizedCoord.y 
		  		+ '&z=' + zoom
		  		+ '&path=' + encodeURIComponent(path);
		  
			div.innerHTML = '<img src="' + url + '"/>';
		  	div.style.width = this.tileSize.width + 'px';
		 	div.style.height = this.tileSize.height + 'px';
		  
		  	return div;
		};      
		
		//--------------------------------------------------------------------------------------------
		// handle user click on map
		function placeMarker(position, map) {

			if (marker) {
			   marker.setMap(null);
			   marker = null;
			}
  			 marker = new google.maps.Marker({
      position: position,
      map: map
  });
		
		
			$('#hit').html('');
			
			$('#hit').html('Loading...');
			
			// http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
			// Compute the tile the user has clicked on, and the relative position of the click
			// within that tile
			var tile_size = 256;
			var pixels = 4;
			var zoom = map.getZoom();
			
			var x_pos = (parseFloat(position.lng()) + 180)/360 * Math.pow(2, zoom);
			var x = Math.floor(x_pos);
			
			// position within tile
			var relative_x = Math.round(tile_size * (x_pos - x));
		
			var y_pos = (1-Math.log(Math.tan(parseFloat (position.lat())*Math.PI/180) + 1/Math.cos(parseFloat(position.lat())*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom);
			var y = Math.floor(y_pos);
			
			// position within tile
			var relative_y = Math.round(tile_size * (y_pos - y));
		
			// cluster into square defined by pixel size
			relative_x = Math.floor(relative_x / pixels) * pixels;
		    relative_y = Math.floor(relative_y / pixels) * pixels;
		
			/*
			// key to query CouchDB
			var key = [];
			key.push(zoom);
			key.push(x);
			key.push(y);
			key.push(relative_x);
			key.push(relative_y);
			

			var url = "couchtilehit.php?key=" + JSON.stringify(key) + "&callback=?";
			*/
			
			var url = "couchtilehitpath.php"
				+ "?x=" + x
				+ "&y=" + y
				+ "&z=" + zoom
				+ "&rx=" + relative_x
				+ "&ry=" + relative_y
				+ "&path=" + encodeURIComponent(path)
				+ "&callback=?";
			
			
			$.getJSON(url,
				function(data){
					if (data.status == 200) {
						if (data.results.length != 0) {
							var html = '';
							
							html += '<div>' + '<b>Number of barcodes: ' + data.results.length + '</b>' + '</div>';
							
							html += '<span class="explain">Tile [x,y,z,rx,ry] = [' + x + ',' + y + ',' + zoom + ',' + relative_x + ',' + relative_y + ']</span>';
							
							for (var i in data.results) {
							   html += '<div class="barcode">';
							   
							   
							   html += '<img src="image.php?id=' + data.results[i].processid + '" width="40" height="40" class="barcode-thumbnail" />';
							   
							   html += data.results[i].processid + ' ';
							   
							   if (data.results[i].bin_guid){
							   	html += '<a href="http://www.boldsystems.org/index.php/Public_BarcodeCluster?clusteruri=' + data.results[i].bin_guid + '" target=_new" >' +  data.results[i].bin_guid + '</a>' + '<br />';
							   	}
							   if (data.results[i].bin_uri){
							   	html += '<a href="http://www.boldsystems.org/index.php/Public_BarcodeCluster?clusteruri=' + data.results[i].bin_uri + '" target=_new" >' +  data.results[i].bin_uri + '</a>' + '<br />';
							   	}

							   if (data.results[i].museumid) {
								html += data.results[i].museumid + '<br />';
							   }
							   
							   if (data.results[i].species_reg) {
								html += data.results[i].species_reg + ' ';
							   }
							   if (data.results[i].species_name) {
								html += data.results[i].species_name + ' ';
							   }
							   
							   if (data.results[i].accession) {
								html += '<a href="http://www.ncbi.nlm.nih.gov/nuccore/' + data.results[i].accession + '" target="_new" >' + data.results[i].accession + '</a>';
							   }
							   html += '</div>';
							   
							}
							
							$('#hit').html(html);
							
						} else {
						    $('#hit').html('No barcodes here (try clicking again)');
						}
						
					}
				});
		}

      //--------------------------------------------------------------------------------------------
      function initialize() {
    	
		var center = new google.maps.LatLng(0,0);
		
        map = new google.maps.Map(document.getElementById('map'), {
          zoom: 2,
          center: center,
          mapTypeId: google.maps.MapTypeId.TERRAIN,
          draggableCursor: 'auto'
        });
        
        // hit test
		google.maps.event.addListener(map, 'click', function(e) {
    		placeMarker(e.latLng, map);
  			});
        
		// Insert this overlay map type as the first overlay map type at
		// position 0. Note that all overlay map types appear on top of
		// their parent base map.
		map.overlayMapTypes.insertAt(
		  0, new BoldMapType(new google.maps.Size(256, 256)));
      
		/* http://stackoverflow.com/questions/6762564/setting-div-width-according-to-the-screen-size-of-user */
		$(window).resize(function() { 
			var windowHeight = $(window).height();
			$('#map').css({'height':windowHeight });
			$('#hit').css({'height':(windowHeight - 310)});
		});	
	
      }
      
    </script>
  </head>
  <body onload="$(window).resize()">
  
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-35330099-1', 'iphylo.org');
  ga('send', 'pageview');

</script>  
  
   	<div style="position:relative;">
 	    <div id="details">
 	    	<div id="breadcrumbs" style="color:rgb(128,128,128);font-size:10px;">
<?php
$back = array();
$breadcrumbs = array();
$breadcrumbs[] = 'Animalia';
$breadcrumbs = array_merge($breadcrumbs, $key);

$n = count($key);
for ($i = 0; $i < $n; $i++)
{
	$back[] = array_slice($key, 0, $i);
}

$m = count($breadcrumbs);
for ($i = 0; $i < $m; $i++)
{
	echo ' &gt; ';
	if ($i < $n)
	{
		echo '<a href="?path=' . urlencode(json_encode($back[$i])) . '">';
	}
	echo $breadcrumbs[$i];
	if ($i < $n)
	{
		echo '</a>';
	}
	
}
?>
 	    	
 	    	
 	    	</div>
 	    	<div id="treemap">
<?php

$theNumSteps = count($items);
$count = 0;
foreach ($items as $i)
{
	// Note that each treemap cell has position:absolute
	echo '<div id="' . urlencode($i->id) . '" class="cell" style="opacity:0.6;filter:alpha(opacity=60);position: absolute; overflow:hidden;text-align:center;';
	echo ' left:' . $i->bounds->x . 'px;';
	echo ' top:' . $i->bounds->y . 'px;';
	echo ' width:' . $i->bounds->w. 'px;';
	echo ' height:' . $i->bounds->h . 'px;';
	echo ' border:1px solid white;';
	
	// Background colour
    $theR = interpolate($theR0, $theR1, $count, $theNumSteps);
    $theG = interpolate($theG0, $theG1, $count, $theNumSteps);
    $theB = interpolate($theB0, $theB1, $count, $theNumSteps);
    $theVal = ((($theR << 8) | $theG) << 8) | $theB;

    printf("background-color: #%06X; ", $theVal);
	echo '" ';
    
    // Mouse activity
  
    echo ' onMouseOver="mouse_over(\'' . urlencode($i->id) . '\');" ';
    echo ' onMouseOut="mouse_out(\'' . urlencode($i->id) . '\');" ';
   

	// Link to drill down
	if (!$i->isLeaf)
	{
	    echo ' onClick="document.location=\'?path=' . urlencode($i->id) . '\';" ';
	}
	echo ' >';
	
				
	// Text is taxon name, plus number of leaf descendants
	// Note that $number[$count] is log (n+1)
	$tag = $i->label . ' ' . number_format(pow(10, $i->size) - 1);
			
			
	// format the tag...
	// 1. Find longest word
	$words = preg_split("/[\s]+/", $tag);
	
	$max_length = 0;
	foreach($words as $word)
	{
		$max_length = max($max_length, strlen($word));
	}
	
	// Font upper bound is proportional to length of longest word
	$font_height = $i->bounds->w / $max_length;
	$font_height *= 1.2;
	if ($font_height < 10)
	{
		$font_height = 10;
	}

	// text			
	echo '<span style="font-size:' . $font_height . 'px;">' . $tag . '</span>';

	echo '</div>';
	echo "\n";

	$count++;
}

?>
 	    	
 	    	</div>
 	    	<div id="hit"></div>
 	    </div>
   		<div id="map"></div>
	</div>
  </body>
</html>