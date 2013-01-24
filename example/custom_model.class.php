<?php
/* Example of WService use. Extended Model. Two simple handlers. */

/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/

class CustomModel extends WSModel {
			
	protected function getPiHandler(){
		if (isset($this->Parameters['round'])) return array ('PI'=>round(pi(),$this->Parameters['round']));
		else return array ('PI'=>pi());
	}
	
	protected function echoDatesHandler(){
		return array('Date n1'=>$this->Parameters['date1'],'Date n2'=>$this->Parameters['date1']);		
	}
}

?>