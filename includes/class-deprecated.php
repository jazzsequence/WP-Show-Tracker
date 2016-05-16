<?php
/**
 * WP Show Tracker Deprecated
 *
 * @since NEXT
 * @package WP Show Tracker
 */

/**
 * WP Show Tracker Deprecated.
 *
 * @since NEXT
 */
class WPST_Deprecated {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since NEXT
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  NEXT
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'migrate_from_old_version' ) );
	}

	public function migrate_from_old_version() {
		if ( isset( $_GET ) && ! isset( $_GET['update_show_counts'] ) ) {
			return;
		}
		delete_option( 'wpst_migrated_from_old_version' );
		if ( $already_run = get_option( 'wpst_migrated_from_old_version' ) ) {
			wp_die( esc_html__( 'Migration has already been run. Skipping migration.', 'wp-show-tracker' ), esc_html__( 'Show Tracker migration error', 'wp-show-tracker' ) );
		}

		// Get all the unique shows.
		$shows = wpst()->helpers->get_show_list();

		echo '<h3>' . esc_html_e( 'Shows updated', 'wp-show-tracker' ) . '</h3>';

		// Each show probably has multiple different shows of that same title. Process each show individually.
		foreach ( $shows as $show_group ) {
			// Skip shows with no title.
			if ( '' == $show_group ) {
				continue;
			}

			$this->process_show( $this->get_all_instances_of_show( $show_group ) );
		}

		// Make sure we don't run this again.
		// add_option( 'wpst_migrated_from_old_version', true );

		wp_die();
	}

	private function get_all_instances_of_show( $show_title ) {
		global $wpdb;

		$ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_title='$show_title'" );

		return ( ! empty( $ids ) ) ? $ids : false ;
	}


	/**
	 * Get the total count for a particular show across all instances of that show.
	 * @param  array $show_group Array of WP_Post objects for a particular show.
	 * @return int               Total count of actual watches for the current show.
	 */
	private function get_show_count( $show_group ) {
		$count = 0;
		foreach ( $show_group as $show ) {
			var_dump($show->post_title);
			$watched = get_post_meta( $show->ID, 'wpst_show_count', true );
			$count = $count + absint( $watched );
		}

		return $count;
	}

	private function prune_show( $shows, $omit_id ) {
		foreach ( $shows as $show_to_delete ) {
			if ( $omit_id == $show_to_delete->ID ) {
				continue;
			}
			wp_delete_post( $show_to_delete->ID );
		}
	}
}
