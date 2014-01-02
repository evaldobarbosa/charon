<?php
namespace Charon;

class ClassBuilder {
	private $name;
	private $namespace;
	private $fields;
	private $createSetters = false;
	
	function __construct($info) {
		foreach ( $info as $info ) {
			$ex = explode("=",$info);
			
			if ( $ex[0] == "class" ) {
				$ex1 = explode("\\", $ex[1]);
				
				if ( count($ex1) > 1 ) {
					$this->name = $ex1[ count($ex1)-1 ];
					unset( $ex1[ count($ex1)-1 ] );
					$this->namespace = implode( "\\", $ex1 );
				} else {
					$this->name = $ex[1];
				}
			}
			
			if ( $ex[0] == "fields" ) {
				$fields= $ex[1];
				
				$ex1 = explode(",",$fields);
				
				array_walk($ex, function(&$item,$key) {
					$item = trim($item);
				});
				$this->fields = $ex1;
				
				unset($ex1);
			}
			
			if ( $ex[0] == "namespace" ) {
				$arr = preg_split('/(?=[A-Z])/',$ex[1]);
				$arr = array_filter( $arr, function( $item ) {
					return ( strlen( trim($item) ) > 0 ); 
				});
				
				$this->namespace = implode("\\",$arr);
			}
			
			unset($ex);
		}
	}
	
	private function createDirectory() {		
		$myNamespace = str_replace( "\\", "/", __NAMESPACE__ );
		$thisNamespace = str_replace( "\\", "/", $this->namespace );
		
		$newDir = str_replace( $myNamespace, $thisNamespace, __DIR__ );
		
		if ( !file_exists($newDir) ) {
			mkdir($newDir,0775,true);
		}
		
		return $newDir;
	}
	
	function run() {
		$path = $this->createDirectory();
		$table = strtolower($this->name);
		
		$contents = array();
		
		$contents[] = "<?";
		$contents[] = "namespace {$this->namespace};";
		
		$contents[] = "";
		$contents[] = "use Infra\DB\AbstractEntity;";
		
		$contents[] = "";
		$contents[] = "class {$this->name} extends AbstractEntity {";
		
		foreach( $this->fields as $field ) {
			$contents[] = "\tprotected \${$field};";
		}
		
		foreach( $this->fields as $field ) {
			$f = ucfirst($field);
			$contents[] = "";
			$contents[] = "\tfunction set{$f}(\$value) {";
			$contents[] = "\t\t\$this->{$field} = \$value;";
			$contents[] = "\t}";
		}
		
		$contents[] = "";
		$contents[] = "\tfunction validate() {";
		$contents[] = "\t\treturn true;";
		$contents[] = "\t}";
		
		$contents[] = "";
		$contents[] = "\tfunction insert() {";
		
		$fields = array_filter($this->fields,function($item) {
			return ( $item !== "id" );	
		});
		
		$contents[] = "\t\t\$stmt = \$this->conn->prepare(";
		$contents[] = "\t\t\t'INSERT INTO {$table} (";
		$contents[] = "\t\t\t	" . implode(", ", $fields);
		$contents[] = "\t\t\t) VALUES (";
		$contents[] = "\t\t\t	:" . implode(", :", $fields);
		$contents[] = "\t\t\t);'";
		$contents[] = "\t\t);";
		
			foreach( $fields as $field ) {
				$contents[] = "\t\t\$stmt->bindValue(':{$field}', \$this->{$field}, \PDO::PARAM_STR);";
			}
		
		$contents[] = "";
		$contents[] = "\t\treturn \$stmt;";
		$contents[] = "\t}";
		
		array_walk($fields,function(&$item,$key) {
			$item = "{$item} = :{$item}";
		});
		
		$contents[] = "";
		$contents[] = "\tfunction update() {";
			$contents[] = "\t\t\$stmt = \$this->conn->prepare(";
			$contents[] = "\t\t\t'UPDATE {$table} SET";
					$contents[] = "\t\t\t	" . implode(", ", $fields);
			$contents[] = "\t\t\t\tWHERE id = :id;";
			$contents[] = "\t\t\t);'";
			$contents[] = "\t\t);";
		
			foreach( $this->fields as $field ) {
				$contents[] = "\t\t\$stmt->bindValue(':{$field}', \$this->{$field}, \PDO::PARAM_STR);";
			}
			$contents[] = "\t\t\$stmt->bindValue(':id', \$this->id, \PDO::PARAM_INT);";
		
		$contents[] = "";
		$contents[] = "\t\treturn \$stmt;";
		$contents[] = "\t}";
		
		$contents[] = "";
		$contents[] = "\tfunction load(\$row) {";
			foreach( $this->fields as $field ) {
				$contents[] = "\t\t\$this->{$field} 		= \$row['{$table}__{$field}'];";
			}
		
		$contents[] = "\t}";
		
		$contents[] = "}";
		
		print_r($contents);
		
		file_put_contents( "{$path}/{$this->name}.php", implode("\n", $contents) );
	}
}

$builder = new ClassBuilder( $argv );
$builder->run();