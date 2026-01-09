<?php

namespace Telepedia\Extensions\Agora\Domain;

use MediaWiki\Title\Title;

class Comment {

	/**
	 * Unique database ID for this comment
	 * @var int
	 */
	private int $id;

	/**
	 * Page ID that this comment belongs to
	 * @var int
	 */
	private int $pageId;

	/**
	 * Wikitext content of this comment
	 * @var string
	 */
	private string $wikitext;

	/**
	 * Time at which this comment was posted
	 * @var string
	 */
	private string $postedTime;

	/**
	 * ID of the parent comment if this is a reply, otherwise, null
	 * @var ?int
	 */
	private ?int $parentId = null;

	/**
	 * ID of the actor who posted this comment
	 * @var int
	 */
	private int $actorId;

	/**
	 * Is this comment deleted?
	 * @var bool
	 */
	private bool $deleted = false;

	/**
	 * Title instance for the article this comment was posted on
	 * @var Title
	 */
	private Title $pageTitle;

	public function __construct() {}

	/**
	 * Return the ID of the parent, alternatively, null if top-level comment
	 * @return int|null
	 */
	public function getParentId(): ?int {
		return $this->parentId ?: null;
	}

	public function setParentId( ?int $parentId ): self {
		if ( $parentId !== null ) {
			$this->parentId = $parentId;
		}
		return $this;
	}

	/**
	 * Return the actor who posted this comment
	 * @return int
	 */
	public function getActorId(): int {
		return $this->actorId;
	}

	/**
	 * Set the actor responsible for posting this comment
	 * @param int $actorId
	 * @return $this
	 */
	public function setActorId(int $actorId): self {
		$this->actorId = $actorId;
		return $this;
	}

	/**
	 * Return the time at which this comment was posted
	 * @return string
	 */
	public function getTimestamp(): string {
		return $this->postedTime;
	}

	/**
	 * Return the wikitext for this comment
	 * @return string
	 */
	public function getWikitext(): string {
		return $this->wikitext;
	}

	/**
	 * Set the wikitext content of this comment
	 * @param string $wikiText
	 *
	 * @return $this
	 */
	public function setWikiText( string $wikiText ): self {
		$this->wikitext = $wikiText;
		return $this;
	}

	/**
	 * Set the time at which this comment was posted
	 * @param string $postedTime
	 *
	 * @return $this
	 */
	public function setTimestamp( string $postedTime ): self {
		$this->postedTime = $postedTime;
		return $this;
	}

	/**
	 * Get the title object for the page which this comment was posted on
	 * @return Title
	 */
	public function getTitle(): Title {
		if ( !is_null( $this->pageTitle ) ) {
			return $this->pageTitle;
		}

		return Title::newFromID( $this->pageId );
	}

	/**
	 * Get the ID of this comment
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Set the ID for this comment
	 * @param int $id
	 * @return $this
	 */
	public function setId( int $id ): self {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get the ID of the article this comment was posted on
	 * @return int
	 */
	public function getPageId(): int {
		return $this->pageId;
	}

	/**
	 * Set the ID of the article this comment was posted on
	 * @param int $pageId
	 * @return $this
	 */
	public function setPageId( int $pageId ): self {
		$this->pageId = $pageId;
		return $this;
	}

	/**
	 * Set the time at which this comment was posted - this will be in the form TS_MW
	 * @param string $postedTime
	 *
	 * @return $this
	 */
	public function setPostedTime( string $postedTime ): self {
		$this->postedTime = $postedTime;
		return $this;
	}

}