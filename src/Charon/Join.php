<?php
namespace Charon;

use Charon\Metadata;

abstract class Join {
	public $source;
	protected $alias;
	protected $from;
	protected $to;

	abstract function getJoinFields();
	abstract function getJoin();
	
	function setAlias($value) {
		$this->alias = $value;
	}
	
	function setTo(Metadata $md) {
		$this->to = $md;
	}
	
	function setFrom(Metadata $md) {
		$this->from = $md;
	}
	
	function getFrom() {
		return $this->from;
	}
	
	function getTo() {
		return $this->to;
	}
	
	function getAlias() {
		return $this->alias;
	}

	function __toString() {
		return $this->getJoin();
	}
	
	function hasEntity(Metadata $md) {
		return ( $this->to === $md || $this->from === $md );
	}
	
	function mapFields(Metadata $md,$alias) {
		$fields = $md->getFields();
		
		array_walk(
			$fields,
			function ( &$item, &$key, $prefix ) {
				$key = "{$prefix}__{$item}";
				$item = "{$prefix}.{$item} as {$prefix}__{$item}";
			},
			$alias
		);
		return array_combine($fields, $fields);
	}
}