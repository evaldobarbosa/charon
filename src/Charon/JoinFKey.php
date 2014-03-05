<?php
namespace Charon;

use Charon\Join;

class JoinFKey extends Join {	
	function getJoinFields() {
		return $this->to->mapFields($this->alias);
	}

	function getJoin() {
		return sprintf(
				"/**/LEFT JOIN %s %s ON %s.id = %s.%s_id",
				$this->to->getInstance()->tableName,
				$this->alias,
				$this->alias,
				//$this->from->getInstance()->tableName,
				$this->source,
				$this->alias
		);
	}
}