<?php
/**
 * Module registry and service locator.
 *
 * Plugins declare themselves on 'sm_register_modules':
 *   $registry->register( 'sm-products', array( 'version' => '0.1.0', 'requires_core' => '0.1.0', 'capabilities' => array( 'listing.products' ) ) );
 * and may expose services:
 *   SM\Core\Modules::set_service( 'weight', $engine );
 *
 * @package SM\Core
 */

namespace SM\Core;

defined( 'ABSPATH' ) || exit;

final class Modules {

	/** @var Modules|null */
	private static $instance;

	/** @var array<string,array> */
	private $modules = array();

	/** @var array<string,object> */
	private static $services = array();

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function init() {
		self::instance()->register(
			'sm-core',
			array(
				'version' => SM_CORE_VERSION,
				'label'   => 'SM Core',
			)
		);
	}

	/**
	 * Register a module. Returns false (and records a notice) when the module
	 * needs a newer sm-core than the installed one.
	 */
	public function register( $slug, array $info ) {
		$info = wp_parse_args(
			$info,
			array(
				'version'       => '0.0.0',
				'requires_core' => '0.0.0',
				'label'         => $slug,
				'capabilities'  => array(),
			)
		);
		if ( version_compare( SM_CORE_VERSION, $info['requires_core'], '<' ) ) {
			$info['error'] = sprintf( 'requires sm-core %s', $info['requires_core'] );
		}
		$this->modules[ $slug ] = $info;
		return empty( $info['error'] );
	}

	public function is_active( $slug ) {
		return isset( $this->modules[ $slug ] ) && empty( $this->modules[ $slug ]['error'] );
	}

	public function all() {
		return $this->modules;
	}

	public static function set_service( $name, $service ) {
		self::$services[ $name ] = $service;
	}

	/** Service by name, or null when the providing plugin is inactive. */
	public static function service( $name ) {
		return self::$services[ $name ] ?? null;
	}
}
