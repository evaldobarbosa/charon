<?php
namespace Charon;

use \PDO;
use Charon\Store as s;
use Sanitize\Sanitizer as snt;

abstract class Entity {
	private $conn;
	private $class;
	private $shortName;
	private $table;
	private $alias;
	private $jsonDecorator;
	
	protected $id;
	
	abstract function validate();
	
	function __construct(\PDO $conn = null ) {
		if ( !is_null($conn) ) {
			$this->setConn( $conn );
		}

		$this->class = get_class($this);
		
		$ex = explode("\\",$this->class);
		
		$this->shortName = $ex[ count($ex)-1 ];
		$this->table = strtolower($this->shortName);
		
		$this->alias = $this->table;
		
		unset($ex);
	}
	
	function setConn(\PDO $conn) {
		$this->conn = $conn;
		
		return $this;
	}

	function getConn() {
		if ( is_null($this->conn) ) {
			$this->conn = Connection::me()->get();
		}

		return $this->conn;
	}
	
	function setId($value) {
		$this->id = snt::add('int', 'id')->sanitize($value);
		
		return $this;
	}
	
	function setAlias($value) {
		$this->alias = $value;
		
		return $this;
	}
	
	function getAlias() {
		return $this->alias;
	}
	
	function __call($key,$arguments) {
		$field = strtolower($key);
		$meta = $this->getMetadata();
		
		if ( $field == "count" ) {
			if ( $meta->hasRKey( $arguments[0] ) ) {
				return count( $this->{$arguments[0]});
			}
		}
		
		$field = preg_replace("/^(get)/", "", $key);
		
		if ( $meta->hasField($field) || $meta->hasFKey($field) || $meta->hasRKey($field) ) {
			return $this->{$field};
		}
		
		return $this;
	}
	
	function __get($key) {
		switch ($key) {
			case 'alias':
				return $this->alias;
				
			case 'className':
				return $this->shortName;
				
			case 'class':
				return $this->class;
				
			case 'id':
				return $this->id;
				
			case 'table':
			case 'tableName':
				return $this->table;
			default:
				$yeah = (
					$this->getMetadata()->hasField($key) ||
					$this->getMetadata()->hasFKey($key) ||
					$this->getMetadata()->hasRKey($key)
				);
				if ( $yeah ) {
					return $this->{$key};
				}
		}
	}
	
	function __set($key,$value) {
		switch ($key) {
			case 'id':
				$this->setId( $value );
				return;
		}
		
		if ( $this->getMetadata()->hasMethod($key) ) {
			$this->{$key}( $value );
		} else if ( $this->getMetadata()->hasField($key) ) {
			$this->{$key} = $value; 
		}
	}
		
	function getMetadata() {
		if ( !s::me()->hasClass($this->class) ) {
			s::me()->createAndAddClass($this->class);
		}
		return s::me()->get( $this->class );
	}
	
	function loadValues($fields) {
		$myFields = $this->getMetadata()->getFields();
	
		if (version_compare(phpversion(), '5.4.14', '<=')) {
			foreach ($myFields as $key=>$item) {
				if ( isset( $fields[$key] ) ) {
					$this->{$key} = $fields[$key];
				}
			}
		} else {
			array_walk($myFields, function($item,$key) use ($fields) {
				if ( isset( $fields[$key] ) ) {
//					echo "{$this->table}.{$key} => {$fields[$key]} \n";
					$this->{$key} = $fields[$key];
				}
			});
		}
	}
	
	function loadFieldValues($record) {
		$myFields = $this->getMetadata()->getFields();
		$termo = "{$this->alias}__";
		$tam = strlen($termo);
		
		if (version_compare(phpversion(), '5.4.14', '<=')) {
		    foreach ($myFields as $key=>$item) {
		    	if ( isset( $record[ "{$termo}{$key}" ] ) ) {
					$this->{$key} = $record[ "{$termo}{$key}" ];
				}
		    }
		} else {
			array_walk($myFields, function($item, $key) use ($record, $termo) {
				$fieldname = "{$termo}{$key}";
				if ( isset( $record[ $fieldname ] ) ) {
					$this->{$key} = $record[ $fieldname ];
				}
			});
		}
	}
	
