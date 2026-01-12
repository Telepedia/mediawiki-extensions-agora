<?php

namespace Telepedia\Extensions\Agora;

use InvalidArgumentException;
use Telepedia\Extensions\Agora\Domain\Comment;
use Telepedia\Extensions\Agora\Domain\CommentCollection;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class CommentFactory {

	/**
	 * Table name for the comment table to avoid writing it all the time
	 */
	private const TABLE_NAME = 'agora_comments';

	/**
	 * Table name for the revision table to avoid writing it all the time
	 */
	private const REVISION_TABLE_NAME = 'agora_comment_revisions';

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
			->from( self::TABLE_NAME )
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
			// needs to come from join on revision table
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
	public function getForPage( int $pageId ): CommentCollection {
		// throw an error if someone tries a virtual namespace
		// not sure if MediaWiki page ID's start at 0 or 1 so may need to increment this to 1 if they start at 0
		if ( $pageId < 0 ) {
			throw new InvalidArgumentException("Page ID must be a positive integer." );
		}

		$dbr = $this->connectionProvider->getReplicaDatabase();

		$res = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( self::TABLE_NAME )
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
				// needs to come from join on new revision table
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
	public function getFromActor( int $actorId ): CommentCollection {
		$dbr = $this->connectionProvider->getReplicaDatabase();

		$res = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( self::TABLE_NAME )
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
				// needs to come from join on revision table
				->setWikiText( $row->comment_wikitext )
				->setPostedTime( $row->comment_posted_time )
				->setActorId( $row->comment_actor_id )
				->setParentId( $row->comment_parent_id ?: null );

			$comments[] = $comment;
		}
		return new CommentCollection( $comments );
	}

	/**
	 * Return a new comment to be populated with content
	 * @return Comment
	 */
	public function getBlank(): Comment {
		return new Comment();
	}
}