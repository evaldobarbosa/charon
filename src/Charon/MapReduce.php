<?php
namespace Charon;

use Charon\Metadata;
use Charon\Entity;

class MapReduce {
	private $collection;
	private $reduce;
	private $map = array();
	private $main;
	private $joins;
	
	function __construct(Metadata $main, $data,$joins) {
		$this->main = $main;
		$this->joins = $joins;
		$this->collection = array();
		$this->reduce = array();
		
		if ( count($data) == 0 ) {
			throw new \Exception("You should give a valid recordset");
		}
		
		array_walk(
			$data,
			function($value) {
				$record = (array)$value;
				
				array_walk($value, array($this,'mapper'), $record);
			}
		);
		
		array_walk(
			$this->map[ $main->getInstance()->getAlias() ],
			array($this,'reducer')
		);
		
		$this->map = array();
	}
	
	function mapper ($value, &$key, $fields) {
		$ex = explode("__",$key);
	
		$id = ( isset( $fields[ "{$ex[0]}__id" ] ) )
			? $fields[ "{$ex[0]}__id" ]
			: null;
	
		$this->map[ $ex[0] ][ $id ][ $ex[1] ] = $value;
	
		unset($ex);
	}
	
	function reducer( $value, $key ) {
		$mainObj = $this->main->cloneIt($key);
			$mainObj->loadValues( $value );
		
		$this->addToCollection( $mainObj );
		
		array_walk($this->joins, function($join) use (&$value,&$mainObj) {
			$this->joinCascade( $mainObj, $value, $join, $mainObj->getAlias() );
		});
		
		$this->collection[ $mainObj->id ] = $mainObj;
		$this->reduce[ $mainObj->id ] = $value;
	}
	
	private function joinCascade( Entity $e, &$source, $join, $current ) {
		$md = $e->getMetadata();
		
		if ( $md->hasFKey($join->alias) ) {
			$class = $md->getFKey($join->alias);
			$joinType = 'fk';
		} else if ( $md->hasRKey($join->alias) ) {
			$class = $md->getRKey($join->alias);
			$joinType = 'rk';
		} else {
			$joinType = 'fk';
		}
		
		switch ( $joinType ) {
			case 'fk':
				$id = $source["{$join->alias}_id"];
					
				$obj = $this->map[ $join->alias ][ $id ];
				
				//$nextEnt = Store::me()->get($class)->getInstance();
				$nextEnt = Store::me()->get($class)->cloneIt($id);
				$nextEnt->setAlias( $join->alias )->loadValues( $obj );
					
				if ( isset($join->next) ) {
					$this->joinCascade( $nextEnt, $obj, $join->next, $join->alias );
				}
				
				$method = $md->getSetter( $join->alias );
				$e->{$method}( $nextEnt );
				$source[ $join->alias ] = $obj;
				
				break;
			case 'rk':
				
				$obj = array_filter(
					$this->map[ $join->alias ],
					function( $item ) use ( $source, $current ) {
						return ( $item["{$current}_id"] == $source['id'] );
					}
				);
				
				if ( isset($join->next) ) {
					array_walk(
						$obj,
						function( &$item ) use ( $join, $class, $md, $e ) {
							$nextEnt = Store::me()->get($class)->getInstance();
							$nextEnt->setAlias( $join->alias )->loadValues( $item );
							
							$this->joinCascade( $nextEnt, $item, $join->next, $join->alias );
							
							$method = $md->getAdder( $join->alias );
							$e->{$method}( $nextEnt );
						}
					);
				}
				
				$source[ $join->alias ] = $obj;
				
				break;
		}
	}
	
	function &addToCollection( Entity $obj ) {
		$xObj = (object)$obj;
		
		if ( !isset( $this->collection[ $xObj->id ] ) ) {
			$this->collection[ $xObj->id ] = $obj;
		}
		
		return $this->collection[ $xObj->id ];
	}
	
	function getCollection($asJson=false) {
		return ( !$asJson)
			? $this->collection
			: $this->reduce;
	}
}