<?php
namespace Charon;

use \stdClass;
use \PDO;
use Charon\JoinParser;
use Charon\Store as s;
use Charon\Metadata;
use Charon\Entity;
use Charon\Filter\MysqlAdapter;
use Charon\MapReduce;

class Loader {
	private $namespace;
	private $conn;
	private $parser;
	
	private $main;
	protected $fields = array();
	protected $joins = array();
	
	protected $filter;
	protected $recordset;
	protected $collection;
	
	function __construct(\PDO $conn) {
		$this->conn = $conn;
		
		$this->createFilter();
		
		$this->parser = new JoinParser();
	}
	
	private function createFilter() {
		switch ( $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME) ) {
			case 'mysql':
				$this->filter = new MysqlAdapter();
				break;
		}
	} 
	
	function load($class) {
		if ( !s::me()->hasClass($class) ) {
			s::me()->addClass(
				s::me()->createClassByName($class)
			);
		}
		$this->main = s::me()->get($class);
		
		$this->fields = $this->main->mapFields( $this->main->getInstance()->alias );
		
		$this->getFilter()->clear();
		
		$this->joins = array();
		$this->recordset = array();
		$this->collection = array();
	
		return $this;
	}
	
	function join($path) {
		$p = $this->parser->parse($this->main,$path);
		
		$this->joins[ $p->alias ] = $p;
		
		return $this;
	}
	
	function get($asJson=false) {
		$this->getFilter()->beginsAt(0)->range(1);
		
		$rs = $this->getAll($asJson);
		
		$obj = current($rs);
		return $obj;
	}
	
	function getAll($asJson=false) {
		$sql = $this->conn->prepare(
			$this->getSQL()
		);
		
		$sql->execute();
		
		$this->recordset = $sql->fetchAll(PDO::FETCH_ASSOC);
		
		echo "<pre style='border: 1px solid #000;background-color: #CCC;'><h3>Debug</h3><hr/>",
			print_r($this->recordset,true),
			"</pre>";
		
		$fields = $this->main->getFields();
		
		$mr = new MapReduce($this->main, $this->recordset, $this->joins);
		
		$this->collection = $mr->getCollection($asJson);
		//echo "<pre>", json_encode($this->collection), "</pre>";
		
		unset($mr);
		
		return $this->collection;
	}
	
	function addToCollection($entity) {
		if ( !isset( $this->collection[ $entity->id ] ) ) {
			$this->collection[ $entity->id ] = $entity;
		}
	}
	
	function getFromColletion($id) {
		if ( isset($this->collection[$id]) ) {
			return $this->collection[$id];
		} else {
			return $this->main->getInstance();
		}
	}
	
	function __call($method,$args) {
		switch ( $method ) {
			case "beginsAt":
			case "range":
				$this->filter->{$method}($args[0]);
				
				break;
			case "asc":
			case "desc":
				//$origin = $this->getMetadataFromJoins($args[0]);
				$origin = str_replace("->", ".", $args[0]);
					
				$main = ( isset($args[1]) )
					? $args[1]
					: false;
					
				$this->filter->{$method}($origin,$main);
				
				break;
			default:
				if ( $this->filter->hasDecorator( ucfirst($method) ) ) {
					//$origin = $this->getMetadataFromJoins($args[0]);
					$origin = str_replace("->", ".", $args[0]);
						
					$arg3 = ( isset($args[2]) )
						? $args[2]
						: null;
						
					$method = ucfirst($method);
					$this->filter->{$method}(
						$origin,
						$args[1],
						$arg3
					);
				}
		}
		
		return $this;
	}
	
	function getFilter() {
		return $this->filter;
	}
	
	function getSQL() {
		$this->parser->process( $this->main, $this->joins );
		$fields = $this->parser->getFields();
		$joins = $this->parser->getJoins();
		
		$sql  = "SELECT \n\t";
		$sql .= implode(
			",\n\t",
			$fields
		);
		$sql .= "\nFROM ( ";
		$sql .= "SELECT * FROM {$this->main->getInstance()->tableName} ";
		
		$filter1 = $this->filter->getOrderBy(true);
		if ( !empty($filter1) ) {
			$sql .= "{$filter1} ";
			$sql .= "{$this->filter->rangeSentence()} ";
		}
		
		$sql .= ") AS {$this->main->getInstance()->tableName}\n";
		
		$sql .= implode(
			"\n",
			$joins
		);
		
		$sql .= $this->filter->getFilter();
		
		$filter2 = $this->filter->getOrderBy();
		if ( !empty($filter2) ) {
			$sql .= "\n{$filter2}";
		}
		
		return $sql;
	}
}