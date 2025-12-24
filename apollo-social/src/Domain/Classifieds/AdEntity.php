<?php

namespace Apollo\Domain\Classifieds;

/**
 * Ad entity (stub)
 *
 * Represents a classified advertisement.
 * TODO: Define ad properties, mod and season binding.
 */
class AdEntity {

	/**
	 * Ad ID
	 * TODO: implement property and getters/setters
	 */
	protected $id;

	/**
	 * Ad slug
	 * TODO: implement property and URL generation
	 */
	protected $slug;

	/**
	 * Season binding (optional)
	 * TODO: implement season association
	 */
	protected $season;

	/**
	 * Moderation status
	 * TODO: implement modow
	 */
	protected $status;

	/**
	 * Get ad URL
	 * TODO: implement URL generation for /anuncio/{slug}
	 */
	public function getUrl() {
		// TODO: implement ad URL generation logic
	}

	/**
	 * Check if ad is approved
	 * TODO: implement mod checking
	 */
	public function isApproved() {
		// TODO: implement approval checking logic
	}
}
