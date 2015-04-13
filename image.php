<?php

// image for a barcode sample

require_once (dirname(__FILE__) . '/lib.php');

$id = '';

if (isset($_GET['id']))
{
	$id = $_GET['id'];
	
	$id = preg_replace('/\.COI-5P$/', '', $id);
}

$url = 'http://www.boldsystems.org/index.php/API_Public/specimen';
$url .= '?ids=' . $id;
$url .= '&format=tsv';



$image_url = "images/blank100x100.png";

$data = get($url);

//echo $url;
//print_r($data);
//exit();

if ($data)
{

	$lines = explode("\n", $data);

	$keys = array();
	$row_count = 0;
	
	foreach ($lines as $line)
	{
		if ($line == '') break;
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			$keys = $row;
		}
		else
		{
			
			$obj = new stdclass;
			
			$n = count($row);
			for ($i = 0; $i < $n; $i++)
			{
				if (trim($row[$i]) != '')
				{
					$obj->{$keys[$i]} = $row[$i];
				}
			}
			
			if (isset($obj->image_urls))
			{
				if ($obj->image_urls != '')
				{
					$image_url = $obj->image_urls;
				}
			}
		}
		
		$row_count++;
	}
}

header("Location: $image_url");
	

?>
