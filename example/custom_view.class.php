<?php
/* Example of WService use. Extended View. Specified string-enumerator. Used for homogeneous elements */

/* Copyright © 2013 Eugene Kosarev <euko@ukr.net>
	*This work is free. You can redistribute it and/or modify it under the
	* terms of the Do What The Fuck You Want To Public License, Version 2,
	* as published by Sam Hocevar. See the COPYING file for more details.
*/

class CustomView extends WSView {
	public function __construct($OutputFormat){
		parent::__construct($OutputFormat);
		$this->setEnumerator(" n");
	}	
}



?>