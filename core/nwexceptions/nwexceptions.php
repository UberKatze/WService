<?php
 
class NoticeException extends Exception { 
    public function __toString() {
        return  "Notice: {$this->message} {$this->file} on line {$this->line}\n";
    }
}
 
class WarningException extends Exception { 
    public function __toString() {
        return  "Warning: {$this->message} {$this->file} on line {$this->line}\n";
    }
}

function nw_error_handler($errno, $errstr) {    
	if($errno == E_WARNING) {
        throw new WarningException($errstr,$errno);
    } 
    if($errno == E_NOTICE) {
        throw new NoticeException($errstr,$errno);
    }        
    throw new Exception($errstr,$errno);
}