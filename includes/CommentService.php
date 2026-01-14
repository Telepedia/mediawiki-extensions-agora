<?php

namespace Telepedia\Extensions\Agora;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\Permissions\UserAuthority;
use MediaWiki\Title\Title;
use ParserOptions;
use Psr\Log\LoggerInterface;
use Telepedia\Extensions\Agora\Domain\Comment;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IDatabase;

class CommentService {

	public const CONSTRUCTOR_OPTIONS = [
		MainConfigNames::ContentNamespaces
	];

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly LoggerInterface $logger,
		private readonly IConnectionProvider $connectionProvider,
		private readonly RestrictionStore $restrictionStore,
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Check whether we can display comments on this page
	 * @param Title $title title object of the page we are checking
	 * @return bool true if we are allowed, false otherwise
	 */
	public function canDisplayComments( Title $title ): bool {
		$titleNs = $title->getNamespace();
		$contentNs = $this->options->get( MainConfigNames::ContentNamespaces );

		if ( !$title->exists() ) {
			return false;
		}

		if ( !in_array( $titleNs, $contentNs, true ) ) {
			return false;
		}

		// we use page protections for deciding whether an article can have comments or not
		// and we toggle it to admin only if comments should be disabled for a page; even if the user
		// is an admin, we still return false to prevent comments from even administrators
		// note: we remove the protection option from ?action=protect so it is only toggleable by the editor

		if ( !$this->areCommentsEnabledOnPage( $title ) ) {
			return false;
		}

		// here we also need to check if the page has comments enabled, if not bail
		return true;
	}

	/**
	 * Helper to check whether article comments are enabled on this article
	 * @TODO: merge this with the above
	 * @param Title $title
	 * @return bool
	 */
	public function areCommentsEnabledOnPage( Title $title ): bool {
		$protectionLevel = $this->restrictionStore->getRestrictions( $title, 'commenting' );

		if ( in_array( 'sysop', $protectionLevel, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the number of comments on this article; includes comments that may be deleted/hidden
	 * @param Title $title title object for the page we want the number of comments for
	 * @return int the number of comments, obviously
	 */
	public function getCommentCount( Title $title ): int {
		$dbr = $this->connectionProvider->getReplicaDatabase();

		// its more efficient here to just use a COUNT(*) than to do ->fetchRowCount()
		$res = $dbr->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'agora_comments' )
			->caller( __METHOD__ )
			->fetchField();

		return $res;
	}

	/**
	 * Check whether the current user is able to post comments
	 * @param UserAuthority $user the authority of the user in question
	 * @return bool true if they can, false otherwise
	 */
	public function userCanComment( UserAuthority $user ): bool {
		if ( !$user->isDefinitelyAllowed( 'comments' ) ) {
			return false;
		}

		$block = $user->getBlock();

		if ( $block && ( $block->isSitewide() || $block->appliesToRight( 'agora-comments' ) ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Save a comment to the database, and return the hydrated object to the caller
	 * @param Comment $comment
	 * @return ?Comment
	 */
	public function save( Comment $comment ): Comment {
		// parse the wikitext and get the HTML back; note we use the Actor ID of the
		// user who posted the comment rather than the ID of the user who is editing if so,
		// to avoid losing any context associated with the comment
		$html = $this->parse(
			$comment->getWikitext(),
			$comment->getTitle(),
			$comment->getActorId()
		);
		$comment->setHtml( $html );

		$dbw = $this->connectionProvider->getPrimaryDatabase();

		// ensure we can rollback if anything errors since we're going to be
		// writing to two different tables
		$dbw->doAtomicSection( __METHOD__, function ( IDatabase $dbw ) use ( $comment ) {

			$isNew = $comment->getId() === null;

			if ( $isNew ) {
				$dbw->newInsertQueryBuilder()
					->insertInto( 'agora_comments' )
					->rows( [
						'page_id' => $comment->getPageId(),
						'comment_actor_id' => $comment->getActorId(),
						'comment_parent_id' => $comment->getParentId(),
						'comment_posted_time' => $dbw->timestamp( wfTimestampNow() ),
						// nothing for now, we will add this later once the revision
						// has been inserted into the DB
						'comment_latest_rev_id' => null,
					] )
					->caller( __METHOD__ )
					->execute();

				$commentId = $dbw->insertId();
				$comment->setId( $commentId );
			} else {
				$commentId = $comment->getId();
				// nothing to do here, since we don't need to touch the
				// agora_comment table for an edit for now - later we may add
				// a modified field
			}

			// now lets insert the revision, which is the actual content, including the HTML
			// and Wikitext
			$dbw->newInsertQueryBuilder()
				->insertInto( 'agora_comment_revision' )
				->rows( [
					'comment_id' => $commentId,
					'comment_rev_actor_id' => $comment->getActorId(),
					'comment_rev_timestamp' => $dbw->timestamp( wfTimestampNow() ),
					'comment_wikitext' => $comment->getWikitext(),
					'comment_html' => $comment->getHtml(),
				] )
				->caller( __METHOD__ )
				->execute();

			$revId = $dbw->insertId();

			$dbw->newUpdateQueryBuilder()
				->update( 'agora_comments')
				->set( [ 'comment_latest_rev_id' => $revId ] )
				->where( [ 'comment_id' => $commentId ] )
				->caller( __METHOD__ )
				->execute();
		} );

		return $comment;
	}


	/**
	 * Utility function to parse a comments wikitext into HTML. Caller can assume that the resultant HTML has been
	 * sanitised and escaped by Parsoid and is safe for output and saving to the database
	 *
	 * @param string $wt the wikitext we want to parse
	 * @param Title $title the title context of the page the comment is being added to
	 * @param int $actorId the actor ID of the user this parse is being performed on behalf of
	 *
	 * @return string the resultant HTML
	 */
	public function parse( string $wt, Title $title, int $actorId ): string {
		$parsoidFactory = MediaWikiServices::getInstance()->getParsoidParserFactory()->create();
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$user = $userFactory->newFromActorId( $actorId );
		$parserOpts = ParserOptions::newFromUser( $user );

		return $parsoidFactory->parse( $wt, $title,$parserOpts )->runOutputPipeline( $parserOpts )->getText();
	}
}