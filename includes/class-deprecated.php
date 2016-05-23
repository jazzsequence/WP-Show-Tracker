<?php
/**
 * WP Show Tracker Deprecated
 *
 * @since 0.6.0
 * @package WP Show Tracker
 */

/**
 * WP Show Tracker Deprecated.
 *
 * @since 0.6.0
 */
class WPST_Deprecated {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.6.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.6.0
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
	 * @since  0.6.0
	 * @return void
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'migrate_from_old_version' ) );
	}

	/**
	 * If ?update_show_counts was run on a version of WP Show Tracker that was updated from an old version, increment the counts across all viewings of that show and get rid of the duplicate posts.
	 *
	 * @since  0.6.0
	 */
	public function migrate_from_old_version() {
		if ( isset( $_GET ) && ! isset( $_GET['update_show_counts'] ) ) {
			return;
		}

		// Only run the checks if debugging is turned off.
		if ( ! defined( 'WP_DEBUG' ) || defined( 'WP_DEBUG' ) && ! WP_DEBUG ) {
			if ( version_compare( wpst()->__get( 'version' ), '1.0.0', '>=' ) ) {
				add_option( 'wpst_migrated_from_old_version', true );
				wp_die( esc_html__( 'You don\'t need to run this update. You are on a non-beta version of WP Show Tracker.', 'wp-show-tracker' ), esc_html__( 'Show Tracker migration not needed.', 'wp-show-tracker' ) );
			}

			if ( $already_run = get_option( 'wpst_migrated_from_old_version' ) ) {
				wp_die( esc_html__( 'Migration has already been run. Skipping migration.', 'wp-show-tracker' ), esc_html__( 'Show Tracker migration error', 'wp-show-tracker' ) );
			}
		}

		// Get all the unique shows.
		$shows = wpst()->helpers->get_show_list();

		// Check for errors.
		if ( ! isset( $shows->errors ) ) {
			echo '<h3>' . esc_html_e( 'Shows updated', 'wp-show-tracker' ) . '</h3>';

			// Each show probably has multiple different shows of that same title. Process each show individually.
			foreach ( $shows as $show_group ) {
				// Skip shows with no title.
				if ( '' == $show_group ) {
					continue;
				}

				$this->process_show( wpst()->helpers->get_show_id_by_title( $show_group ) );
			}

			// Make sure we don't run this again.
			add_option( 'wpst_migrated_from_old_version', true );
		} else {
			foreach ( $shows->errors as $key => $value ) {
				esc_attr_e( $value[0] );
			}
		}

		wp_die();
	}

	/**
	 * Update the first show with data from the duplicates.
	 *
	 * @since  0.6.0
	 * @param  array $ids An array of post IDs reflecting all instances of this show.
	 */
	private function process_show( $ids ) {
		// Bail if we don't have IDs.
		if ( ! $ids ) {
			return;
		}
		// Get all the instances of this show.
		$shows = get_posts( array(
			'post_type'  => 'wpst_show',
			'post__in'   => $ids,
			'order'      => 'ASC',
			'nopaging'   => true,
		) );

		// Bail if we don't have any shows.
		if ( empty( $shows ) ) {
			return;
		}

		// First let's get a total count of all the times this show was watched.
		$watched = $this->get_show_count( $shows );

		// The first instance of this show should be the first element in the array.
		$this_show_id = $shows[0]->ID;

		// Update the show count with the total count from all other show instances.
		update_post_meta( $this_show_id, 'wpst_show_count', $watched );

		// Update viewer data, attach viewers to this show.
		$this->update_viewer_data( $shows, $this_show_id );

		// Update viewings, attach each sitting to this show.
		$this->update_viewings( $shows, $this_show_id );

		// Delete all the other show instances.
		$this->prune_show( $shows, $this_show_id );

		// Display a completed screen with an updated list of show counts.
		echo esc_html( sprintf( __( 'Show %s updated with %d watches.', 'wp-show-tracker' ), $shows[0]->post_title, $watched ) ) . '<br />';
	}

	/**
	 * Handles the viewer information for each show.
	 * This method:
	 * 	1. Gets the viewers of all the passed shows.
	 *  2. Adds/appends the viewers of all the shows to the $parent_show_id.
	 *  3. Calculates the number of views for each viewer for each show.
	 *  4. Saves those counts for each viewer to the $parent_show_id.
	 *
	 * @since  0.6.0
	 * @param  array $shows          Array of WP_Post objects for a particular show.
	 * @param  int   $parent_show_id The post ID of the parent show.
	 * @todo                         Finish this method!!!
	 */
	private function update_viewer_data( $shows, $parent_show_id ) {
		// Get the viewers of all the passed shows.
		$viewers = $this->get_viewers_for_shows( $shows );

		// This method needs to:
		// 3. Calculate the number of views for each viewer for each show.
		// 4. Save those counts for each viewer to the $parent_show_id.
		// Add/append the viewers of all the shows to the $parent_show_id.
		foreach ( $viewers as $viewer ) {
			$this_viewer  = $viewer[0]->name;

			wp_set_object_terms( $parent_show_id, $this_viewer, 'wpst_viewer', true );

			$this_viewers_shows = $this->get_this_viewers_shows( $shows, $this_viewer );

		}
	}

	/**
	 * Return an array that includes a count of all the times a viewer has watched a particular show.
	 *
	 * @since  0.6.0
	 * @param  array  $shows  Array of WP_Post objects for a particular show.
	 * @param  string $viewer The viewer term name.
	 * @return array          An array of show information for the viewer for a particular show.
	 */
	private function get_this_viewers_shows( $shows, $viewer ) {
		$this_viewers_shows = array();

		// Loop through the shows and calculate the number of views for each viewer for each show.
		foreach ( $shows as $show ) {
			$this_viewers_shows[ $show->ID ] = array(
				'slug'  => $show->post_name,
				'ID'    => $show->ID,
				'count' => 0,
			);

			// Check if this show was watched by this viewer.
			if ( has_term( $viewer, 'wpst_viewer', $show ) ) {
				$this_viewers_shows[ $show->ID ]['count'] = $this_viewers_shows[ $show->ID ]['count'] + get_post_meta( $show->ID, 'wpst_show_count', true );
			}
		}

		return $this_viewers_shows;
	}

	/**
	 * Handles the viewings for each show. Gets the viewers for all the passed shows & records the viewings for each viewer to the parent show.
	 *
	 * @since  0.6.0
	 * @param  array $shows          Array of WP_Post objects for a particular show.
	 * @param  int   $parent_show_id The post ID of the parent show.
	 */
	private function update_viewings( $shows, $parent_show_id ) {
		// Get the viewers of all the passed shows.
		$viewers = $this->get_viewers_for_shows( $shows );

		// This method needs to:
		// 1. Get the viewers of all the passed shows.
		// 2. Record viewings for each viewer to the $parent_show_id as meta entries.
	}

	/**
	 * Get the viewer terms for an array of passed shows.
	 *
	 * @since  0.6.0
	 * @param  array $shows Array of WP_Post objects for a particular show.
	 * @return array        Array of viewer terms.
	 */
	private function get_viewers_for_shows( $shows ) {
		foreach ( $shows as $show ) {
			$viewers[] = wp_get_post_terms( $show->ID, 'wpst_viewer' );
		}

		return $viewers;
	}

	/**
	 * Get the total count for a particular show across all instances of that show.
	 *
	 * @since  0.6.0
	 * @param  array $show_group Array of WP_Post objects for a particular show.
	 * @return int               Total count of actual watches for the current show.
	 */
	private function get_show_count( $show_group ) {
		$count = 0;
		foreach ( $show_group as $show ) {
			$watched = get_post_meta( $show->ID, 'wpst_show_count', true );
			$count = $count + absint( $watched );
		}

		return $count;
	}

	/**
	 * Delete duplicate shows when we're done.
	 *
	 * @since  0.6.0
	 * @param  array $shows   An array of show WP_Post objects.
	 * @param  int   $omit_id The post ID of the first show in the series that we're leaving.
	 */
	private function prune_show( $shows, $omit_id ) {
		foreach ( $shows as $show_to_delete ) {
			if ( $omit_id == $show_to_delete->ID ) {
				continue;
			}
			wp_delete_post( $show_to_delete->ID );
		}
	}
}
