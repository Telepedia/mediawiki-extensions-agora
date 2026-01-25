<?php

namespace Telepedia\Extensions\Agora;

use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use stdClass;
use Telepedia\Extensions\Agora\Domain\Comment;
use Telepedia\Extensions\Agora\Domain\CommentCollection;
use Telepedia\UserProfileV2\Avatar\UserProfileV2Avatar;
use Wikimedia\Rdbms\IConnectionProvider;

class CommentFactory {

	/**
	 * Table name for the comment table to avoid writing it all the time
	 */
	private const TABLE_NAME = 'agora_comments';

	/**
	 * Table name for the revision table to avoid writing it all the time
	 */
	private const REVISION_TABLE_NAME = 'agora_comment_revision';

	public function __construct(
		private readonly IConnectionProvider $connectionProvider,
		private readonly UserFactory $userFactory
	) {}

	/**
	 * Return a single comment from a database id
	 * @param int $id
	 * @return ?Comment
	 */
	public function newFromId( int $id ): ?Comment {
		$dbr = $this->connectionProvider->getReplicaDatabase();
		$queryInfo = $this->getQueryInfo();

		$row = $dbr->newSelectQueryBuilder()
			->select( $queryInfo['fields'] )
			->from( $queryInfo['tables']['c'], 'c' )
			->join(
				$queryInfo['tables']['r'],
				'r',
				$queryInfo['joins']['r'][1]
			)
			->where( [ 'c.comment_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( !$row ) {
			return null;
		}

		return $this->rowToComment( $row );
	}

	/**
	 * Return all the comments associated with a specific article ID
	 * @param Title $title
	 * @param bool $hideDeleted whether we should hide the deleted comments
	 * @return CommentCollection
	 */
	public function getForPage( Title $title, bool $hideDeleted ): CommentCollection {
		$articleId = $title->getArticleID();
		$dbr = $this->connectionProvider->getReplicaDatabase();

		$queryInfo = $this->getQueryInfo();

		$res = $dbr->newSelectQueryBuilder()
			->select( $queryInfo['fields'] )
			->from( $queryInfo['tables']['c'], 'c' )
			->leftJoin(
				$queryInfo['tables']['r'],
				'r',
				$queryInfo['joins']['r'][1]
			)
			->where( [ 'c.page_id' => $articleId ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$comments = [];
		$actorIds = [];

		foreach ( $res as $row ) {
			$comment = $this->rowToComment( $row );
			$comments[] = $comment;

			if ( $comment->getActorId() ) {
				$actorIds[] = $comment->getActorId();
			}
		}
		
		if ( !empty( $actorIds ) ) {
			$this->hydrateActors( $comments, array_unique( $actorIds ) );
		}

		$allComments = null;

		if ( $hideDeleted ) {
			$allComments = ( new CommentCollection( $comments ) )->withoutDeleted();
		} else {
			$allComments = new CommentCollection( $comments );
		}

		return $allComments;
	}

	/**
	 * Hydrate the comments with actor information such as the avatar and username
	 * * @param Comment[] $comments
	 * @param int[] $actorIds Unique list of actor IDs
	 */
	private function hydrateActors( array $comments, array $actorIds ): void {
		$userMap = [];

		foreach ( $actorIds as $actorId ) {
			$user = $this->userFactory->newFromActorId( $actorId );

			if ( $user->isRegistered() ) {
				$userMap[ $actorId ] = [
					'username' => $user->getName(),
					'avatar'   => ( new UserProfileV2Avatar( $user->getId() ) )->getAvatarUrl( [ 'raw' => true ] ),
				];
			}
		}

		foreach ( $comments as $comment ) {
			$id = $comment->getActorId();

			if ( isset( $userMap[ $id ] ) ) {
				$comment->setUsername( $userMap[ $id ]['username'] );
				$comment->setAvatar( $userMap[ $id ]['avatar'] );
			} else {
				$comment->setUsername( wfMessage( 'agora-unknown-user' )->text() );
				$comment->setAvatar( '' );
			}
		}
	}

	/**
	 * Return all the comments made by a particular actor
	 * @param int $actorId
	 * @return CommentCollection
	 */
	public function getFromActor( int $actorId ): CommentCollection {
		// no-op for now
	}

	/**
	 * Return a new comment to be populated with content
	 * @return Comment
	 */
	public function getBlank(): Comment {
		return new Comment();
	}

	/**
	 * Get the parent comment of this comment
	 * @param Comment $comment either the comment object, or null if the comment is a parent
	 * @return ?Comment
	 */
	public function getParent( Comment $comment ): ?Comment {
		if ( $comment->getParentId() !== null ) {
			return $this->newFromId( $comment->getParentId() );
		}
		return null;
	}

	/**
	 * Helper function to help hydrate a comment (agora_comments) with its contents (agora_comment_revision)
	 * @return array
	 */
	private function getQueryInfo(): array {
		return [
			'tables' => [
				// agora_comments
				'c' => self::TABLE_NAME,
				// agora_comment_revision
				'r' => self::REVISION_TABLE_NAME
			],
			'fields' => [
				'c.comment_id',
				'c.page_id',
				'c.comment_actor_id',
				'c.comment_parent_id',
				'c.comment_posted_time',
				'c.comment_deleted_actor',
				'r.comment_wikitext',
				'r.comment_html'
			],
			'joins' => [
				'r' => [
					'LEFT JOIN',
					'c.comment_latest_rev_id = r.comment_rev_id'
				]
			]
		];
	}

	/**
	 * Convert a database row into a comment object
	 * @param stdClass $row
	 * @return Comment
	 */
	private function rowToComment( stdClass $row ): Comment {
		$comment = $this->getBlank()
			->setId( ( int )$row->comment_id )
			->setPageId( ( int )$row->page_id )
			->setActorId( ( int )$row->comment_actor_id )
			->setParentId( $row->comment_parent_id ? (int)$row->comment_parent_id : null )
			->setPostedTime( $row->comment_posted_time )
			->setWikiText( $row->comment_wikitext )
			->setHtml( $row->comment_html );

		if ( $row->comment_deleted_actor ) {
			$comment->setActorForDeletion( ( int )$row->comment_deleted_actor );
		}

		return $comment;
	}
}