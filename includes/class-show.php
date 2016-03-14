<?php
/**
 * WP Show Tracker Show
 *
 * @version 0.2.0
 * @package WP Show Tracker
 */

require_once dirname( __FILE__ ) . '/../vendor/cpt-core/CPT_Core.php';
require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

/**
 * WPST_Show Class
 *
 * @since  0.1.0
 */
class WPST_Show extends CPT_Core {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 * Register Custom Post Types. See documentation in CPT_Core, and in wp-includes/post.php
	 *
	 * @since  0.1.0
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
			array(
				'supports'  => array( 'title' ),
				'menu_icon' => 'dashicons-editor-video',
			)
		);
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function hooks() {
		add_action( 'cmb2_init', array( $this, 'fields' ) );
		add_action( 'admin_menu', array( $this, 'remove_metaboxes' ) );
	}

	/**
	 * Add custom fields to the CPT
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function fields() {
		$prefix = 'wpst_show_';

		$cmb = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Show Information', 'wp-show-tracker' ),
			'object_types'  => array( 'wpst_show' ),
		) );

		if ( ! is_admin() ) {
			$cmb->add_field( array(
				'name'       => __( 'Show Title', 'wp-show-tracker' ),
				'id'         => 'submitted_post_title',
				'desc'       => __( 'The name of the show, episode or movie.', 'wp-show-tracker' ),
				'type'       => 'text',
				'attributes' => array( 'required' => 'required' ),
			) );
		}

		$cmb->add_field( array(
			'name'       => __( 'Viewer', 'wp-show-tracker' ),
			'id'         => $prefix . 'viewer',
			'desc'       => __( 'Who watched this show?', 'wp-show-tracker' ),
			'type'       => 'taxonomy_radio',
			'taxonomy'   => 'wpst_viewer',
			'attributes' => array( 'required' => 'required' ),
			'show_option_none' => false,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Count', 'wp-show-tracker' ),
			'id'         => $prefix . 'count',
			'desc'       => __( 'How many shows watched in this sitting.', 'wp-show-tracker' ),
			'default'    => '1',
			'type'       => 'text_small',
			'attributes' => array( 'required' => 'required' ),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Date', 'wp-show-tracker' ),
			'id'         => $prefix . 'date',
			'desc'       => __( 'When was this show watched?', 'wp-show-tracker' ),
			'type'       => 'text_date_timestamp',
			'default'    => strtotime( 'today' ),
		) );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @since  0.1.0
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
	 * @since 0.1.0
	 * @param array $column  Column currently being rendered.
	 * @param int   $post_id ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {
		}
	}


	/**
	 * Remove default WordPress metaboxes.
	 * @since 0.2.0
	 */
	public function remove_metaboxes() {
		remove_meta_box( 'tagsdiv-wpst_viewer', 'wpst_show', 'side' );
	}
}
