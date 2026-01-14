<?php

namespace Telepedia\Extensions\Agora\Rest;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\TokenAwareHandlerTrait;
use MediaWiki\Rest\Validator\Validator;
use MediaWiki\Title\TitleFactory;
use Telepedia\Extensions\Agora\CommentFactory;
use Telepedia\Extensions\Agora\CommentService;
use Wikimedia\ParamValidator\ParamValidator;

class PostCommentHandler extends SimpleHandler {

	use TokenAwareHandlerTrait;

	public function __construct(
		private readonly CommentFactory $commentFactory,
		private readonly CommentService $commentService,
		private readonly TitleFactory $titleFactory,
	) {

	}

	public function run(): Response {
		$authority = $this->getAuthority();

		if ( !$this->commentService->userCanComment( $authority ) ) {
			return $this->getResponseFactory()->createHttpError(
				403,
				[ 'error' => wfMessage( 'agora-error-not-allowed' )->text() ]
			);
		}

		$body = $this->getValidatedBody();

		$articleId = isset( $body['articleId'] ) ? ( int )$body['articleId'] : null;
		$parentId = isset( $body['parentId'] ) ? ( int )$body['parentId'] : null;

		if ( is_null( $articleId ) && is_null( $parentId ) ) {
			// must either provide an articleId if posting a top level comment, or a parentId if replying to a comment
			// @TODO: check - maybe we need to always require the articleId so we can check whether comments are even
			// enabled on this page
			// alternatively we can get the pageId from the parent comment and check that way
			return $this->getResponseFactory()->createHttpError(
				400,
				[ 'error' => wfMessage( 'agora-error-malformed-request' )->text() ]
			);
		}

		$wikitext = trim( ( string )$body['wikitext'] ?? null );

		if ( !$wikitext ) {
			return $this->getResponseFactory()->createHttpError(
				400,
				[ 'error' => wfMessage( 'agora-error-malformed-wikitext' )->text() ]
			);
		}

		if ( !is_null( $parentId ) ) {
			$comment = $this->commentFactory->newFromId( $parentId );

			if ( $comment->isDeleted() ) {
				return $this->getResponseFactory()->createHttpError(
					400,
					[ 'error' => wfMessage( 'agora-error-parent-deleted' )->text() ]
				);
			}

			if ( $this->commentFactory->getParent( $comment ) !== null ) {
				return $this->getResponseFactory()->createHttpError(
					400,
					[ 'error' => wfMessage( 'agora-error-parent' )->text() ]
				);
			}

			// we got this far, so we are posting a reply to a top-level comment; find out what article it is
			$articleId = $comment->getTitle()->getArticleID();
		}

		$article = $this->titleFactory->newFromID( $articleId );

		// check whether we can post comments on this article
		if ( !$this->commentService->canDisplayComments( $article ) ) {
			return $this->getResponseFactory()->createHttpError(
				400,
				[ 'error' => wfMessage( 'agora-error-comments-disabled' )->text() ]
			);
		}

		// we got this far, lets create the comment
		$newComment = $this->commentFactory->getBlank()
			->setParentId( $parentId )
			->setPageId( $articleId )
			->setWikitext( $wikitext )
			->setActorId( $this->getSession()->getUser()->getActorId() );

		// @TODO: check abusefilter for potentially harmful content
		$this->commentService->save( $newComment );

		// satisfy return of function for now
		return $this->getResponseFactory()->createNoContent();
	}

	/**
	 * @inheritDoc
	 */
	public function needsWriteAccess(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( Validator $restValidator ): void {
		parent::validate( $restValidator );
		$this->validateToken( false );
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyParamSettings(): array {
		return [
			'articleId' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'parentId' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'wikitext' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		] + $this->getTokenParamDefinition();
	}
}