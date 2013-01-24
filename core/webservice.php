<?php
/**
* The Core of WService
* Mediator - Centralized entry point
* @version 0.1
* @author Eugene Kosarev 
*/
/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/
require_once("nwexceptions/nwexceptions.php");
require_once("ws_controller.class.php");
require_once("ws_view.class.php");
require_once("ws_model.class.php");
require_once("ws_soapbridge.class.php");

class Webservice {
	public $WSViewClass='WSView';
	public $WSModelClass='WSModel';
	public $WSControllerClass='WSController';
	public $OutputFormat='XML';
	public $CatchWarnings=false;
	public $CatchAll=false;	
	public $FetchParametersFrom='GET';
	public $Request=false;	
	public $SOAPEncode=true;
	public $SOAPRPC=true;
	public $SOAPURI='';
	
	public $Model=false;
	public $View=false;
	public $Controller=false;	
	
	public $DBLink=false;
	
	protected $OptionalParameters=array();		//Optional Parameters for all Handlers
	protected $RequiredParameters=array();		//Required Parameters for all Handlers
	protected $HandlersParameters=array();		//Parameters-to-Handlers
	protected $Parameters=array();				//Parameters summary
	protected $RequestParameter=false;	

	
	public function fetchRequestFromGET($Name){
		$this->Request=@$_GET[$Name];		
		$this->RequestParameter=$Name;
	}
	
	public function fetchRequestFromPOST($Name){
		$this->Request=@$_POST[$Name];
		$this->RequestParameter=$Name;
	}
	
	public function addRequiredParameters($Handler,$Parameters){
		if (!is_array($Parameters)) return;
		if ($Handler!=false) {
			if (is_array($Handler)) {
				foreach ($Handler as $H) {					
					$this->HandlersParameters[$H]['required']=array_merge(is_array($this->HandlersParameters[$H]['required'])?$this->HandlersParameters[$H]['required']:array(),$Parameters);
				}
			}
			else {
				$this->HandlersParameters[$Handler]['required']=array_merge(is_array($this->HandlersParameters[$Handler]['required'])?$this->HandlersParameters[$Handler]['required']:array(),$Parameters);
			}
		}
		else {
			$this->RequiredParameters=array_merge($this->RequiredParameters,$Parameters);
		}
	}
	
	public function addOptionalParameters($Handler,$Parameters){
		if (!is_array($Parameters)) return;
		if ($Handler!=false) {
			if (is_array($Handler)) {
				foreach ($Handler as $H) {
					$this->HandlersParameters[$H]['optional']=array_merge(is_array($this->HandlersParameters[$H]['optional'])?$this->HandlersParameters[$H]['optional']:array(),$Parameters);
				}
			}
			else {
				$this->HandlersParameters[$Handler]['optional']=array_merge(is_array($this->HandlersParameters[$Handler]['optional'])?$this->HandlersParameters[$Handler]['optional']:array(),$Parameters);
			}
		}
		else {
			$this->OptionalParameters=array_merge($this->OptionalParameters,$Parameters);
		}
	}
	
	public function prepare(){		
		
		//Create Controller object
		if (class_exists($this->WSControllerClass)) {
			$this->Controller = new $this->WSControllerClass();
		}
		else {
			die("Controller class '$this->WSControllerClass' does not exists");
		}
		
		//Create View object
		if (class_exists($this->WSViewClass)) {
			$this->View = new $this->WSViewClass($this->OutputFormat);		
		}
		else {
			die("View class '$this->WSViewClass' does not exists");
		}
		
		//Create Model object
		if (class_exists($this->WSModelClass)) {
			$this->Model = new $this->WSModelClass($this->DBLink);
		}
		else {
			die("Model class '$this->WSModelClass' does not exists");
		}
		
		$this->Parameters=$this->Model->getRequests();		
		
		foreach ($this->Parameters as $Handler=>$value) {
			unset($this->Parameters[$Handler]);
			if (count($this->RequiredParameters!=0)) {
				$this->Parameters[$Handler]['required']=$this->RequiredParameters;
			}
			else {
				$this->Parameters[$Handler]['required']=array();
			}
			
			if (count($this->OptionalParameters!=0)) {
				$this->Parameters[$Handler]['optional']=$this->OptionalParameters;
			}
			else {
				$this->Parameters[$Handler]['optional']=array();
			}			
		}
		foreach ($this->HandlersParameters as $Handler=>$Parameters) {			
			if (isset($this->Parameters[$Handler]['required'])) $this->Parameters[$Handler]['required']=array_merge($this->Parameters[$Handler]['required'],is_array($Parameters['required'])?$Parameters['required']:array());
			if (isset($this->Parameters[$Handler]['optional'])) $this->Parameters[$Handler]['optional']=array_merge($this->Parameters[$Handler]['optional'],is_array($Parameters['optional'])?$Parameters['optional']:array());
		}
		
		if ($this->Request!='') {
			$this->Controller->optionalParameters=$this->Parameters[$this->Request]['optional'];
			$this->Controller->requiredParameters=$this->Parameters[$this->Request]['required'];
		}
		else {
			$this->Controller->optionalParameters=$this->OptionalParameters;
			$this->Controller->requiredParameters=$this->RequiredParameters;
		}
		
		
		/* Kinda some magic */
		if (isset($_REQUEST['_struct_xml'])) {
			$this->View->setOutputFormat('XML');
			$this->View->setXMLRootTag($this->RequestParameter==false?'Request':$this->RequestParameter);
			$this->View->generateOutput($this->getFormattedParams());
			die();
		}		
	}
	
