<?php
namespace Charon;

use Charon\Entity;

abstract class AbstractJsonDecorator {
	abstract function execute(Entity $e);
}