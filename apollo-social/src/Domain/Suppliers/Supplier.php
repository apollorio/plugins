<?php
/**
 * Supplier Entity
 *
 * Imutable value object representing a supplier in the Cena-Rio catalog.
 * Follows DDD patterns with normalized getters and strict type handling.
 *
 * @package Apollo\Domain\Suppliers
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Domain\Suppliers;

/**
 * Class Supplier
 *
 * Represents a supplier entity with all catalog properties.
 *
 * @since 1.0.0
 */
class Supplier {

	/**
	 * Supplier ID.
	 *
	 * @var int
	 */
	private int $id;

	/**
	 * Supplier name/title.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Supplier description.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Featured image URL.
	 *
	 * @var string
	 */
	private string $logo_url;

	/**
	 * Banner image URL.
	 *
	 * @var string
	 */
	private string $banner_url;

	/**
	 * Category slug.
	 *
	 * @var string
	 */
	private string $category;

	/**
	 * Category label (pt-BR).
	 *
	 * @var string
	 */
	private string $category_label;

	/**
	 * Subcategory slug.
	 *
	 * @var string
	 */
	private string $subcategory;

	/**
	 * Region slug.
	 *
	 * @var string
	 */
	private string $region;

	/**
	 * Neighborhood.
	 *
	 * @var string
	 */
	private string $neighborhood;

	/**
	 * Event types supported.
	 *
	 * @var array<string>
	 */
	private array $event_types;

	/**
	 * Supplier type (servico, produto, hibrido).
	 *
	 * @var string
	 */
	private string $supplier_type;

	/**
	 * Supplier modes (consignado, ecologico, personalizado).
	 *
	 * @var array<string>
	 */
	private array $modes;

	/**
	 * Badges (ecologico, acessivel_pcd, diversidade).
	 *
	 * @var array<string>
	 */
	private array $badges;

	/**
	 * Price tier (1=$ , 2=$$ , 3=$$$).
	 *
	 * @var int
	 */
	private int $price_tier;

	/**
	 * Maximum capacity (when applicable).
	 *
	 * @var int
	 */
	private int $capacity_max;

	/**
	 * Contact email.
	 *
	 * @var string
	 */
	private string $contact_email;

	/**
	 * Contact phone.
	 *
	 * @var string
	 */
	private string $contact_phone;

	/**
	 * WhatsApp number.
	 *
	 * @var string
	 */
	private string $contact_whatsapp;

	/**
	 * Instagram handle.
	 *
	 * @var string
	 */
	private string $contact_instagram;

	/**
	 * Website URL.
	 *
	 * @var string
	 */
	private string $contact_website;

	/**
	 * Linked Apollo user ID for chat.
	 *
	 * @var int
	 */
	private int $linked_user_id;

	/**
	 * Average rating (1-5).
	 *
	 * @var float
	 */
	private float $rating_avg;

	/**
	 * Total reviews count.
	 *
	 * @var int
	 */
	private int $reviews_count;

	/**
	 * Is verified supplier.
	 *
	 * @var bool
	 */
	private bool $is_verified;

	/**
	 * Post status.
	 *
	 * @var string
	 */
	private string $status;

	/**
	 * Tags array.
	 *
	 * @var array<string>
	 */
	private array $tags;

