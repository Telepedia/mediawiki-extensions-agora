<?php

namespace Telepedia\Extensions\Agora\Domain;

use ArrayObject;

class CommentCollection extends ArrayObject {

	public function __construct( $results ) {
		foreach ( $results as $row ) {
			$this[ $row->id ] = $row;
		}
		parent::__construct();
	}
}