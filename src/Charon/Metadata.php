<?php
namespace Charon;

use \ReflectionClass;
use Charon\Store as s;

class Metadata {
	public $instance;
	private $fields = array();
	private $fkeys = array();
	private $rkeys = array();
	private $notshow = array();
	
	public $getters = array();
	public $setters = array();
	public $adders = array();
	public $methods = array();
	
	function __construct(Entity $e) {
		$this->instance = $e;
		
		$ref = new ReflectionClass( $e );
		
		$properties = $ref->getProperties( \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED );
		
		foreach( $properties as $prop ) {
			$this->reflectProperty($prop);
		}
		
		$methods = $ref->getMethods( \ReflectionMethod::IS_PUBLIC );
		foreach( $methods as $method ) {
			$this->methods[] = $method->getName();
		}
		
		unset($ref);
	}
	
	private function reflectProperty(\ReflectionProperty $p) {
		if ( !$this->parseDoc($p) ) {
			$this->fields[ $p->getName() ] = $p->getName();
		}
	}
	
	private function parseDoc(\ReflectionProperty $p) {
		$isAKey = false;
		
		$matches = array();
		preg_match_all( '/@rk (.*)/', $p->getDocComment(), $matches );
	
		if ( count($matches) == 2 && !empty($matches[1]) ) {
			if ( $matches[1][0][0] == '\\' ) {
				$matches[1][0] = substr($matches[1][0], 1);
			}
			
			$this->rkeys[ $p->getName() ] = $matches[1][0];
			$this->instance->isARKey($p->getName());
			$isAKey = true;
		}
	
		$matches = array();
		preg_match_all( '/@fk (.*)/', $p->getDocComment(), $matches );
	
		if ( count($matches) == 2 && !empty($matches[1]) ) {
			if ( $matches[1][0][0] == '\\' ) {
				$matches[1][0] = substr($matches[1][0], 1);
			}
			
			$this->fkeys[$p->getName()] = $matches[1][0];
			$isAKey = true;
		}
		
		$matches = array();
		preg_match_all('/@notshow/', $p->getDocComment(), $matches);
		
		if ( in_array('@notshow', $matches[0]) ) {
			$this->notshow[] = $p->getName();
		}
		
		$matches = array();
		preg_match_all('/@setter (.*)/', $p->getDocComment(),$matches);
		
		if ( count($matches) == 2 && !empty($matches[1]) ) {
			$this->setters[$p->getName()] = $matches[1][0];
			$isAKey = true;
		}
		
		$matches = array();
		preg_match_all('/@getter (.*)/', $p->getDocComment(),$matches);
		
		if ( count($matches) == 2 && !empty($matches[1]) ) {
			$this->getters[$p->getName()] = $matches[1][0];
			$isAKey = true;
		}
		
		$matches = array();
		preg_match_all('/@adder (.*)/', $p->getDocComment(),$matches);
		
		if ( count($matches) == 2 && !empty($matches[1]) ) {
			$this->adders[$p->getName()] = $matches[1][0];
			$isAKey = true;
		}
		
		return $isAKey;
	}
	
	function getKeyByClass($class) {
		$fkey = array_search($class, $this->fkeys);
		$rkey = array_search($class, $this->rkeys);
		
		return ( empty($fkey) )
			? $rkey
			: $fkey;
	}
	
	function getKey($field) {
		if ( isset($this->fkeys[$field]) ) {
			return $this->fkeys[$field];
		} else if ( isset($this->rkeys[$field]) ) {
			return $this->rkeys[$field];
		} else {
			throw new \Exception("Key '{$field}' not exists on '{$this->instance->class}'");
		}
	}
	
	function getFields() {
		return $this->fields;
	}
	
	function hasField($field) {
		return ( isset($this->fields[$field]) );
	}
	
	function hasMethod($key) {
		return ( in_array($key, $this->methods) );
	}
	
	function hasFKey($field) {
		return ( isset($this->fkeys[$field]) );
	}
	
	function hasRKey($field) {
		return ( isset($this->rkeys[$field]) );
	}
	
	function getFKey($field) {
		return $this->fkeys[$field];
	}
	
	function getRKey($field) {
		return $this->rkeys[$field];
	}
	
	function getAllFKeys() {
		return $this->fkeys;
	}
	
	function getAllRKeys() {
		return $this->rkeys;
	}
	
	function getAllFKeyNames() {
		return array_keys($this->fkeys);
	}
	
	function getAllRKeyNames() {
		return array_keys($this->rkeys);
	}
	
	function getMethod($listOfMethods,$name,$alias) {
		if ( !isset($listOfMethods[$name]) ) {
			$class = $this->getInstance()->class;
			
			throw new \Exception("{$class}: {$alias} not found. Please check annotation for field '{$name}'.");
		}
		
		return $listOfMethods[$name];
	}
	
	function getSetter($v) {
		return $this->getMethod(
			$this->setters,
			$v,
			'Setter'
		);
	}
	
	function getGetter($v) {
		return $this->getMethod(
			$this->getters,
			$v,
			'Getter'
		);
	}
	
	function getAdder($v) {
		return $this->getMethod(
			$this->adders,
			$v,
			'Adder'
		);
	}
	
	function notShowField($name) {
		return ( in_array($name, $this->notshow) );
	}
	
	function getInstance() {
		return $this->instance;
	}
	
	function mapFields($alias) {
		$fields = $this->getFields();
		
		$fk = $this->getAllFKeys();
		$newFk = array();
		array_walk($fk, function(&$item,&$key) use ($alias,&$newFk) {
			$newFk[ "{$alias}_{$key}_id" ] = "{$key}_id";
		});
		
		$fields = array_merge($fields,$newFk);
		
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
	
	function cloneIt($id=null) {
		$new = clone $this->instance;
		$new->id = $id;
		return $new;
	}
}