	/**
	 * Constructor - Initialize with data array.
	 *
	 * @param array<string, mixed> $data Supplier data array.
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $data = array() ) {
		$this->id                = isset( $data['id'] ) ? \absint( $data['id'] ) : 0;
		$this->name              = isset( $data['name'] ) ? \sanitize_text_field( $data['name'] ) : '';
		$this->description       = isset( $data['description'] ) ? \wp_kses_post( $data['description'] ) : '';
		$this->logo_url          = isset( $data['logo_url'] ) ? \esc_url_raw( $data['logo_url'] ) : '';
		$this->banner_url        = isset( $data['banner_url'] ) ? \esc_url_raw( $data['banner_url'] ) : '';
		$this->category          = isset( $data['category'] ) ? \sanitize_key( $data['category'] ) : '';
		$this->category_label    = isset( $data['category_label'] ) ? \sanitize_text_field( $data['category_label'] ) : '';
		$this->subcategory       = isset( $data['subcategory'] ) ? \sanitize_key( $data['subcategory'] ) : '';
		$this->region            = isset( $data['region'] ) ? \sanitize_key( $data['region'] ) : '';
		$this->neighborhood      = isset( $data['neighborhood'] ) ? \sanitize_text_field( $data['neighborhood'] ) : '';
		$this->event_types       = isset( $data['event_types'] ) && \is_array( $data['event_types'] ) ? \array_map( 'sanitize_key', $data['event_types'] ) : array();
		$this->supplier_type     = isset( $data['supplier_type'] ) ? \sanitize_key( $data['supplier_type'] ) : 'servico';
		$this->modes             = isset( $data['modes'] ) && \is_array( $data['modes'] ) ? \array_map( 'sanitize_key', $data['modes'] ) : array();
		$this->badges            = isset( $data['badges'] ) && \is_array( $data['badges'] ) ? \array_map( 'sanitize_key', $data['badges'] ) : array();
		$this->price_tier        = isset( $data['price_tier'] ) ? \min( 3, \max( 1, \absint( $data['price_tier'] ) ) ) : 1;
		$this->capacity_max      = isset( $data['capacity_max'] ) ? \absint( $data['capacity_max'] ) : 0;
		$this->contact_email     = isset( $data['contact_email'] ) ? \sanitize_email( $data['contact_email'] ) : '';
		$this->contact_phone     = isset( $data['contact_phone'] ) ? \sanitize_text_field( $data['contact_phone'] ) : '';
		$this->contact_whatsapp  = isset( $data['contact_whatsapp'] ) ? \sanitize_text_field( $data['contact_whatsapp'] ) : '';
		$this->contact_instagram = isset( $data['contact_instagram'] ) ? \sanitize_text_field( $data['contact_instagram'] ) : '';
		$this->contact_website   = isset( $data['contact_website'] ) ? \esc_url_raw( $data['contact_website'] ) : '';
		$this->linked_user_id    = isset( $data['linked_user_id'] ) ? \absint( $data['linked_user_id'] ) : 0;
		$this->rating_avg        = isset( $data['rating_avg'] ) ? (float) $data['rating_avg'] : 0.0;
		$this->reviews_count     = isset( $data['reviews_count'] ) ? \absint( $data['reviews_count'] ) : 0;
		$this->is_verified       = isset( $data['is_verified'] ) ? (bool) $data['is_verified'] : false;
		$this->status            = isset( $data['status'] ) ? \sanitize_key( $data['status'] ) : 'pending';
		$this->tags              = isset( $data['tags'] ) && \is_array( $data['tags'] ) ? \array_map( 'sanitize_text_field', $data['tags'] ) : array();
	}

	/**
	 * Get supplier ID.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get supplier name.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get supplier description.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get logo URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_logo_url(): string {
		if ( '' === $this->logo_url ) {
			// Fallback to UI Avatars.
			$initials = $this->get_initials();
			return 'https://ui-avatars.com/api/?name=' . rawurlencode( $initials ) . '&background=0f172a&color=fff';
		}
		return $this->logo_url;
	}

	/**
	 * Get banner URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_banner_url(): string {
		return $this->banner_url;
	}

	/**
	 * Get category slug.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_category(): string {
		return $this->category;
	}

	/**
	 * Get category label (pt-BR).
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_category_label(): string {
		return $this->category_label;
	}

	/**
	 * Get subcategory slug.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_subcategory(): string {
		return $this->subcategory;
	}

	/**
	 * Get region slug.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_region(): string {
		return $this->region;
	}

	/**
	 * Get neighborhood.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_neighborhood(): string {
		return $this->neighborhood;
	}

	/**
	 * Get event types.
	 *
	 * @return array<string>
	 *
	 * @since 1.0.0
	 */
	public function get_event_types(): array {
		return $this->event_types;
	}

