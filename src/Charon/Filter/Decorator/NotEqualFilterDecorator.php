<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class NotEqualFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		if ( is_string($this->value) ) {			
			return "UPPER({$this->field}) <> UPPER('{$this->value}')";
		} else {
			return "{$this->field} <> '{$this->value}'";
		}
	}
}