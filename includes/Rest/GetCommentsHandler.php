<?php

namespace Telepedia\Extensions\Agora\Rest;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use Telepedia\Extensions\Agora\CommentFactory;
use Telepedia\Extensions\Agora\CommentService;
use Wikimedia\ParamValidator\ParamValidator;

class GetCommentsHandler extends SimpleHandler {

	public function __construct(
		private readonly CommentFactory $commentFactory,
		private readonly CommentService $commentService,
		private readonly TitleFactory $titleFactory,
	) {

	}

	/**
	 * @param $articleId
	 * @return Response
	 */
	public function run( $articleId ): Response {
		$title = $this->titleFactory->newFromID( $articleId );

		// this will check whether the article exists et al; if we can't display comments, then we
		// also can't get them from the API
		if ( !$this->commentService->canDisplayComments( $title ) ) {
			return $this->getResponseFactory()->createHttpError(
				403,
				[ 'error' => wfMessage( 'agora-error-comments-disabled' )->text() ]
			);
		}

		$comments = null;

		// this is a bit fucked?!
		$hideDeleted = $this->getValidatedParams()['hideDeleted'];
		$isMod = $this->getAuthority()->isDefinitelyAllowed( 'comments-admin' );

		if ( $isMod ) {
			$allComments = $this->commentFactory->getForPage( $title, $hideDeleted );
		} else {
			$allComments = $this->commentFactory->getForPage( $title, true );
		}

		$comments['comments'] = $allComments->jsonSerialize();

		$comments['isMod'] = $isMod;

		return $this->getResponseFactory()->createJson( $comments );
	}

	/**
	 * @inheritDoc
	 */
	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings(): array {
		return [
			'articleId' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'hideDeleted' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false
			],
		];
	}

}