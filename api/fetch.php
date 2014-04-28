<?php

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');


$url = 'http://www.boldsystems.org/index.php/API_Public/combined';

$url .= '?container=' . 'MBMIA';
$url .= '&combined_download=tsv';

//echo $url . "\n";

$data = get($url);

//echo $data;

if ($data)
{
	$lines = explode("\n", $data);

	$keys = array();
	$row_count = 0;


	foreach ($lines as $line)
	{
		//echo $line . "\n----\n";
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			$keys = $row;
		}
		else
		{
			print_r($row);
			
			$obj = new stdclass;
			
			$n = count($row);
			for ($i = 0; $i < $n; $i++)
			{
				if (trim($row[$i]) != '')
				{
					$obj->{$keys[$i]} = $row[$i];
				}
			}
			
			// id
			$obj->_id = $obj->processid;
			
			//print_r($obj);
			
			echo json_encode($obj);
		}
		
		$row_count++;
	}
}
	

?>
