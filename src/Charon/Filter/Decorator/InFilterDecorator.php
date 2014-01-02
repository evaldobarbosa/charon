<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class InFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		$val = implode("','", $this->value);
		return "UPPER({$this->field}) LIKE UPPER('%{$val}')";
	}
}