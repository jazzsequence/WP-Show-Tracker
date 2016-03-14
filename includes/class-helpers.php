<?php
/**
 * WP Show Tracker Helpers
 * @version <%= version %>
 * @package WP Show Tracker
 */

class WPST_Helpers {
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
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 */
	public function hooks() {
	}

	/**
	 * Get the max shows for the viewer. Defaults to 0 (unlimited).
	 * @param  string $viewer A valid slug for the viewer term.
	 * @return int            The number of shows for that viewer. Default is 0.
	 */
	public function get_max_shows_for_viewer( $viewer ) {
		return ( wp_show_tracker()->options->get_option( $viewer . '-max-shows' ) ) ? absint( wp_show_tracker()->options->get_option( $viewer . '-max-shows' ) ) : 0;
	}

	/**
	 * Get the start day for the week. Defaults to Sunday.
	 * @return string The start day of the week.
	 */
	public function get_start_day() {
		return ( wp_show_tracker()->options->get_option( 'wpst_start_day' ) ) ? esc_attr( wp_show_tracker()->options->get_option( 'wpst_start_day' ) ) : 'sunday';
	}

	/**
	 * Returns the show count for the current week for the given viewer.
	 * @param  string $viewer The wpst_viewer term slug.
	 * @return int            The total number of shows watched by this viewer this week.
	 */
	public function get_show_count_this_week_for( $viewer ) {
		// Get the shows for this week.
		$shows = get_posts( array(
			'post_type'   => 'wpst_show',
			'nopaging'    => true,
			'wpst_viewer' => $viewer,
			'meta_query'  => array(
				array(
					'key'     => 'wpst_show_date',
					'value'   => array( strtotime( sprintf( 'last %s', $this->get_start_day() ) ), strtotime( 'today' ) ),
					'compare' => 'BETWEEN',
				),
			),
		) );

		// Loop through this week's shows and tally up the total count.
		$count = 0;
		foreach ( $shows as $show ) {
			if ( $show_count = get_post_meta( $show['ID'], 'wpst_show_count', true ) ) {
				$count = $count + absint( $show_count );
			}
		}

		return $count;
	}
}
