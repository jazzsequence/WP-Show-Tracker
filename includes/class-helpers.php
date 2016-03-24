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
		add_filter( 'wpst_before_show_form', array( $this, 'display_show_count_for_viewers' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue those scripts!
	 * @since NEXT
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			wp_enqueue_script( 'wp-show-tracker', wpst()->url . '/assets/js/maxshows.js', array( 'jquery' ), wpst()->version, true );
			wp_localize_script( 'wp-show-tracker', 'maxshows', array(
				'hidden_viewers' => $this->hide_viewers(),
				// 'hide_submit'    =>
			) );
		}
	}

	/**
	 * Get the max shows for the viewer. Defaults to 0 (unlimited).
	 * @param  string $viewer A valid slug for the viewer term.
	 * @return int            The number of shows for that viewer. Default is 0.
	 */
	public function get_max_shows_for_viewer( $viewer ) {
		return ( wpst()->options->get_option( $viewer . '-max-shows' ) ) ? absint( wpst()->options->get_option( $viewer . '-max-shows' ) ) : 0;
	}

	/**
	 * Get the start day for the week. Defaults to Sunday.
	 * @return string The start day of the week.
	 */
	public function get_start_day() {
		return ( wpst()->options->get_option( 'wpst_start_day' ) ) ? esc_attr( wpst()->options->get_option( 'wpst_start_day' ) ) : 'sunday';
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
			if ( $show_count = get_post_meta( $show->ID, 'wpst_show_count', true ) ) {
				$count = $count + absint( $show_count );
			}
		}

		return $count;
	}

	/**
	 * Checks if the viewer term exists.
	 * @uses   get_term_by    This check uses get_term_by which isn't recommended for resource reasons. As such, internally we never perform this check but it's used in all the template tags to determine if the viewer is a valid viewer.
	 * @param  string $viewer A wpst_viewer term slug.
	 * @return bool           Whether the viewer term exists.
	 */
	public function viewer_exists( $viewer ) {
		$viewer_obj = get_term_by( 'slug', $viewer, 'wpst_viewer' );

		if ( is_wp_error( $viewer_obj ) || ! $viewer_obj ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks to make sure a value was passed and that the viewer exists. If either fail, returns a wp_die message.
	 * @uses   viewer_exists  Relies on the internal method viewer_exists which uses get_term_by to determine if the viewer is a valid taxonomy term.
	 * @param  string $viewer A wpst_viewer term slug.
	 */
	public function viewer_die( $viewer = '' ) {
		// If no viewer was passed, bail.
		if ( '' == $viewer ) {
			wp_die( esc_attr__( 'No viewer was given. You need to pass a valid viewer slug to <code>wpst_get_max_shows_for</code>', 'wp-show-tracker' ), esc_attr__( 'Error in wpst_get_max_shows_for', 'wp-show-tracker' ) );
		}

		// If a viewer was passed but it wasn't valid, bail.
		if ( ! wpst()->helpers->viewer_exists( $viewer ) ) {
			wp_die( esc_attr__( 'A viewer was passed to <code>wpst_get_max_shows_for</code> but that viewer did not exist or was not recognized as a valid viewer.', 'wp-show-tracker' ), esc_attr__( 'Error in wpst_get_max_shows_for', 'wp-show-tracker' ) );
		}
	}

	/**
	 * Display a show count for each viewer above the show submission form.
	 * @return string A show count for each viewer or an empty string if max is unlimited.
	 */
	public function display_show_count_for_viewers() {
		$output = '';
		$viewers = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );
		foreach ( $viewers as $viewer ) {
			if ( $this->get_max_shows_for_viewer( $viewer->slug ) >= 1 ) {
				$output .= sprintf( '<div class="alert warn"><p>' . __( '%1$d of %2$d shows watched for %3$s.', 'wp-show-tracker' ) . '</p></div>', $this->get_show_count_this_week_for( $viewer->slug ), $this->get_max_shows_for_viewer( $viewer->slug ), $viewer->name );
			}
		}

		return $output;
	}

	/**
	 * Checks if the passed viewer has watched the maximum number of shows this week.
	 * @param  string $viewer The viewer term slug.
	 * @return bool           True/false whether the current number of shows is equal to or greater than the max shows.
	 */
	public function watched_max_shows( $viewer ) {
		return $this->get_show_count_this_week_for( $viewer ) >= $this->get_max_shows_for_viewer( $viewer );
	}

	/**
	 * Returns an array of viewers to hide because they have reached their maximum number of shows.
	 * @return mixed Array of viewers who have watched their max shows or false if none have reached max shows.
	 */
	public function hide_viewers() {
		$viewers = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );
		$count = count( $viewers );

		$hide_for = false;
		foreach ( $viewers as $viewer ) {
			if ( $this->watched_max_shows( $viewer->slug ) ) {
				$hide_for[] = $viewer->slug;
			}
		}

		return $hide_for;
	}

	public function maybe_hide_form( $cmb2_form ) {

	}
}
