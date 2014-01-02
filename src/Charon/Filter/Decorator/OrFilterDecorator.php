<?php
namespace Charon\Filter\Decorator;

use Charon\AbstractFilterDecorator;

class OrFilterDecorator extends AbstractFilterDecorator {
	final function run() {
		return "OR";
	}
}