<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class ContainsFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		return "UPPER({$this->field}) LIKE UPPER('%{$this->value}%')";
	}
}