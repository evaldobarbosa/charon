<?php
namespace Charon;

use \stdClass;
use Charon\Metadata;
use Charon\JoinFKey;
use Charon\JoinRKey;

class JoinParser {
	private $fields = array();
	private $joins = array();
	
	function getFields() {
		return $this->fields;
	}
	
	function getJoins() {
		return $this->joins;
	}
	
	function process( Metadata $main, $joins ) {
		$this->fields = array();
		$this->joins = array();

		if (version_compare(phpversion(), '5.4.14', '<=')) {
		    foreach ($joins as $item) {
		    	$this->joins = array_merge(
					$this->joins,
					$this->splitToSql( $main, $item )
				);
		    }
		} else {
			array_walk($joins, function($item) use ( $main ) {
				$this->joins = array_merge(
					$this->joins,
					$this->splitToSql( $main, $item )
				);
			});
		}
		
		if ( count($this->fields) == 0 ) {
			$this->fields = $main->mapFields( $main->getInstance()->getAlias() );
		}
	}
	
	private function splitToSql( Metadata $from, $join ) {
		$sql = array();
		
		$to = Store::me()->findEntity($from,$join->alias);
		
		$this->fields = array_merge(
			$this->fields,
			$from->mapFields( $from->getInstance()->getAlias() )
		);
		
		$fields = ( is_a($to, "Charon\\JoinFKey") )
			? $to->getTo()->mapFields( $join->alias )
			: $to->getFrom()->mapFields( $join->alias );
			
		$this->fields = array_merge(
			$this->fields,
			$fields
		);
		
		$sql[] = $to->getJoin();
		
		$j = ( isset($join->next) )
				? $join->next
				: null;
		
		while ( isset($j) ) {
			$from = $this->getFrom($to,$j->alias);
			
			$to = Store::me()->findEntity($from,$j->alias);
			
			$fields = ( is_a($to, "Charon\\JoinFKey") )
				? $to->getTo()->mapFields( $j->alias )
				: $to->getFrom()->mapFields( $j->alias );
			
			$this->fields = array_merge(
				$this->fields,
				$fields
			);
			
			$sql[] = $to->getJoin();
			
			$j = ( isset($j->next) )
				? $j->next
				: null;
		}
		
		return $sql;
	}
	
	private function getFrom($to,$join_alias) {
		return ( is_a($to, "Charon\\JoinFKey") )
			? $to->getTo()
			: $to->getFrom();
	}
	
	private function updateFields(Join $join, $alias) {
		$to = ( is_a($join, "Charon\\JoinFKey") )
			? $join->getTo()->mapFields( $alias )
			: $join->getFrom()->mapFields( $join->getAlias() );
		
		$from = ( is_a($join, "Charon\\JoinFKey") )
			? $join->getFrom()->mapFields( $join->getAlias() )
			: $join->getTo()->mapFields( $alias );
		
		$this->fields = array_merge(
			$this->fields,
			$from
		);
	}
	
	function parse( Metadata $main, $path ) {
		$ex = explode("->", $path);

		$md = $main;
		
		$j = null;
		$j1 = null;
		foreach ($ex as $piece) {
			if ( is_null($j) ) {
				$j = new stdClass;
				$j->alias = $piece;
				$j->class = ( $md->hasFKey($piece) )
					? $md->getFKey($piece)
					: $md->getRKey($piece);
				
				$j1 = $j;
				
				$md = Store::me()->get($j->class);
				continue;
			}
			
			$current = new stdClass;
			$current->alias = $piece;
			if ( $md->hasFKey($piece) ) {
				$current->class = $md->getFKey($piece);
			} else if ( $md->hasRKey($piece) ) { 
				$current->class = $md->getRKey($piece);
			}
			$md = Store::me()->get($current->class);
				
			$j1->next = $current;
			$j1 = $j1->next;
		}

		return $j;
	}
}
