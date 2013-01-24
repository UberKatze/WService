<?php
/**
* The Core of WService
* Class to handle SOAP requests. Uses MVC objects to wrap.
* @version 0.1
* @author Eugene Kosarev 
*/
/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/
class SOAPBridge{
	protected $WSModelObject;	
	protected $WSViewObject;
	protected $WSControllerObject;
	
	function __construct($WSModelObject,$WSViewObject,$WSControllerObject){
		$this->WSModelObject=$WSModelObject;
		$this->WSViewObject=$WSViewObject;
		$this->WSControllerObject=$WSControllerObject;		
	}
	
	public function Request($RequestName,$ParamsJSON){		
		$this->WSControllerObject->addParameters(json_decode($ParamsJSON));		
		$this->WSControllerObject->checkParameters();
		$this->WSControllerObject->setView($this->WSViewObject);
		$this->WSControllerObject->setModel($this->WSModelObject);		
		$this->WSControllerObject->setRequest($RequestName);
		$this->WSControllerObject->doNotPrintOutput();
		return $this->WSControllerObject->run();
	}
}

?>