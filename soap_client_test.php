<?php
  ini_set("soap.wsdl_cache_enabled", "0");
  $client = new SoapClient("http://localhost/WService/example_service_soap.php?_wsdl");
  try {
  	echo "<pre>\n";
  	print $client->Request("getPi",json_encode(array("round"=>'6')));  	
    echo "\n</pre>\n";
  } 
  catch (SoapFault $exception) {
    echo $exception;       
  }
?> 