	function find( $id, $attachRK=false ) {
		$loader = new Loader($this->getConn());
		
		$sql = $loader->load($this->class)->getSQL();
		$sql .= " WHERE id = :id";
		
		$ds = $this->getConn()->prepare($sql);
		$ds->bindParam(':id', $id, PDO::PARAM_INT);
		
		$ds->execute();
		
		$rs = $ds->fetchAll(PDO::FETCH_ASSOC);
		
		if ( isset($rs[0]["{$this->alias}__id"]) ) {
			$this->id = $rs[0]["{$this->alias}__id"];
			$this->loadFieldValues( $rs[0] );
		
			/**
			 * @todo Implementar rotina de lazyload
			 */
			//$mr = new MapReduce( s::me()->get($this->class), $rs, $joins);
		} else {
			throw new \Exception("{$this->shortName}: Record not found");
		}
		
		unset($ds,$rs,$loader);
		
		return $this;
	}
	
	function save() {
		$this->validate();
		
		if ( (int)$this->id == 0 ) {
			$this->insert();
		} else {
			$this->update();
		}
		
		return $this;
	}
	
	private function getFieldsToSave() {
		//$fields = s::me()->getMetadata($this->class)->getFields();
		$fields = $this->getMetadata()->getFields();
			unset($fields['id']);
			
		$keys = s::me()->getMetadata($this->class)->getAllFKeys();
		
		array_walk(
			$keys,
			function( &$item, &$key ) {
				$item = "{$key}_id";
			}
		);
		return array_merge($fields,$keys);
	}
	
	private function execPost( $sql, $fields, $bindId=false ) {
		$ds = $this->getConn()->prepare($sql);
		
		foreach ( $fields as $key=>$value ) {
			if ( is_object( $this->{$key} ) ) {
				$ds->bindParam(":{$value}", $this->{$key}->id, PDO::PARAM_INT);
			} else {
				/**
				 * @todo Criar tipagem por anotação
				 */
				if ( is_int($this->{$key}) ) {
					$ds->bindParam(":{$value}", $this->{$key}, PDO::PARAM_INT);
				} else {
					$ds->bindParam(
						":{$value}",
						$this->{$key},
						PDO::PARAM_STR
					);
				}
			}
		}
		
		if ( $bindId ) {
			$ds->bindParam(":id", $this->id, PDO::PARAM_INT);
		}
		$ds->execute();
	}
	
	private function insert() {
		$fields = $this->getFieldsToSave();
		
		$sql = sprintf(
			"INSERT INTO %s (\n\t%s\n) VALUES (\n\t:%s\n);",
			$this->table,
			implode(", ", $fields),
			implode(", :", $fields)
		);
		
		$this->execPost($sql,$fields);
		
		$this->id = $this->getConn()->lastInsertId();
	}
	
	private function update() {
		$fields = $this->getFieldsToSave();
		
		$_fields = $fields;
		array_walk(
			$_fields,
			function (&$item) {
				$item = "{$item} = :{$item}";
			}
		);
		
		$sql = sprintf(
			"UPDATE %s SET \n\t%s\nWHERE id = :id;",
			$this->table,
			implode(", ", $_fields)
		);
		
		$this->execPost($sql,$fields,true);
		
		//$this->id = $this->conn->lastInsertId();
	}
	
	function delete() {
		$sql = "DELETE FROM {$this->table} WHERE id = :id";
		
		$this->execPost($sql,array(),true);
		
		$this->id = $this->getConn()->lastInsertId();
	}
	
	function getConnection() {
		return $this->conn;
	}
	
	function isARKey($key) {
		if ( !is_array($this->{$key}) ) {
			$this->{$key} = array();
		}
	}
	
	function setJsonDecorator( JsonDecorator $jd ) {
		$this->jsonDecorator = $jd;
	}
}
