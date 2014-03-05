<?php
namespace Charon;

use Charon\Metadata;
use Charon\Entity;
use Charon\JoinFKey;
use Charon\JoinRKey;

class Store {
	static public $instance;
	
	protected $metadata = array();
	
	function createClassByName($name) {
		if ( !class_exists($name) ) {
			throw new \Exception(
				sprintf(
					'Class not found: %s',
					$name
				)
			);
		}
	
		$obj = new $name;
	
		return $obj;
	}
	
	function addClass (Entity $e) {
		if ( !$this->hasClass($e->class) ) {
			$this->metadata[ $e->class ] = new Metadata( $e );
		}
	
		return $this;
	}
	
	function createAndAddClass($name) {
		$e = $this->createClassByName($name);
		$this->addClass($e);
		
		return $this->getMetadata($e->class);
	}
	
	function get($class) {
		if ( !$this->hasClass($class) ) {
			$this->createAndAddClass($class);
		}
		
		return $this->metadata[$class];
	}
	
	function findEntity(Metadata $md, $field) {
		$otherClass = null;
		$join = null;
	
		if ( $md->hasFKey($field)) {
			$class = $md->getFKey($field);
			
			$other = Store::me()->createAndAddClass( $class );
			if ( is_null($other) ) {
				$other = Store::me()->get( $class );
			}
			
			$join = new JoinFKey();
				$join->setFrom($md);
				$join->setTo($other);
		} else if ( $md->hasRKey($field) ) {
			$class = $md->getRKey($field);
			
			$other = Store::me()->createAndAddClass( $class );
			if ( is_null($other) ) {
				$other = Store::me()->get( $class );
			}
			
			$join = new JoinRKey();
				$join->setTo($md);
				$join->setFrom($other);
		} else {
			throw new \Exception( "{$field} throws an error" );
			//Notes::show( Store::me() );
		}
		$join->setAlias($field);
		
		return $join;
	}
	
	/**
	 * 
	 * @param string $class
	 * @return Charon\Metadata
	 */
	function getMetadata($class) {
		if ( $this->hasClass($class) ) {
			return $this->metadata[$class];
		}
	} 
	
	function hasClass($class) {
		return ( isset($this->metadata[$class]) );
	}
	
	function __toString() {
		$x = "========METADATA=======\n";
		$x .=	print_r($this->metadata, true);
		$x .= "========METADATA=======\n";
		return $x;
	}
	
	public static function getInstance() {
		if (!isset(self::$instance) && is_null(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
	 
		return self::$instance;
	}
	
	static function me() {
		return self::getInstance();
	}
}