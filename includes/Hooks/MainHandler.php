<?php

namespace Telepedia\Extensions\Agora\Hooks;

use MediaWiki\Block\Hook\GetAllBlockActionsHook;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\EditPage__attemptSave_afterHook;
use MediaWiki\Hook\EditPageGetCheckboxesDefinitionHook;
use MediaWiki\Hook\TitleGetRestrictionTypesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\Registration\ExtensionRegistry;
use Telepedia\Extensions\Agora\CommentService;
use Telepedia\UserProfileV2\Avatar\UserProfileV2Avatar;

class MainHandler implements
	EditPageGetCheckboxesDefinitionHook,
	TitleGetRestrictionTypesHook,
	BeforePageDisplayHook,
	MakeGlobalVariablesScriptHook,
	GetAllBlockActionsHook,
	EditPage__attemptSave_afterHook
{

	/**
	 * @inheritDoc
	 */
	public function onEditPageGetCheckboxesDefinition( $editpage, &$checkboxes ): void {

		if ( !$editpage->getContext()->getAuthority()->isAllowed( 'protect' ) ) {
			return;
		}

		$title = $editpage->getTitle();
		if ( !$title->canExist() ) {
			return;
		}

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
		$areCommentsEnabled = !in_array( 'sysop', $restrictionStore->getRestrictions( $title, 'commenting' ) );

		$checkboxes['wpCommentsEnabled'] = [
			'id' => 'wpCommentsEnabled',
			'label-message' => 'agora-comments-enabled',
			'tooltip' => 'agora-comments-enabled',
			'label-id' => 'mw-editpage-agora-comments-enabled',
			'default' => $areCommentsEnabled,
		];
	}

	/**
	 * Add our custom restriction type to allow toggling comments on or off an article
	 * @param $title
	 * @param $types
	 * @return void
	 */
	public function onTitleGetRestrictionTypes( $title, &$types ): void {
		// if we are in API request context, add commenting as a restriction type
		// to ensure API requests can toggle comments on or off
		if ( defined( 'MW_API' ) ) {
			$types[] = 'commenting';
			return;
		}

		// this is a bit of a hack; we don't want the comment restriction to appear on the protection form
		// since MediaWiki only provides a hook to ADD to the protection form and not remove from it
		// here we will just check whether we are on the protection form (or unprotection) and not add the restriction
		// level so it won't be added to the UI - this works because of a slight MediaWiki quirk, MediaWiki will not
		// touch the "commenting" restriction when the page is protected again or unprotected even though it does not
		// show in the form, because it doesn't know about it
		$request = RequestContext::getMain()->getRequest();
		$action = $request->getVal( 'action' );

		if ( $action === 'protect' || $action === 'unprotect' ) {
			return;
		}

		// we're not on the protection or unprotection form, so we can add it as a restriction so when we save a page
		// MediaWiki knows the protection exists.
		$types[] = 'commenting';
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		/** @var CommentService $commentService */
		$commentService = MediaWikiServices::getInstance()->get( 'Agora.CommentService' );
		$title = $out->getTitle();

		// don't show on main page, if we're not in content namespace, or if we're not viewing an article
		if ( !$commentService->canDisplayComments( $out->getTitle() ) ||
			$out->getActionName() !== 'view' ||
			$title->isMainPage()
		) {
			return;
		}

		$out->addModules( 'ext.agora.comments' );
	}

	/**
	 * @inheritDoc
	 */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		$title = $out->getTitle();

		/** @var CommentService $commentService */
		$commentService = MediaWikiServices::getInstance()->getService( 'Agora.CommentService' );

		if ( !$commentService->canDisplayComments( $title ) ) {
			return;
		}

		$vars['wgAgora'] = [
			'commentCount' => $commentService->getCommentCount( $title ),
			'isMod' => $commentService->userCanDelete( $out->getUser() )
		];
		
		$avatar = null;

		// get the users avatar if available
		if ( ExtensionRegistry::getInstance()->isLoaded( 'UserProfileV2' ) ) {
			$avatar = ( new UserProfileV2Avatar( $out->getUser()->getId() ) )->getAvatarUrl( [ 'raw' => true ] );
		}

		$vars['wgAgora']['userAvatar'] = $avatar;
	}

	/**
	 * Allow partial blocks for comments
	 */
	public function onGetAllBlockActions( &$actions ): void {
		$actions[ 'agora-comments' ] = 666;
	}

	/**
	 * @inheritDoc
	 */
	public function onEditPage__attemptSave_after( $editpage_Obj, $status, $resultDetails ): void {
		if ( !$status->isOK() ) {
			// save failed, nothing to do
			return;
		}

		$title = $editpage_Obj->getTitle();
		$ctx = $editpage_Obj->getContext();
		$request = $ctx->getRequest();
		$user = $ctx->getUser();

		// if the user does not have the necessary permission to protect an article, do nothing
		if ( !$ctx->getAuthority()->isDefinitelyAllowed( 'protect') ) {
			return;
		}

		// comment state from the editor; checked means that comments should be enabled for everyone
		$commentsEnabled = $request->getCheck( 'wpCommentsEnabled' );

		// inverse of above
		$shouldBeSysopOnly = !$commentsEnabled;

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
		$currentRestrictions = $restrictionStore->getRestrictions( $title, 'commenting' ) ?: [];

		// Is commenting currently restricted to sysops? if so, comments are disabled
		$isCurrentlySysopOnly = in_array( 'sysop', $currentRestrictions, true );

		if ( $isCurrentlySysopOnly === $shouldBeSysopOnly ) {
			// same state, nought to be done heree
			return;
		}

		$wikiPage = $editpage_Obj->getArticle()->getPage();

		if ( $shouldBeSysopOnly ) {
			// protect to sysop only; other logic elsewhere takes this as an indication
			// that comments are disabled for everyone
			$limit = [ 'commenting' => 'sysop' ];
			$expiry = [ 'commenting' => 'infinity' ];
		} else {
			$limit = [ 'commenting' => '' ];
			$expiry = [ 'commenting' => 'infinity' ];
		}

		$cascade = false;

		$wikiPage->doUpdateRestrictions(
			$limit,
			$expiry,
			$cascade,
			'',
			$user
		);
	}

}