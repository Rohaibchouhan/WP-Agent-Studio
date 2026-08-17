<?php
namespace AiElementorAgent\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a declarative AI Skill definition.
 */
class Skill {

	private string $name;
	private string $version;
	private string $category;
	private string $description;
	private array $permissions;
	private array $tools;
	private array $safety;
	private string $prompt;
	private array $schema;
	private array $examples;

	public function __construct( array $metadata, string $prompt = '', array $schema = array(), array $examples = array() ) {
		$this->name        = $metadata['name'] ?? '';
		$this->version     = $metadata['version'] ?? '1.0.0';
		$this->category    = $metadata['category'] ?? 'general';
		$this->description = $metadata['description'] ?? '';
		$this->permissions = $metadata['permissions'] ?? array();
		$this->tools       = $metadata['tools'] ?? array();
		$this->safety      = $metadata['safety'] ?? array();
		$this->prompt      = $prompt;
		$this->schema      = $schema;
		$this->examples    = $examples;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_version(): string {
		return $this->version;
	}

	public function get_category(): string {
		return $this->category;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function get_permissions(): array {
		return $this->permissions;
	}

	public function get_tools(): array {
		return $this->tools;
	}

	public function get_safety(): array {
		return $this->safety;
	}

	public function get_prompt(): string {
		return $this->prompt;
	}

	public function get_schema(): array {
		return $this->schema;
	}

	public function get_examples(): array {
		return $this->examples;
	}

	public function has_gsap_enabled(): bool {
		$features = $this->schema['properties']['motion_ux']['properties'] ?? array();
		if ( ! empty( $features['enable_gsap']['default'] ) ) {
			return true;
		}
		return ! empty( $this->safety['gsap_animation']['enabled'] );
	}

	public function get_gsap_config(): array {
		return array(
			'enabled'        => $this->has_gsap_enabled(),
			'cdn'            => 'https://gsap.com/',
			'scroll_trigger' => true,
		);
	}

	public function to_array(): array {
		return array(
			'name'        => $this->name,
			'version'     => $this->version,
			'category'    => $this->category,
			'description' => $this->description,
			'permissions' => $this->permissions,
			'tools'       => $this->tools,
			'safety'      => $this->safety,
			'prompt'      => $this->prompt,
			'schema'      => $this->schema,
			'examples'    => $this->examples,
			'gsap'        => $this->get_gsap_config(),
		);
	}
}
