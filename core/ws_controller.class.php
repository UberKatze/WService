<?php
/**
* The Core of WService
* Controller - layer between Model and View. Checks input parameters.
* @version 0.1
* @author Eugene Kosarev 
*/
/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/
class WSController{
	
	/* Required input parameters in format key:parameter_name => value:parameter_format */
	public $requiredParameters=array();
	/* Optional input parameters in format key:parameter_name => value:parameter_format */
	public $optionalParameters=array();	
	/**
	parameter_format possible values:		
		array() - list of values
		string[length] - string with fixed length
		numeric - any numeric
		natural - natural number
		regexp[exp] - custom regexp format check
		any other (suggest: 'string' or 'plain') - anything
	*/	
	
	/* If True - prints output, else - run() only returns result */
	protected $PrintOutput=true;
	
	/* Parameters recieved */
	protected $Parameters=array();

	/* Request of model function */
	protected $Request=false;
		
	/* Text Message */
	protected $Error='';
	
	/*Object of Model */
	protected $WSModel=false;
	
	/* Object of View */
	protected $WSView=false;	
	
	function __construct($requiredParameters=false,$optionalParameters=false){
		if (is_array($requiredParameters)) $this->requiredParameters=$requiredParameters;
		if (is_array($optionalParameters)) $this->requiredParameters=$optionalParameters;
	}	
	
	/* All protected variables are readable w/o special functions */
	public function __get($name) {
      return isset($this->$name) ? $this->$name : false;
    }
    
    public function doNotPrintOutput(){
    	$this->PrintOutput=false;
    }
    
    /* Set Request */
    public function setRequest($Request){
    	$this->Request=$Request;
    }
    
    /* Add optional parameter - if already exist - update */
    public function addOptionalParameter($Name,$Format){
    	$this->optionalParameters[$Name]=$Format;
    }
    
    /* Add required parameter - if already exist - update */
    public function addRequiredParameter($Name,$Format){
    	$this->requiredParameters[$Name]=$Format;    
    }
    
    /* Add Parameter */
    public function addParameter($Name,$Value){
		$this->Parameters[$Name]=$Value;
	}
	
	public function addParameters($ParametersArr){
		foreach ($ParametersArr as $Name=>$Value){
			$this->Parameters[$Name]=$Value;	
		}
	}
    
	/* retrieve parameters from GET request */
	public function fetchGETParameters(){
		if (isset($_GET)) {
			$this->Parameters=array_merge($this->Parameters,$_GET);
		}		
	}
	
	/* retrieve parameters from POST request */
	public function fetchPOSTParameters(){
		if (isset($_POST)) {
			$this->Parameters=array_merge($this->Parameters,$_POST);
		}
	}
	
	/* retrieve parameters from GET & POST request. If equal parameter occur - take POST value */
	public function fetchAllParameters(){
		$this->fetchGETParameters();
		$this->fetchPOSTParameters();
	}
	
	/* check format of parameters */
	public function checkParameters(){
		$MissingParams=array();
		$WrongFormatParams=array();		
		if (count($this->requiredParameters)!=0) {			
			foreach ($this->requiredParameters as $ExpectedName=>$RequiredFormat){				
				if (!isset($this->Parameters[$ExpectedName])) {					
					$MissingParams[]=$ExpectedName;
				}
				foreach ($this->Parameters as $ParamName=>$ParamValue) {
					if ($ExpectedName==$ParamName) {
						if (!$this->checkFormat($ParamValue,$RequiredFormat)) {
							if (is_array($RequiredFormat)) $ExpectedFormat='values ['.implode(', ',$RequiredFormat).']';
							else $ExpectedFormat=$RequiredFormat;
							$WrongFormatParams[]=$ParamName.'="'.$ParamValue.'" (expected: '.$ExpectedFormat.')';					
						}
					}
				}
			}
		}
		if (count($this->optionalParameters)!=0) {
			foreach ($this->optionalParameters as $ExpectedName=>$RequiredFormat){			
				foreach ($this->Parameters as $ParamName=>$ParamValue) {
					if ($ExpectedName==$ParamName) {
						if (!$this->checkFormat($ParamValue,$RequiredFormat)) {
							if (is_array($RequiredFormat)) $ExpectedFormat='values ['.implode(', ',$RequiredFormat).']';
							else $ExpectedFormat=$RequiredFormat;
							$WrongFormatParams[]=$ParamName.'="'.$ParamValue.'" (expected: '.$ExpectedFormat.')';					
						}
					}
				}
			}
		}
		
		if (count($MissingParams)!=0) {
			$this->Error='Missing parameter(s): '.implode(', ',$MissingParams);
			return false;
		}
		if (count($WrongFormatParams)!=0) {
			$this->Error='Wrong format of parameter(s): '.implode(', ',$WrongFormatParams);
			return false;
		}
		return true;
	}
	
	public function setView($ViewObject){
		if (is_object($ViewObject)) {
			if (get_class($ViewObject)=='WSView' || is_subclass_of($ViewObject,'WSView')) {
				$this->WSView=$ViewObject;
				return true;
			}
			
		}
		return false;		
	}
	
	public function setModel($ModelObject){
		if (is_object($ModelObject)) {
			if (get_class($ModelObject)=='WSModel' || is_subclass_of($ModelObject,'WSModel')) {
				$this->WSModel=$ModelObject;
				return true;
			}
		}
		return false;
	}
	
	/* Run service. Main function */
	public function run(){		
		if ($this->WSView==false) {
			die ('View is not set');
		}		
		if ($this->WSModel==false) {
			$this->Error='Model is not set';			
		}
		if ($this->Error!='') {
			return $this->WSView->generateOutput(array('Error'=>$this->Error),$this->PrintOutput);			
		}
		
		$this->WSModel->clearParameters();
		$this->WSModel->setParameters($this->Parameters);		
		return $this->WSView->generateOutput( $this->WSModel->processRequest($this->Request),$this->PrintOutput);		
	}
	
	/* Validate value by Format*/
	protected function checkFormat($Value,$Format){
		//list of values in array
		if (is_array($Format)){			
			if (!in_array($Value,$Format)) return false;
			else return true;
		}
		
		//string with fixed length
		if (strstr($Format,'string[')){
			$length=substr($Format,7,strpos($Format,']')-7);			
			if (strlen($Value)!=$length) return false;
			else return true;
		}
		
		if ($Format=='numeric') {
			if (!is_numeric($Value)) return false;
			return true;
		}		
			
		//natural
		if ($Format=='natural') {			
			if (!(is_numeric($Value) && $Value-floor($Value)==0 && $Value>=0))	return false;
			else return true;		
		}
				
		//regexp		
		if (strstr($Format,'regexp[')){			
			$RegExp=substr($Format,7,strlen($Format)-8);			
			if (preg_match($RegExp,$Value)==false) return false;
			else return true;
		}
		
		//default
		return true;
	}
}
?>