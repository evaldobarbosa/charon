<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class BetweenFilterDecorator extends AbstractFilterDecorator {
	protected $value2;
	
	function setValue2( $value ) {
		$this->value2 = $value;
	}
	
	final function run() {
		return "{$this->field} BETWEEN {$this->value} AND {$this->value2}";
	}
}