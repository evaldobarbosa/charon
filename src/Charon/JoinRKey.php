<?php
namespace Charon;

use Charon\Join;

class JoinRKey extends Join {
	function getJoinFields() {
		return $this->from->mapFields($this->alias);
	}

	function getJoin() {
		$field = $this->from->getKeyByClass(
					$this->to->getInstance()->class
				);
		$source = ( !empty($this->source) )
			? $this->source
			: $this->to->getInstance()->tableName;

		return sprintf(
			"LEFT JOIN %s %s ON %s.%s_id = %s.id",
			$this->from->getInstance()->tableName,
			$this->alias,
			$this->alias,
			$field,
			$source
		);
	}
}