<?php

namespace Telepedia\Extensions\Agora\Domain;

use ArrayObject;
use JsonSerializable;

class CommentCollection extends ArrayObject implements JsonSerializable {

	/**
	 * Parent comments, with their children (replies) nested within
	 * @var array
	 */
	private array $parents = [];


	public function __construct( array $comments = [] ) {
		$flatList = [];
		foreach ( $comments as $comment ) {
			$flatList[ $comment->getId() ] = $comment;
		}

		parent::__construct( $flatList );

		$this->build();
	}

	/**
	 * Build the collection, taking into account whether a comment has a parent
	 * and if so, appending it to its parent
	 * @return void
	 */
	private function build(): void {
		$iterator = $this->getIterator();

		foreach ( $iterator as $comment ) {
			$parentId = $comment->getParentId();

			if ( $parentId === null ) {
				// 'tis a parent
				$this->parents[] = $comment;
			} else {
				if ( $this->offsetExists( $parentId ) ) {
					$this->offsetGet( $parentId )->addChild( $comment );
				}

				// we reach here if the parent didn't exist. if that is the case, ignore
				// the comment - nothing we can do
			}
		}
	}

	public function withoutDeleted(): CommentCollection {
		return $this->filter(
			fn ( $comment ) => !$comment->isDeleted()
		);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_values( $this->parents );
	}

	/**
	 * Filter this collection
	 * @param callable $callback
	 * @return $this
	 */
	public function filter( callable $callback ): static {
		$filteredResults = [];
		foreach ( $this as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				$filteredResults[$key] = $value;
			}
		}
		return new static( $filteredResults );
	}
}