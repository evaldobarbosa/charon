<?php
namespace Charon;

class Connection {
	static private $instance;
	static private $classname = __CLASS__; 
	private $conn;
	
	function __construct() {
		if ( !defined("DSN") ) {
			throw new \Exception('Dados da conexão não informados');
		}
		
		$p = parse_url(DSN);

		$options = ( $p['scheme'] == 'mysql' )
			? [ \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ]
			: [];
		
		$this->conn = new \PDO(
			sprintf(
				"%s:host=%s;port=%d;dbname=%s",
				$p['scheme'],
				$p['host'],
				$p['port'],
				str_replace( '/', '', $p['path'] )
			),
			$p['user'],
			$p['pass'],
			$options
		);
	}
	
	function get() {
		return $this->conn;
	}
	
	static function me() {
		self::init();
	
		return self::$instance;
	}
	
	private static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self::$classname;
		}
	}
}
