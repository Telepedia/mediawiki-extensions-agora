<?php

namespace Telepedia\Extensions\Agora;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\IConnectionProvider;

class CommentService {

	public const CONSTRUCTOR_OPTIONS = [
		MainConfigNames::ContentNamespaces
	];

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly LoggerInterface $logger,
		private readonly IConnectionProvider $connectionProvider
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Check whether we can dispaly comments on this page
	 * @param Title $title title object of the page we are checking
	 * @return bool true if we are allowed, false otherwise
	 */
	public function canDisplayComments( Title $title ): bool {
		$titleNs = $title->getNamespace();
		$contentNs = $this->options->get( MainConfigNames::ContentNamespaces );

		if ( !in_array( $titleNs, $contentNs, true ) ) {
			return false;
		}

		// here we also need to check if the page has comments enabled, if not bail
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
}