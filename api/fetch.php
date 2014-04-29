<?php

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');

// Specimens and sequences
$url = 'http://www.boldsystems.org/index.php/API_Public/combined';

$url .= '?container=' . 'MBMIA';
$url .= '&combined_download=tsv';


$data = get($url);

if ($data)
{
	// CouchDB
	$docs = new stdclass;
	$docs->docs = array();
	
	$bulk_size = 1000;
	$bulk_count = 0;
	$bulk_override = false;


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
			
			// id
			$obj->_id = $obj->processid;
			
			// Upload in bulk
			$docs->docs [] = $obj;
			echo ".";
			
			if (count($docs->docs ) == $bulk_size)
			{
				if ($bulk_override)
				{
					$docs->new_edits = false;
				}
			
				echo "CouchDB...";
				$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'] . '/_bulk_docs', json_encode($docs));
				$bulk_count += $bulk_size;
				echo "\nUploaded... total=$bulk_count\n";
			
				$docs->docs  = array();
			}
		}
		
		$row_count++;
	}
	
	 // Make sure we load the last set of docs
	if (count($docs->docs ) != 0)
	{
		echo "CouchDB...\n";
		
		
		if ($bulk_override)
		{
			$docs->new_edits = false;
		}
		
		$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'] . '/_bulk_docs', json_encode($docs));		
		echo $resp;
		
		
		$bulk_count += count($docs->docs);
		echo "\nUploaded... total=$bulk_count\n";
	
	
		$docs->docs  = array();
	}	
}
	

?>
