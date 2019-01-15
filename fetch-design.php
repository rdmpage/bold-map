<?php

// export data from BioNames CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/lib.php');


$views = array(
	'bold' => array('alignment', 'barcode', 'export', 'geo', 'geodd', 'phylogeny', 'sequence', 'stats', 'viz'));


foreach ($views as $database => $views)
{
	foreach ($views as $view)
	{
		$url = 'https://4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/' . $database . '/_design/' . $view;
		$resp = get($url);
	
		file_put_contents($database . '/' . $view . '.js', $resp);
	}
}
		


?>