<?php
namespace Apollo\Domain\Entities;

/**
 * Group Entity
 */
class GroupEntity {

	public $id;
	public $title;
	public $slug;
	public $type;
	public $season_slug;
	public $description;
	public $status;
	public $visibility;
	public $members_count = 0;
	public $creator_id;
	public $created_by; 
	// Legacy alias
	public $created_at;
	public $updated_at;
	public $published_at;

	public function __construct( $data = array() ) {
		$this->id            = $data['id'] ?? 0;
		$this->title         = $data['title'] ?? '';
		$this->slug          = $data['slug'] ?? '';
		$this->type          = $data['type'] ?? 'comunidade';
		$this->season_slug   = $data['season_slug'] ?? null;
		$this->description   = $data['description'] ?? '';
		$this->status        = $data['status'] ?? 'draft';
		$this->visibility    = $data['visibility'] ?? 'public';
		$this->members_count = $data['members_count'] ?? 0;
		$this->creator_id    = $data['creator_id'] ?? ( $data['created_by'] ?? 0 );
		$this->created_by    = $this->creator_id; 
		// Legacy alias
		$this->created_at    = $data['created_at'] ?? date( 'Y-m-d H:i:s' );
		$this->updated_at    = $data['updated_at'] ?? null;
		$this->published_at  = $data['published_at'] ?? null;
	}

	// Getters for compatibility
	public function getId(): int {
		return (int) $this->id; }
	public function getTitle(): string {
		return $this->title; }
	public function getSlug(): string {
		return $this->slug; }
	public function getDescription(): string {
		return $this->description; }
	public function getType(): string {
		return $this->type; }
	public function getStatus(): string {
		return $this->status; }
	public function getVisibility(): string {
		return $this->visibility; }
	public function getSeasonSlug(): ?string {
		return $this->season_slug; }
	public function getCreatorId(): int {
		return (int) $this->creator_id; }
	public function getCreatedAt(): string {
		return $this->created_at; }
	public function getUpdatedAt(): ?string {
		return $this->updated_at; }
	public function getPublishedAt(): ?string {
		return $this->published_at; }
	public function getMembersCount(): int {
		return (int) $this->members_count; }

	public function isSeason() {
		return $this->type === 'season';
	}

	public function isNucleo() {
		return $this->type === 'nucleo';
	}

	public function isComunidade() {
		return $this->type === 'comunidade';
	}
}
