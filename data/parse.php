<?php

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

ini_set("auto_detect_line_endings", true); // vital because some files have Windows ending

// BOLD data
$filename = 'iBOL_phase_4.50_COI.tsv';
$filename = 'iBOL_phase_4.25_COI.tsv';
$filename = 'iBOL_phase_4.00_COI.tsv';
$filename = 'iBOL_phase_3.75_COI.tsv'; 
$filename = 'iBOL_phase3.0_COI.tsv';
$filename = 'iBOL_phase_2.75_COI.tsv';
$filename = 'iBOL_phase_2.50_COI.tsv';
$filename = 'iBOL_phase_2.25_COI.tsv';


$filenames=array(
'iBOL_phase_0.5.tsv',
'iBOL_phase_0.75.tsv',
'iBOL_phase_1.0.tsv',
'iBOL_phase_1.5.tsv',
'iBOL_phase_1.25.tsv',
'iBOL_phase_1.75.tsv',
'iBOL_phase_2.0_COI.tsv'
);

$filenames=array(
'iBOL_phase_4.75_COI.tsv'
);

foreach ($filenames as $filename)
{
	$keys = array();
	
	$row_count = 0;
	
	// CouchDB
	$docs = new stdclass;
	$docs->docs = array();
	
	$bulk_size = 1000;
	$bulk_count = 0;
	$bulk_override = false;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
	
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
		
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			$keys = $row;
		}
		else
		{
			//print_r($row);
			
			$obj = new stdclass;
			
			$n = count($row);
			for ($i = 0; $i < $n; $i++)
			{
				if ($row[$i] != '')
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
			
			
			// FASTA file
			if (0)
			{			
				echo ">" . $obj->processid . "\n";
				echo $obj->nucraw . "\n";
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
