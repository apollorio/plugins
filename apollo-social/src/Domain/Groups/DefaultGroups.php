<?php

namespace Apollo\Domain\Groups;

use Apollo\Domain\Groups\Repositories\GroupsRepository;

/**
 * Default Groups Creator
 * Creates default COMUNIDADES and PROJECT TEAM (NÚCLEO) groups on plugin activation
 */
class DefaultGroups {

	private GroupsRepository $repository;

	public function __construct() {
		$this->repository = new GroupsRepository();
	}

	/**
	 * Create default groups if they don't exist
	 */
	public function createDefaults(): void {
		// Create COMUNIDADES (public communities group)
		$this->createComunidadesGroup();

		// Create PROJECT TEAM (NÚCLEO - producers/work group)
		$this->createProjectTeamGroup();
	}

	/**
	 * Create COMUNIDADES group
	 */
	private function createComunidadesGroup(): void {
		$existing = $this->repository->findBySlug( 'comunidades' );
		if ( $existing ) {
			return;
			// Already exists
		}

		$this->repository->create(
			array(
				'title'       => 'COMUNIDADES',
				'slug'        => 'comunidades',
				'description' => 'Grupo público para comunidades e grupos de interesse',
				'type'        => 'comunidade',
				'status'      => 'published',
				'visibility'  => 'public',
				'creator_id'  => 1,
			// System user
			)
		);
	}

	/**
	 * Create PROJECT TEAM (NÚCLEO) group
	 */
	private function createProjectTeamGroup(): void {
		$existing = $this->repository->findBySlug( 'project-team' );
		if ( $existing ) {
			return;
			// Already exists
		}

		$this->repository->create(
			array(
				'title'                                      => 'PROJECT TEAM',
				'slug'                                       => 'project-team',
				'description'                                => 'Núcleo de produtores e equipe de trabalho Apollo',
				'type'                                       => 'nucleo',
				'status'                                     => 'published',
				'visibility'                                 => 'members_only',
				// Private group for producers
												'creator_id' => 1,
			// System user
			)
		);
	}

	/**
	 * Get default COMUNIDADES group
	 */
	public function getComunidadesGroup() {
		return $this->repository->findBySlug( 'comunidades' );
	}

	/**
	 * Get default PROJECT TEAM group
	 */
	public function getProjectTeamGroup() {
		return $this->repository->findBySlug( 'project-team' );
	}
}
