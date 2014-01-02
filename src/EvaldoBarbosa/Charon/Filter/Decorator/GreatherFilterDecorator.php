<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class GreatherFilterDecorator extends AbstractFilterDecorator {
	private $andEqual = false;
	
	function orEqual() {
		$this->andEqual = true;
		return $this;
	}
	
	final function run() {
		return "UPPER({$this->field}) LIKE UPPER('%{$this->value}')";
	}
}