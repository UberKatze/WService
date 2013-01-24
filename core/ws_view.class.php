<?php
/**
* The Core of WService
* View. Creates output string in specified format from associative array.
* @version 0.1
* @author Eugene Kosarev 
*/
/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/

//TODO: add XML tags & attributes format check
class WSView{

	protected $OutputFormat=false;
	
	protected $Output='';
	
	protected $XMLRootTag='Root';

	protected $CSVDelimiter = ',';
	protected $CSVNextLine = "\n";
	protected $CSVCellBorder = '"';
	protected $CSVOnlyValues = true;
	
	protected $Enumerator=false;
	
	function __construct($OutputFormat){		
		$this->setOutputFormat($OutputFormat);
	}
	
	public function setOutputFormat($OutputFormat){
		switch ($OutputFormat){
			case 'XML':						
			case 'JSON':			
			case 'CSV':
			case 'PHPSerialized':			
				$this->OutputFormat=$OutputFormat;
			break;
			default:
				$this->OutputFormat='XML';
		}
	}
	
	public function generateOutput($Data,$PrintOutput=true){
		switch ($this->OutputFormat){
			case 'XML': 
				$this->GenerateXMLResponse($Data);
			break;
			case 'JSON':
				$this->GenerateJSONResponse($Data);
			break;
			case 'CSV':
				$this->GenerateCSVResponse($Data);
			break;
			case 'PHPSerialized':
				$this->GenerateSerializedResponse($Data);
			break;
		}
		if ($PrintOutput) print $this->Output;
		return $this->Output;
	}	
	
	public function setXMLRootTag($Tag){
		$this->XMLRootTag=$Tag;
	}
	
	public function setCSVOptions($Delimiter,$NextLine,$CellBorder,$OnlyValues) {
		$this->CSVDelimiter=$Delimiter;
		$this->CSVNextLine=$NextLine;
		$this->CSVCellBorder=$CellBorder;
		$this->CSVOnlyValues=$OnlyValues;
	}
	
	public function setEnumerator($Enumerator){
		$this->Enumerator=$Enumerator;
	}
	
	protected function GenerateJSONResponse($array) {
		$array=$this->PrepareDataRecursive($array);		
		$this->Output=json_encode($array);
	}
	
	protected function PrepareDataRecursive($data){		
		if (is_array($data)) {
			$ret_arr=array();
			foreach ($data as $key=>$value) {
				
				if ($this->Enumerator!=false) {						
					if (strstr($key,$this->Enumerator)) {
						$key=str_replace($this->Enumerator,'',$key);							
					}
				}
				
				if (is_array($value)) {					
					$ret_arr[$key]=$this->PrepareDataRecursive($value);			// Recursion
				}
				else {					
					$ret_arr[$key]=utf8_encode($value);
				}				
			}			
		}
		else {
			$ret_arr=utf8_encode($data);
		}			
		return $ret_arr;		
	}
	
	protected function GenerateXMLResponse($array_tags_values){				
		$xml='<?xml version="1.0" encoding="UTF-8"?>';		
		$xml.="<$this->XMLRootTag>";
		$xml.=$this->GenerateRecursiveXMLTagsFromArray($array_tags_values);
		if (strstr($this->XMLRootTag,' ')) $xml.="</".substr($this->XMLRootTag,0,strpos($this->XMLRootTag,' ')).">";
		else $xml.="</$this->XMLRootTag>";		
		$xml=utf8_encode($xml);
		$this->Output=$xml;		
	}
	
	protected function GenerateRecursiveXMLTagsFromArray($array){
		$retstring='';
		if (!is_array($array)) return '';
		foreach ($array as $key=>$element) {
			if ($this->Enumerator!=false) {
				if (strstr($key,$this->Enumerator)) $key=substr($key,0,strpos($key,$this->Enumerator));
			}
			$retstring.="<".$key.">";
			
			if (is_array($element)) $retstring.=$this->GenerateRecursiveXMLTagsFromArray($element); // Recursion
			else {
				if (stristr($element,'<![CDATA[') && strstr($element,']]>')) $retstring.=$element;
				else $retstring.= htmlentities($element,ENT_QUOTES,'UTF-8');				
			}
						
			if (strstr($key,' ')) $retstring.="</".substr($key,0,strpos($key,' ')).">";
			else $retstring.="</".$key.">";
		}
		return $retstring;
	}	
	
	protected function GenerateCSVRecursive($array){		
		$retstring='';
		if (!is_array($array)) return '';
		foreach ($array as $key=>$element) {
			if ($this->Enumerator!=false) {
				if (strstr($key,$this->Enumerator)) $key=str_replace($this->Enumerator,'',$key);
			}
			$retstring.=$this->CSVCellBorder.str_ireplace($this->CSVCellBorder,$this->CSVCellBorder.$this->CSVCellBorder,$key).$this->CSVCellBorder;
			
			if (is_array($element)) $retstring.=$this->CSVNextLine.$this->GenerateCSVRecursive($element); // Recursion
			else {
				$retstring.=$this->CSVDelimiter.$this->CSVCellBorder.str_ireplace($this->CSVCellBorder,$this->CSVCellBorder.$this->CSVCellBorder,$element).$this->CSVCellBorder;								
			}			
			if (next($array)) $retstring.=$this->CSVNextLine;			
		}
		return $retstring;
	}
	
	protected function GenerateCSVOnlyValuesRecursive($array){		
		$retstring='';
		if (!is_array($array)) return '';
		foreach ($array as $key=>$element) {			
			if (is_array($element)) $retstring.=$this->GenerateCSVOnlyValuesRecursive($element); // Recursion
			else {
				$retstring.=$this->CSVCellBorder.str_ireplace($this->CSVCellBorder,$this->CSVCellBorder.$this->CSVCellBorder,$element).$this->CSVCellBorder;
				if (next($array)) $retstring.=$this->CSVDelimiter;								
				else $retstring.=$this->CSVNextLine;
			}			
		}
		return $retstring;
	}
	
	protected function GenerateCSVResponse($array){
		if ($this->CSVOnlyValues) $this->Output=$this->GenerateCSVOnlyValuesRecursive($array);
		else $this->Output=$this->GenerateCSVRecursive($array);
	}
	
	protected function GenerateSerializedResponse($array){
		$array=$this->PrepareDataRecursive($array);
		$this->Output=serialize($array);
	}
	
}
?>