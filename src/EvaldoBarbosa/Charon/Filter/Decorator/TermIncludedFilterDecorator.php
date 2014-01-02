<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class TermIncludedFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		return "UPPER({$this->field}) LIKE UPPER('%{$this->value}%')";
	}
}