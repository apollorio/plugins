<?php
namespace Apollo\Application\Classifieds;

use Apollo\Domain\Entities\GroupEntity;

/**
 * Season Binding Validator
 *
 * Ensures classified ads in season context have correct season_slug
 */
class BindSeason {

	/**
	 * Validate season binding for classified ad
	 *
	 * @param array            $adPayload
	 * @param GroupEntity|null $contextGroup
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function validate( array $adPayload, ?GroupEntity $contextGroup = null ): bool {
		// If no context group, no season binding required
		if ( ! $contextGroup ) {
			return true;
		}

		// If context group is not a season, no season binding required
		if ( ! $contextGroup->isSeason() ) {
			return true;
		}

		// Season context requires season_slug
		if ( empty( $adPayload['season_slug'] ) ) {
			throw new \InvalidArgumentException( 'Season slug é obrigatório para anúncios em seasons', 422 );
		}

		// Season slug must match context group's season
		if ( $adPayload['season_slug'] !== $contextGroup->season_slug ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Season slug deve ser "%s" para este contexto, mas "%s" foi fornecido',
					$contextGroup->season_slug,
					$adPayload['season_slug']
				),
				422
			);
		}

		return true;
	}

	/**
	 * Apply season binding to ad payload
	 *
	 * @param array            $adPayload
	 * @param GroupEntity|null $contextGroup
	 * @return array
	 */
	public function apply( array $adPayload, ?GroupEntity $contextGroup = null ): array {
		if ( $contextGroup && $contextGroup->isSeason() ) {
			$adPayload['season_slug'] = $contextGroup->season_slug;
			$adPayload['group_id']    = $contextGroup->id;
		}

		return $adPayload;
	}

	/**
	 * Get available seasons for validation
	 *
	 * @return array
	 */
	public function getAvailableSeasons(): array {
		$seasons_config = include APOLLO_SOCIAL_PLUGIN_DIR . 'config/seasons.php';
		return array_keys( $seasons_config );
	}

	/**
	 * Validate season slug exists
	 *
	 * @param string $season_slug
	 * @return bool
	 */
	public function isValidSeasonSlug( string $season_slug ): bool {
		$available_seasons = $this->getAvailableSeasons();
		return in_array( $season_slug, $available_seasons );
	}
}
