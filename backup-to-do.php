<?php

// export data from BioNames CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/lib.php');


$database = 'bold';

file_put_contents($database . '/data.jsonl', '');

$rows_per_page = 1000;
$skip = 0;

$done = false;
while (!$done)
{


	$url = 'http://127.0.0.1:5984/' . $database . '/_design/export/_view/jsonl';
	
	$url .= '?limit=' . $rows_per_page . '&skip=' . $skip;
	
	echo $url . "\n";

	$resp = get($url);

	if ($resp)
	{
		$response_obj = json_decode($resp);
		if (!isset($response_obj->error))
		{
			$n = count($response_obj->rows);
			
			foreach ($response_obj->rows as $row)
			{
			
				file_put_contents($database . '/data.jsonl', json_encode($row), FILE_APPEND);

			}	
		}
	}
	
	$skip += $rows_per_page;
	$done = ($n < $rows_per_page);			
}

			


?>