	/**
	 * Get supplier type.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_supplier_type(): string {
		return $this->supplier_type;
	}

	/**
	 * Get modes.
	 *
	 * @return array<string>
	 *
	 * @since 1.0.0
	 */
	public function get_modes(): array {
		return $this->modes;
	}

	/**
	 * Get badges.
	 *
	 * @return array<string>
	 *
	 * @since 1.0.0
	 */
	public function get_badges(): array {
		return $this->badges;
	}

	/**
	 * Get price tier.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_price_tier(): int {
		return $this->price_tier;
	}

	/**
	 * Get price tier display ($/$$/$$$).
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_price_display(): string {
		return \str_repeat( '$', $this->price_tier );
	}

	/**
	 * Get maximum capacity.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_capacity_max(): int {
		return $this->capacity_max;
	}

	/**
	 * Get contact email.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_contact_email(): string {
		return $this->contact_email;
	}

	/**
	 * Get contact phone.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_contact_phone(): string {
		return $this->contact_phone;
	}

	/**
	 * Get WhatsApp number.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_contact_whatsapp(): string {
		return $this->contact_whatsapp;
	}

	/**
	 * Get Instagram handle.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_contact_instagram(): string {
		return $this->contact_instagram;
	}

	/**
	 * Get website URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_contact_website(): string {
		return $this->contact_website;
	}

	/**
	 * Get linked user ID.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_linked_user_id(): int {
		return $this->linked_user_id;
	}

	/**
	 * Check if has linked user for chat.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function has_linked_user(): bool {
		return $this->linked_user_id > 0;
	}

	/**
	 * Get average rating.
	 *
	 * @return float
	 *
	 * @since 1.0.0
	 */
	public function get_rating_avg(): float {
		return round( $this->rating_avg, 1 );
	}

	/**
	 * Get reviews count.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_reviews_count(): int {
		return $this->reviews_count;
	}

	/**
	 * Check if verified.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function is_verified(): bool {
		return $this->is_verified;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get tags.
	 *
	 * @return array<string>
	 *
	 * @since 1.0.0
	 */
	public function get_tags(): array {
		return $this->tags;
	}

	/**
	 * Get initials from name for avatar fallback.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_initials(): string {
		$words    = \explode( ' ', $this->name );
		$initials = '';
		foreach ( $words as $word ) {
			if ( '' !== $word ) {
				$initials .= \mb_strtoupper( \mb_substr( $word, 0, 1 ) );
				if ( \mb_strlen( $initials ) >= 2 ) {
					break;
				}
			}
		}
		return $initials ? $initials : 'SP';
	}

	/**
	 * Convert to array for view model.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 1.0.0
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'name'              => $this->name,
			'description'       => $this->description,
			'logo_url'          => $this->get_logo_url(),
			'banner_url'        => $this->banner_url,
			'category'          => $this->category,
			'category_label'    => $this->category_label,
			'subcategory'       => $this->subcategory,
			'region'            => $this->region,
			'neighborhood'      => $this->neighborhood,
			'event_types'       => $this->event_types,
			'supplier_type'     => $this->supplier_type,
			'modes'             => $this->modes,
			'badges'            => $this->badges,
			'price_tier'        => $this->price_tier,
			'price_display'     => $this->get_price_display(),
			'capacity_max'      => $this->capacity_max,
			'contact_email'     => $this->contact_email,
			'contact_phone'     => $this->contact_phone,
			'contact_whatsapp'  => $this->contact_whatsapp,
			'contact_instagram' => $this->contact_instagram,
			'contact_website'   => $this->contact_website,
			'linked_user_id'    => $this->linked_user_id,
			'has_linked_user'   => $this->has_linked_user(),
			'rating_avg'        => $this->get_rating_avg(),
			'reviews_count'     => $this->reviews_count,
			'is_verified'       => $this->is_verified,
			'status'            => $this->status,
			'tags'              => $this->tags,
			'initials'          => $this->get_initials(),
		);
	}
}
