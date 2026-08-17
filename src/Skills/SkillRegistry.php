<?php
namespace AiElementorAgent\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages registered AI skills, validation, and search index.
 */
class SkillRegistry {

	private SkillLoader $loader;
	private array $skills = array();

	public function __construct( SkillLoader $loader ) {
		$this->loader = $loader;
		$this->reload_skills();
	}

	public function reload_skills(): void {
		$this->skills = $this->loader->load_all_skills();
	}

	public function register_skill( Skill $skill ): void {
		$this->skills[ $skill->get_name() ] = $skill;
	}

	public function get_skill( string $name ): ?Skill {
		return $this->skills[ $name ] ?? null;
	}

	/**
	 * List all registered skills.
	 *
	 * @param string|null $category Filter by category if specified.
	 * @return array Array of Skill objects or arrays.
	 */
	public function list_skills( ?string $category = null ): array {
		if ( null === $category ) {
			return $this->skills;
		}

		$filtered = array();
		foreach ( $this->skills as $name => $skill ) {
			if ( $skill->get_category() === $category ) {
				$filtered[ $name ] = $skill;
			}
		}
		return $filtered;
	}

	/**
	 * Find skills by matching prompt keywords.
	 */
	public function find_matching_skills( string $user_prompt ): array {
		$matches = array();
		$prompt_lower = strtolower( $user_prompt );

		foreach ( $this->skills as $name => $skill ) {
			$name_match = str_contains( $prompt_lower, strtolower( $name ) );
			$desc_match = str_contains( $prompt_lower, strtolower( $skill->get_description() ) );
			if ( $name_match || $desc_match ) {
				$matches[] = $skill;
			}
		}

		return $matches;
	}
}
