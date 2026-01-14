<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Telepedia\Extensions\Agora\CommentFactory;
use Telepedia\Extensions\Agora\CommentService;

return [
	'Agora.CommentService' => static function (
		MediaWikiServices $services
	): CommentService {
		return new CommentService(
			new ServiceOptions( CommentService::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			LoggerFactory::getInstance( 'Agora' ),
			$services->getConnectionProvider(),
			$services->getRestrictionStore()
		);
	},

	'Agora.CommentFactory' => static function (
		MediaWikiServices $services
	): CommentFactory {
		return new CommentFactory(
			$services->getConnectionProvider()
		);
	},
];