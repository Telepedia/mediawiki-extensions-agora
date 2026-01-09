<?php

namespace Telepedia\Extensions\Agora;

use InvalidArgumentException;
use Telepedia\Extensions\Agora\Domain\Comment;
use Telepedia\Extensions\Agora\Domain\CommentCollection;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class CommentFactory {

	public function __construct(
		private readonly IConnectionProvider $connectionProvider
	) {}

	/**
	 * Return a single comment from a database id
	 * @param int $id
	 * @return Comment
	 */
	public function newFromId( int $id ): Comment {
		$dbr = $this->connectionProvider->getReplicaDatabase();

		$row = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( 'agora_comments' )
			->where(
				[
					'comment_id' => $id,
				]
			)
			->caller( __METHOD__ )
			->fetchRow();

		$comment = new Comment();

		if ( $row == null ) {
			return $comment;
		}

		$comment->setId( $row->comment_id )
			->setPageId( $row->page_id )
			->setWikiText( $row->comment_wikitext )
			->setPostedTime( $row->comment_posted_time )
			->setActorId( $row->comment_actor_id )
			->setParentId( $row->comment_parent_id ?: null );

		return $comment;
	}

	/**
	 * Return all the comments associated with a specific article ID
	 * @param int $pageId
	 * @return CommentCollection
	 */
	public function fromPage( int $pageId ): CommentCollection {
		// throw an error if someone tries a virtual namespace
		// not sure if MediaWiki page ID's start at 0 or 1 so may need to increment this to 1 if they start at 0
		if ( $pageId < 0 ) {
			throw new InvalidArgumentException("Page ID must be a positive integer." );
		}

		$dbr = $this->connectionProvider->getReplicaDatabase();

		$res = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( 'agora_comments' )
			->where(
				[
					'comment_page_id' => $pageId,
				]
			)
			->caller( __METHOD__ )
			->fetchResultSet();

		$comments = [];

		foreach ( $res as $row ) {
			$comment = new Comment();
			$comment->setId( $row->comment_id )
				->setPageId( $row->page_id )
				->setWikiText( $row->comment_wikitext )
				->setPostedTime( $row->comment_posted_time )
				->setActorId( $row->comment_actor_id )
				->setParentId( $row->comment_parent_id ?: null );

			$comments[] = $comment;
		}
		return new CommentCollection( $comments );
	}

	/**
	 * Return all the comments made by a particular actor
	 * @param int $actorId
	 * @return CommentCollection
	 */
	public function fromActor( int $actorId ): CommentCollection {
		$dbr = $this->connectionProvider->getReplicaDatabase();

		$res = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( 'agora_comments' )
			->where(
				[
					'comment_actor' => $actorId,
				]
			)
			->caller( __METHOD__ )
			->fetchResultSet();

		$comments = [];

		foreach ( $res as $row ) {
			$comment = new Comment();
			$comment->setId( $row->comment_id )
				->setPageId( $row->page_id )
				->setWikiText( $row->comment_wikitext )
				->setPostedTime( $row->comment_posted_time )
				->setActorId( $row->comment_actor_id )
				->setParentId( $row->comment_parent_id ?: null );

			$comments[] = $comment;
		}
		return new CommentCollection( $comments );
	}
}