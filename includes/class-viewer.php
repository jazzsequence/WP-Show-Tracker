<?php
/**
 * WP Show Tracker Viewer
 *
 * @version 0.1.0
 * @package WP Show Tracker
 */

require_once dirname(__FILE__) . '/../vendor/taxonomy-core/Taxonomy_Core.php';

class WPST_Viewer extends Taxonomy_Core {
	/**
	 * Parent plugin class
	 *
	 * @var class
	 * @since  NEXT
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 * Register Taxonomy. See documentation in Taxonomy_Core, and in wp-includes/taxonomy.php
	 *
	 * @since NEXT
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Register this taxonomy
		// First parameter should be an array with Singular, Plural, and Registered name
		// Second parameter is the register taxonomy arguments
		// Third parameter is post types to attach to.
		parent::__construct(
			array( __( 'Viewer', 'wp-show-tracker' ), __( 'Viewers', 'wp-show-tracker' ), 'wpst_viewer' ),
			array( 'hierarchical' => false ),
			array( 'post' )
		);
	}

	/**
	 * Initiate our hooks
	 *
	 * @since NEXT
	 * @return void
	 */
	public function hooks() {
	}
}
