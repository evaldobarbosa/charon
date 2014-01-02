<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class AndFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		return "AND";
	}
}