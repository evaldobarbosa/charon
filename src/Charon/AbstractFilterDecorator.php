<?php
namespace Charon;

use Charon\AbstractFilter;

abstract class AbstractFilterDecorator {
	protected $filter;
	protected $field;
	protected $value;
	
	final function __construct( AbstractAdapter $filter ) {
		$this->filter = $filter;
	}
	
	final function setValue( $field, $value ) {
		$this->field = $field;
		$this->value = $value;
	}
	
	abstract function run();
	
	final function __toString() {
		return $this->run();
	}
}