<?php
namespace AiElementorAgent\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads declarative Skill packages from the filesystem directory.
 */
class SkillLoader {

	private string $skills_dir;

	public function __construct( ?string $skills_dir = null ) {
		$this->skills_dir = $skills_dir ?: ( dirname( __DIR__, 2 ) . '/skills' );
	}

	/**
	 * Load all declarative skills from skills directory.
	 *
	 * @return array<string, Skill> Array of Skill objects indexed by skill name.
	 */
	public function load_all_skills(): array {
		$skills = array();

		if ( ! is_dir( $this->skills_dir ) ) {
			return $skills;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->skills_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isFile() && 'skill.json' === $item->getFilename() ) {
				$skill_folder = $item->getPath();
				$skill = $this->load_skill_from_folder( $skill_folder );
				if ( $skill && ! empty( $skill->get_name() ) ) {
					$skills[ $skill->get_name() ] = $skill;
				}
			}
		}

		return $skills;
	}

	/**
	 * Load a single skill from a specific directory folder.
	 */
	public function load_skill_from_folder( string $folder_path ): ?Skill {
		$metadata_file = $folder_path . '/skill.json';
		if ( ! file_exists( $metadata_file ) ) {
			return null;
		}

		$metadata_raw = file_get_contents( $metadata_file );
		$metadata = json_decode( $metadata_raw, true );
		if ( ! is_array( $metadata ) ) {
			return null;
		}

		$prompt = '';
		$prompt_file = $folder_path . '/prompt.md';
		if ( file_exists( $prompt_file ) ) {
			$prompt = file_get_contents( $prompt_file );
		}

		$schema = array();
		$schema_file = $folder_path . '/schema.json';
		if ( file_exists( $schema_file ) ) {
			$schema_raw = file_get_contents( $schema_file );
			$schema = json_decode( $schema_raw, true ) ?: array();
		}

		$examples = array();
		$examples_file = $folder_path . '/examples.json';
		if ( file_exists( $examples_file ) ) {
			$examples_raw = file_get_contents( $examples_file );
			$examples = json_decode( $examples_raw, true ) ?: array();
		}

		return new Skill( $metadata, $prompt, $schema, $examples );
	}
}
