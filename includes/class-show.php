<?php
/**
 * WP Show Tracker Show
 *
 * @version 0.1.0
 * @package WP Show Tracker
 */

require_once dirname(__FILE__) . '/../vendor/cpt-core/CPT_Core.php';
require_once dirname(__FILE__) . '/../vendor/cmb2/init.php';

class WPST_Show extends CPT_Core {
	/**
	 * Parent plugin class
	 *
	 * @var class
	 * @since  NEXT
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 * Register Custom Post Types. See documentation in CPT_Core, and in wp-includes/post.php
	 *
	 * @since  NEXT
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Register this cpt
		// First parameter should be an array with Singular, Plural, and Registered name.
		parent::__construct(
			array( __( 'Show', 'wp-show-tracker' ), __( 'Shows', 'wp-show-tracker' ), 'wpst_show' ),
			array( 'supports' => array( 'title' ) )
		);
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
		add_action( 'cmb2_init', array( $this, 'fields' ) );
		add_action( 'admin_menu', array( $this, 'remove_metaboxes' ) );
	}

	/**
	 * Add custom fields to the CPT
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function fields() {
		$prefix = 'wpst_';

		$cmb = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'WP Show Tracker Show Meta Box', 'wp-show-tracker' ),
			'object_types'  => array( 'wp-show-tracker-show' ),
		) );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @since  NEXT
	 * @param  array $columns Array of registered column names/labels.
	 * @return array          Modified array
	 */
	public function columns( $columns ) {
		$new_column = array();
		return array_merge( $new_column, $columns );
	}

	/**
	 * Handles admin column display. Hooked in via CPT_Core.
	 *
	 * @since  NEXT
	 * @param array $column  Column currently being rendered.
	 * @param int   $post_id ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {
		}
	}

	public function remove_metaboxes() {
		remove_meta_box( 'tagsdiv-wpst_viewer', 'wpst_show', 'side' );
	}
}