	public function run() {
		if ($this->Controller==false || $this->Model==false || $this->View==false) $this->prepare();
		
		switch ($this->FetchParametersFrom) {
			case 'GET':
				$this->Controller->fetchGETParameters();
			break;
			case 'POST':
				$this->Controller->fetchPOSTParameters();
			break;	
			case 'SOAP':
				$this->runSOAP();
				die();
			break;	
			default:
				$this->Controller->fetchAllParameters();
		}
		
		if ($this->CatchWarnings==true) set_error_handler("nw_error_handler", E_WARNING);
		if ($this->CatchAll==true) set_error_handler("nw_error_handler", E_ALL);		
		try {
			$this->Controller->checkParameters();
			$this->Controller->setView($this->View);
			$this->Controller->setModel($this->Model);		
			$this->Controller->setRequest($this->Request);			
			$this->Controller->run();
		}
		catch (WarningException $exception){
			$Trace=$exception->getTrace();
			$File=$Trace[0]['args'][2];
			$Line=$Trace[0]['args'][3];
			$Error="Caught Warning Exception: ".$exception->getMessage().". Code: ".$exception->getCode().". File: ".$File.". Line: ".$Line.".";
			$this->View->generateOutput(array('Error'=>$Error));
		}
		catch (NoticeException $exception){
			$Trace=$exception->getTrace();
			$File=$Trace[0]['args'][2];
			$Line=$Trace[0]['args'][3];
			$Error="Caught Notice Exception: ".$exception->getMessage().". Code: ".$exception->getCode().". File: ".$File.". Line: ".$Line.".";
			$this->View->generateOutput(array('Error'=>$Error));
		}
		catch (Exception $exception){
			$Error="Caught Exception: ".$exception->getMessage().". Code: ".$exception->getCode().". File: ".$exception->getFile().". Line: ".$exception->getLine().".";
			$this->View->generateOutput(array('Error'=>$Error));
		}
	}
	
	public function runSOAP(){		
		if (isset($_REQUEST['_wsdl'])) {
			print $this->generateWSDL();
			die();
		}
		if ($this->Controller==false || $this->Model==false || $this->View==false) $this->prepare();
				
		$server = new SoapServer((($_SERVER['port']==443 || $_SERVER['HTTP_X_FORWARDED_PORT']==443)?'https':'http')."://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'?_wsdl'); 
		$server->setClass("SOAPBridge",$this->Model,$this->View,$this->Controller); 
		$server->handle();		
	}
	
	protected function getFormattedParams(){
		$ret_arr=array();
		foreach ($this->Parameters as $Handler=>$Parameters) {
			foreach ($Parameters['optional'] as $Parameter=>$Format){
				if (is_array($Format)) {
					$ret_arr[$Handler]['optional'][$Parameter]="values [".implode(' ,',$Format)."]";
				}
				else {
					$ret_arr[$Handler]['optional'][$Parameter]=$Format;
				}				
			}
			foreach ($Parameters['required'] as $Parameter=>$Format){
				if (is_array($Format)) {
					$ret_arr[$Handler]['required'][$Parameter]="values [".implode(' ,',$Format)."]";
				}
				else {
					$ret_arr[$Handler]['required'][$Parameter]=$Format;
				}				
			}
		}	
		return $ret_arr;
	}
	
	protected function generateWSDL(){
		$URI=$this->SOAPURI;
		
		if ($this->SOAPEncode) {
			$encoding="<soap:body use='encoded' namespace='urn:$URI' encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>";;
		}
		else {
			$encoding="<soap:body use='literal'/>";
		}		
		if ($this->SOAPRPC) {
			$style='rpc';
		}
		else {
			$style='document';	
		}	
		
		$wsdl=utf8_encode("<?xml version ='1.0' encoding ='UTF-8' ?>
<definitions 
 name='$URI' 
 xmlns:tns='urn:$URI'
 xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
 xmlns:xsd='http://www.w3.org/2001/XMLSchema'
 xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
 xmlns='http://schemas.xmlsoap.org/wsdl/'> 
 <message name='".$URI."Request'>
  <part name='RequestName' type='xsd:string'/>
  <part name='JSONEncodedParameters' type='xsd:string'/>  
 </message>
 <message name='".$URI."Response'>
  <part name='Result' type='xsd:string'/>
 </message> 
<portType name='".$URI."PortType'>
 <operation name='Request'>
  <input message='tns:".$URI."Request'/>
  <output message='tns:".$URI."Response'/>
 </operation>    
</portType>
<binding name='".$URI."Binding' type='tns:".$URI."PortType'>
 <soap:binding style='$style' transport='http://schemas.xmlsoap.org/soap/http'/>  
 <operation name='Request'>
  <soap:operation soapAction='urn:$URI'/>
  <input>   
   $encoding
  </input>
  <output>
   $encoding
  </output>
  </operation>  
</binding>
<service name='".$URI."Service'>
 <port name='".$URI."Port' binding='".$URI."Binding'>
  <soap:address location='".(($_SERVER['port']==443 || $_SERVER['HTTP_X_FORWARDED_PORT']==443)?'https':'http')."://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']."'/>
 </port> 
</service>
</definitions>");
				
		return $wsdl;
	}
}
?>