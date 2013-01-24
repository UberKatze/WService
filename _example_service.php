<?php

if (!isset($method) || !isset($output)) die();

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once("core/webservice.php");
require_once("example/custom_model.class.php");
require_once("example/custom_view.class.php");
require_once("example/custom_controller.class.php");

// Put your DB attributes here
if ( !($DBLink = @mysql_connect('localhost', 'root', '')) ) die("Could not connect to DB");
if (!mysql_select_db('test')) die("Could not select DB");
// -----------------------------

$Webservice = new Webservice();

$Webservice->DBLink=$DBLink;

$Webservice->FetchParametersFrom=$method;

$Webservice->CatchAll=true;

$Webservice->WSModelClass='CustomModel';
$Webservice->WSViewClass='CustomView';
$Webservice->WSControllerClass='CustomController';

switch ($method) {
	case 'GET': 
		$Webservice->fetchRequestFromGET('request');
		$Webservice->addRequiredParameters(false,array('request'=>'string'));		
	break;
	case 'POST':
		$Webservice->fetchRequestFromPOST('request');
		$Webservice->addRequiredParameters(false,array('request'=>'string'));		
	break;
	case 'SOAP':		
		$Webservice->SOAPURI='Example';
	break;
}
	
$Webservice->OutputFormat=$output;

$Webservice->addRequiredParameters('echoDates',array('date1'=>'dd-mm-yyyy','date2'=>'dd-mm-yyyy'));
$Webservice->addOptionalParameters('getPi',array('round'=>'natural'));

/* To view all handlers and parameters use webservice with '_struct_xml' parameter */

$Webservice->run();

?>