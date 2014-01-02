<?php
/* require "Store.php";
require "Metadata.php";
require "Loader.php";
require "Entity.php";
require "Join.php";
require "JoinFKey.php";
require "JoinRKey.php";

require "Entities/Sorteio.php";
require "Entities/Cliente.php";
require "Entities/Produto.php";
require "Entities/Habilitacao.php";

require "../Infra/Sanitize/Sanitizer.php"; */

//require "/home/evaldobarbosa/Projetos/Lucky/vendor/autoload.php";
require "/home/evaldo/Projetos/Lucky/vendor/autoload.php";// 0 41 98 8137 9355

use Charon\Notes as n;
use Charon\Store as s;
use Charon\Metadata;
use Charon\Entity;

$db = new \PDO('mysql:host=localhost;port=3306;dbname=lucky;charset=UTF8','root','root123');

date_default_timezone_set('America/Belem');
echo n::box('CALL');

$dl = new Charon\Loader( $db );
	$dl->load('Lucky\Model\Sorteio')
		->join('cupom->cliente')
		->join('produto->tagger->tag')
		->join('proprietario')
		->equal("sorteio->id",9)
		->desc("sorteio->id");
	
//	echo $dl->getSQL();
$rs = $dl->get(true);

//print_r($rs);
echo json_encode($rs);

/* $cliente = new Boo\Entities\Cliente($db);
	$cliente->find(1);
	
$produto = new Boo\Entities\Produto($db);
	$produto->find(1);

$sorteio = new Boo\Entities\Sorteio($db);
	$sorteio->find(6);
	$sorteio->delete();  
  
	$sorteio->realizacao = '2013-11-30 10:00:00';
	$sorteio->valor_cota = 2.76;
	$sorteio->qtd_cotas = 25;
	$sorteio->setProprietario( $cliente );
	$sorteio->setProduto( $produto );
	
	$sorteio->save();
	
	$sorteio->valor_cota = 2.79;
	
	$sorteio->save(); */
	
	//$sorteio->find(2);
	//$sorteio->valor_cota = 2.369;
	//$sorteio->save(); 
	
	