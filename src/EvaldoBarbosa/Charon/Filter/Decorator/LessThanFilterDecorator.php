<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class LessThanFilterDecorator extends AbstractFilterDecorator {
	private $andEqual = false;
	
	function orEqual() {
		$this->andEqual = true;
		return $this;
	}
	
	final function run() {
		$op = ( $this->andEqual ) ? "<=" : "<";
		return "{$this->field} {$op} {$this->value}";
	}
}