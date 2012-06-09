<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/Br.php');

class BrException extends Exception {

}

if (!DEFINED("E_STRICT")) {
	DEFINE("E_STRICT", 2048);
}
if (!DEFINED("E_DEPRECATED")) {
	DEFINE("E_DEPRECATED", 8192);
}

class BrErrorException extends ErrorException {

	private $errorTypes = array(
	  E_ERROR           => "Error"
	, E_WARNING         => "Warning"
	, E_PARSE           => "Parsing Error"
	, E_NOTICE          => "Notice"
	, E_CORE_ERROR      => "Core Error"
	, E_CORE_WARNING    => "Core Warning"
	, E_COMPILE_ERROR   => "Compile Error"
	, E_COMPILE_WARNING => "Compile Warning"
	, E_USER_ERROR      => "User Error"
	, E_USER_WARNING    => "User Warning"
	, E_USER_NOTICE     => "User Notice"
	, E_STRICT          => "Runtime Notice"
	, E_DEPRECATED      => "Deprecated"
	);

	public function getType() {
		
		return br($this->errorTypes, $this->getSeverity(), 'Unknown Error');
		
	}
	
	public function isFatal() {
		
		return (($this->getSeverity() == E_ERROR) || ($this->getSeverity() == E_USER_ERROR));
		
	}

}

class BrCallStackException extends BrException {

	function __construct() {

		parent::__construct('Callstack');

	}

}

class BrExceptionNotImplemented extends BrException {

	function __construct() {

		parent::__construct('Feature not implemented');

	}

}

class BrAssertException extends BrException {

	function __construct($message) {

		parent::__construct($message ? $message : 'Assertion error');

	}

}

