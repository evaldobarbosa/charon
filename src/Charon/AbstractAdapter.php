<?php
namespace Charon;

use Charon\Metadata;
use Charon\AbstractFilterDecorator;
use Charon\Join;

abstract class AbstractAdapter {
	protected  $rangeInit;
	protected  $rangeSize;
	private $terms = array();
	private $orderBy = array();
	private $lastWasOr = false;
	
	function createFilter( $filter ) {
		$filterClass =  __NAMESPACE__ . "\Filter\Decorator\\{$filter}FilterDecorator";
		return new $filterClass( $this );
	}
	
	private function addTerm( AbstractFilterDecorator $decorator, $groupId = null ) {
		if ( !is_null($groupId) ) {
			$this->terms[ $groupId ][] = $decorator;
		} else {
			$this->terms[] = $decorator;
		}
		$this->lastWasOr = ( is_a($decorator, __NAMESPACE__ . '\Filter\Decorator\OrFilterDecorator') );
	}
	
	function clear() {
		$this->rangeInit = 0;
		$this->rangeSize = 0;
		$this->terms = array();
		$this->orderBy = array();
		$this->lastWasOr = false;
	}
	
	function hasDecorator($filter) {
		$filter = __NAMESPACE__ . "\Filter\Decorator\\{$filter}FilterDecorator";
		return ( class_exists($filter) );
	}
	
	final function _And( $groupId = null ) {
		$dec = $this->createFilter('And');
		$this->addTerm( $dec, $groupId );
		return $this;
	}
	
	final function _Or( $groupId = null ) {
		$dec = $this->createFilter('Or');
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function beginsAt($num) {
		$this->rangeInit = $num;
		
		return $this;
	}
	
	final function range($num) {
		$this->rangeSize = $num;
		
		return $this;
	}
	
	final function equal( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
	
		$dec = $this->createFilter('Equal');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
	
		return $this;
	}
	
	final function notEqual( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
	
		$dec = $this->createFilter('NotEqual');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
	
		return $this;
	}
	
	final function startsWith( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('StartsWith');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function endsWith( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('EndsWith');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function contains( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('Contains');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	
	final function between( $field, $rangeIni, $rangeEnd, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('Between');
		$dec->setValue( $field, $rangeIni );
		$dec->setValue2( $rangeEnd );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function in( $field, array $value, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('In');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function greatherThan( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
		
		$dec = $this->createFilter('GreatherThan');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
		
		return $this;
	}
	
	final function greatherOrEqualThan( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
	
		$dec = $this->createFilter('GreatherThan');
		$dec->orEqual()->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
	
		return $this;
	}
	
	final function lessThan( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
	
		$dec = $this->createFilter('LessThan');
		$dec->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
	
		return $this;
	}
	
	final function lessOrEqualThan( $field, $value, $groupId = null ) {
		$this->addAnd($groupId);
	
		$dec = $this->createFilter('LessThan');
		$dec->orEqual()->setValue( $field, $value );
		$this->addTerm( $dec, $groupId );
	
		return $this;
	}
	
	final private function addAnd( $groupId = null ) {
		if ( count( $this->terms ) > 0 && !$this->lastWasOr ) {
			$this->_And( $groupId );
		}
	}
	
	final function asc($fieldname,$main=false) {
		$this->orderedBy( $fieldname, 'ASC', $main );
		return $this;
	}
	
	final function desc($fieldname,$main=false) {
		$this->orderedBy( $fieldname, 'DESC', $main );
		return $this;
	}
	
	final private function orderedBy( $fieldname, $orderType = 'ASC', $main=false ) {
		$order = new \stdClass();
			$order->field = "{$fieldname} {$orderType}";
			$order->isMain = $main;
			
		$this->orderBy[ $fieldname ] = $order;
		
		return $this;
	}
	
	final function getOrderBy($main=false) {
		$order = array();
		
		foreach( $this->orderBy as $key => $value ) {
			if ( $main !== $value->isMain ) {
				continue;
			}
			
			$order[] = $value->field;
		}

		return ( count($order) > 0 )
			? "ORDER BY " . implode(", ", $order)
			: null;
	}
	
	final function getFilter() {
		$filter = "";
		
		foreach( $this->terms as $key=>$term ) {
			if ( is_array( $term ) ) {
				$filter .= $this->processGroupOfTerms($term);
				continue;
			}
			$filter .= "\n\t" . $term->run();
		}
		
		if ( !empty($filter) ) {
			$filter = "\nWHERE" . $filter;
		}
		
		return $filter;
	}
	
	final private function processGroupOfTerms( array $group ) {
		$filter = "\n(";
		foreach( $group as $key=>$term ) {
			$filter .= "\n\t" . $term->run();
		}
		$filter .= "\n)";
		return $filter;
	}
	
	/**
	 * SQL Term based on SELECT.
	 * It is used for TOP/LIMIT
	 * @example SELECT * FROM... LIMIT 0,10 (mysql,pgsql,...)
	 * @example SELECT TOP 10 * FROM... (mssql)
	 * @return string
	 */
	abstract function selectHeader();
	abstract function rangeSentence();
}