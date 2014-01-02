<?php
namespace Charon\Filter;

use Charon\AbstractAdapter;

class MysqlAdapter extends AbstractAdapter {
	function selectHeader() {
		return "SELECT";
	}
	
	function rangeSentence() {
		if ( $this->rangeInit > 0 && $this->rangeSize > 0 ) {
			return "LIMIT {$this->rangeInit}, {$this->rangeSize}";
		} else if ( $this->rangeInit == 0 && $this->rangeSize > 0 ) {
			return "LIMIT {$this->rangeSize}";
		}
	}
}