<?php

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');


//--------------------------------------------------------------------------------------------------
function api_output($obj, $callback)
{
	if ($obj->status == 404)
	{
		header('HTTP/1.1 404 Not Found');
	}
	
	header("Content-type: text/plain");
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo json_format(json_encode($obj));
	if ($callback != '')
	{
		echo ')';
	}
}

?>