<?php
/* Example of WService use. Extended Controller. Date format check added. */

/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/
class CustomController extends WSController {
	/* Validate value by Format*/
	protected function checkFormat($Value,$Format){
		if (parent::checkFormat($Value,$Format)){			
			if ($Format=='dd-mm-yyyy'){							
				$RegExp='^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[012])-(19|20)\d\d$^';
				if (preg_match($RegExp,$Value)==false) return false;
				else return true;
			}
		}
		else {
			return false;
		}
		return true;
	}
	
}
?>