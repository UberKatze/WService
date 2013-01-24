<?php
/**
* The Core of WService
* Model. Handlers for request must end with word 'Handler'. Handlers returns any associative array
* @version 0.1
* @author Eugene Kosarev 
*/
/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/
class WSModel{
	// Link to database
	protected $DBLink;
	
	protected $Parameters=array();
	
	protected $DefalutRequestHandler=false;
	
	protected $LastRequest=false;
	
	function __construct($DBLink){
		if (!$DBLink) die();
		$this->DBLink=$DBLink;		
	}
		
	/* Clear parameters */
	public function clearParameters(){
		$this->Parameters=array();
	}
	
	/* Set parameters with array of parameters */
	public function setParameters($Parameters){
		$this->Parameters=$Parameters;
	}
	
	/* Add parameter */
	public function addParameter($Name,$Value){
		$this->Parameters[$Name]=$Value;
	}
	
	/* try to call method to handle request ( method (except default) must end with word 'Handler') */
	public function processRequest($Request=false){							
		$Request=$this->getHand($Request);
		
		//check Request Handler exist
		if (!method_exists($this,$Request)) {
			$this->LastRequest=false;
			return array('Error'=>'Call to undefined Request Handler: '.($Request==$this->DefalutRequestHandler?'Default Request Handler':str_ireplace('Handler','',$Request)));
		}
		else {
			$this->LastRequest=str_ireplace('Handler','',$Request);
		}
				
		//execute Request Handler
		return $this->$Request();
	}	
	
	/* returns all possible request names */
	public function getRequests(){
		$methods_arr = get_class_methods(get_class($this));
		$ret_arr=array();
		foreach ($methods_arr as $method){
			if (stripos($method,'Handler')) $ret_arr[str_ireplace('Handler','',$method)]='';
		}
		return $ret_arr;
	}
	
	/* returns Handler Function Name  */
	protected function getHand($Request){		
		//if Request is false - use Default Request Handler
		if ($this->DefalutRequestHandler!=false && $Request==false) {
			$Request=$this->DefalutRequestHandler;
		}
		elseif ($Request!=false) {			
			$Request=$Request.'Handler';
		}	
		
		return $Request;		
	}
	
}
?>