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
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.1.0
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_filter( 'wpst_before_show_form', array( $this, 'display_show_count_for_viewers' ) );
		add_filter( 'wpst_cmb2_post_form', array( $this, 'maybe_hide_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue those scripts!
	 * @since 0.2.0
	 */
	public function enqueue_scripts() {
		$min = '.min';

		// Don't use minified js/css if DEBUG is on.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$min = '';
		}

		if ( ! is_admin() ) {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'show-tracker', wpst()->url . 'assets/js/show-tracker' . $min . '.js', array(), wpst()->version, true );
			wp_enqueue_style( 'show-tracker', wpst()->url . 'assets/css/show-tracker' . $min . '.css', array(), wpst()->version, 'screen' );
			wp_localize_script( 'show-tracker', 'showtracker', array(
				'hidden_viewers' => $this->hide_viewers(),
				'autosuggest'    => $this->autosuggest_terms(),
				'wp_debug'       => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			) );
		}
	}

	/**
	 * Returns an array of unique show names.
	 * @since  0.3.0
	 * @return array Unique show names for all shows fetched from the WP-API.
	 */
	public function autosuggest_terms() {
		// Get the shows from WP-API.
		$request = wp_remote_get( home_url( '/wp-json/wp/v2/shows?filter[posts_per_page]=1000' ) );

		if ( $request && ! is_wp_error( $request ) ) {
			return $this->unique( $request, true );
		}

		return new WP_Error( 'wpst_remote_get_fail', __( 'WordPress remote get operation failed.', 'wp-show-tracker' ), $request );
	}

	/**
	 * Returns an array of unique show names for a given viewer.
	 * @since  0.5.1
	 * @param  string $viewer The viewer slug.
	 * @return array          Unique show names for all shows for the passed viewer.
	 */
	public function get_unique_show_list( $viewer ) {

		if ( false === ( $shows = get_transient( 'unique_show_list_for_' . $viewer ) ) ) {
			// Get the shows.
			$shows = $this->get_show_list( array( 'wpst_viewer' => $viewer ) );

			set_transient( 'unique_show_list_for_' . $viewer, $shows, 24 * HOUR_IN_SECONDS );
		}

		if ( $shows && ! is_wp_error( $shows ) ) {
			return $this->unique( $shows );
		}

		return new WP_Error( 'get_unique_show_list_failed', __( 'Get unique show list failed.', 'wp-show-tracker' ), $shows );
	}

	/**
	 * Returns an array of unique show names across all shows for all viewers.
	 * @param  array $atts Any passed custom WP_Query arguments.
	 * @return array       Only unique show names.
	 */
	public function get_show_list( $atts = array() ) {

		if ( false === ( $shows = get_transient( 'wpst_get_all_show_list' ) ) ) {

			// Define the default args. If atts have been passed, use the viewer and posts_per_page for those values (if they exist).
			$args = array(
				'post_type' => 'wpst_show',
				'post_status' => 'publish',
				'wpst_viewer' => ( isset( $atts['wpst_viewer'] ) && term_exists( $atts['wpst_viewer'], 'wpst_viewer' ) ) ? $atts['wpst_viewer'] : array(),
				'posts_per_page' => ( isset( $atts['posts_per_page'] ) ) ? $atts['posts_per_page'] : -1,
			);

			// We're going to do an array merge of all the other passed WP_Query arguments. Reset viewer and posts per page before we do the array merge.
			if ( ! empty( $atts ) ) {
				if ( isset( $atts['wpst_viewer'] ) ) {
					unset( $atts['wpst_viewer'] );
				}
				if ( isset( $atts['posts_per_page'] ) ) {
					unset( $atts['posts_per_page'] );
				}
			}

			// Merge the arguments.
			$args = ( ! empty( $atts ) ) ? array_merge( $atts, $args ) : $args;

			// Get the shows.
			$shows = get_posts( $args );

			set_transient( 'wpst_get_all_show_list', $shows, 24 * HOUR_IN_SECONDS );
		}

		if ( $shows && ! is_wp_error( $shows ) ) {
			return $this->unique( $shows );
		}

		return new WP_Error( 'get_all_show_list_failed', __( 'Get all show list failed.', 'wp-show-tracker' ), $shows );
	}

	/**
	 * Get an array of only the unique show names.
	 * @param  array $shows   An array of show names.
	 * @param  bool  $use_api Whether the passed shows array is coming from the WP-API.
	 * @return array          An array of _unique_ show names from the given shows.
	 */
	private function unique( $shows, $use_api = false ) {

		if ( ! $use_api ) {
			$show_list = array();
			// Build an array of show titles.
			foreach ( $shows as $show ) {
				$show_list[] = $show->post_title;
			}

			// Strip out the duplicate titles.
			$show_list = array_unique( $show_list );
		} else {
			// Decode the json.
			$posts = json_decode( $shows['body'] );

			// Build an array of show titles.
			foreach ( $posts as $show ) {
				$show_list[] = $show->title->rendered;
			}

			// Strip out the duplicate titles.
			$show_list = array_unique( $show_list );
		}

		return $show_list;
	}

	/**
	 * Get a count for how many times the passed show was watched.
	 * @since  0.5.1
	 * @param  string $title  The show's actual title.
	 * @param  string $viewer The viewer slug.
	 * @return int            The total count for this show.
	 */
	public function count_unique_shows( $title, $viewer ) {
		if ( false === ( $unique_shows = get_transient( 'show_count_' . sanitize_title( $title ) . '_for_' . $viewer ) ) ) {
			$unique_shows = new WP_Query( array(
				'post_type' => 'wpst_show',
				'wpst_viewer' => $viewer,
				'post_status' => 'publish',
				's' => $title,
				'posts_per_page' => -1,
			) );

			set_transient( 'show_count_' . sanitize_title( $title ) . '_for_' . $viewer, $unique_shows, 7 * DAY_IN_SECONDS );
		}

		return $unique_shows->found_posts;
	}

	/**
	 * Return the highest show count across all shows.
	 * @return [type]        [description]
	 */
	public function get_highest_show_count() {
		$shows = class_exists( 'WP_REST_Controller' ) ? $this->autosuggest_terms() : $this->get_show_list();

		if ( false === ( $high_count = get_transient( 'wpst_high_count' ) ) ) {
			$high_count = 0;
			$viewers    = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );

			// Loop through all the viewers, then loop through and count the shows for each viewer. Set the high count to the largest number.
			foreach ( $viewers as $viewer ) {
				foreach ( $shows as $show ) {
					$show_count = $this->count_unique_shows( $show, $viewer->slug );
					$high_count = ( $show_count > $high_count ) ? $show_count : $high_count;
				}
			}

			set_transient( 'wpst_high_count', $high_count, 24 * HOUR_IN_SECONDS );
		}

		return $high_count;
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
	 * @since  0.2.0
	 * @return string The start day of the week.
	 */
	public function get_start_day() {
		return ( wpst()->options->get_option( 'wpst_start_day' ) ) ? esc_attr( wpst()->options->get_option( 'wpst_start_day' ) ) : 'sunday';
	}

	/**
	 * Returns the show count for the given viewer. Default range is the last/current week.
	 * @since  0.2.0
	 * @param  string $viewer The wpst_viewer term slug.
	 * @param  string $from   A from date, day or time. Gets run through strtotime so almost any valid time string will work here. Passing 'alltime' will return total count.
	 * @return int            The total number of shows watched by this viewer this week.
	 */
	public function get_show_count_for( $viewer, $from = 'week' ) {

		// Check if today is the start day. If it is, we need to adjust our start/end times.
		$start_day = ( 'week' == $from ) ? strtotime( sprintf( 'last %s', $this->get_start_day() ) ) : strtotime( $from );
		$start     = ( strtotime( 'today' ) == strtotime( $start_day ) ) ? strtotime( 'today midnight' ) : $start_day;
		$end       = ( 'today' == $start_day ) ? strtotime( sprintf( 'next %s', $start_day ) ) : strtotime( 'today' );
		$date_range = ( 'alltime' == $from ) ? array() : array(
			'key'     => 'wpst_show_date',
			'value'   => array( $start, $end ),
			'compare' => 'BETWEEN',
		);

		// Check for cached 'alltime' count.
		$count = ( 'alltime' == $from ) ? get_transient( 'wpst_alltime_for_' . $viewer ) : 0;

		// We don't have a count, calculate the total shows.
		if ( ! $count ) {

			// Get the shows.
			$shows = get_posts( array(
				'post_type'   => 'wpst_show',
				'nopaging'    => true,
				'wpst_viewer' => $viewer,
				'meta_query'  => array( $date_range ),
			) );

			// Loop through shows and tally up the total count.
			foreach ( $shows as $show ) {
				if ( $show_count = get_post_meta( $show->ID, 'wpst_show_count', true ) ) {
					$count = $count + absint( $show_count );
				}
			}

			// Cache alltime counts so we don't need to calculate them every time.
			if ( 'alltime' == $from && false === get_transient( 'wpst_alltime_for_' . $viewer ) ) {
				set_transient( 'wpst_alltime_for_' . $viewer, $count, 24 * HOUR_IN_SECONDS );
			}
		}

		return $count;
	}

	/**
	 * Checks if the viewer term exists.
	 * @since  0.2.0
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
	 * @since  0.2.0
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
	 * @since  0.2.0
	 * @return string A show count for each viewer or an empty string if max is unlimited.
	 */
	public function display_show_count_for_viewers() {
		$output = '';
		$viewers = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );
		foreach ( $viewers as $viewer ) {
			if ( $this->get_max_shows_for_viewer( $viewer->slug ) >= 1 ) {
				$output .= '<div class="alert warn"><p>';

				// Get show count.
				$shows = ( $this->get_max_shows_for_viewer( $viewer->slug ) ) ? sprintf( _n( '%s show', '%s shows', $this->get_max_shows_for_viewer( $viewer->slug ) ), $this->get_max_shows_for_viewer( $viewer->slug ) ) : '';

				// Translators: 1: Viewer. 2: Show count this week. 3: Max shows for viewer.
				$output .= sprintf( __( '%1$s has watched %2$d of %3$s this week.', '%1$s has watched %2$d of %3$d shows this week.', 'wp-show-tracker' ), $viewer->name, $this->get_show_count_for( $viewer->slug ), $shows );

				// Translators: Remaining shows this week.
				$output .= sprintf( ' ' . _n( '%1$d show remains.', '%1$d shows remain.', $this->get_remaining_shows_for( $viewer->slug ), 'wp-show-tracker' ), $this->get_remaining_shows_for( $viewer->slug ) );
				$output .= '</p></div>';
			}
		}

		return $output;
	}

	/**
	 * Return the remaining shows for the passed viewer.
	 * @since  0.2.0
	 * @param  string $viewer The viewer term slug.
	 * @return int            The number of shows remaining for that user.
	 */
	public function get_remaining_shows_for( $viewer ) {
		$show_count = $this->get_show_count_for( $viewer );
		$max_shows  = $this->get_max_shows_for_viewer( $viewer );
		return absint( $max_shows ) - absint( $show_count );
	}

	/**
	 * Checks if the passed viewer has watched the maximum number of shows this week.
	 * @since  0.2.0
	 * @param  string $viewer The viewer term slug.
	 * @return bool           True/false whether the current number of shows is equal to or greater than the max shows.
	 */
	public function watched_max_shows( $viewer ) {
		return $this->get_show_count_for( $viewer ) >= $this->get_max_shows_for_viewer( $viewer );
	}

	/**
	 * Returns an array of viewers to hide because they have reached their maximum number of shows.
	 * @since  0.2.0
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

	/**
	 * Determines if all shows have been watched by all viewers.
	 * @since  0.2.0
	 * @return bool Returns true if all viewers have reached their maximum shows.
	 */
	public function all_shows_watched() {
		$all_viewer_count   = count( get_terms( 'wpst_viewer', array( 'hide_empty' => false ) ) );
		$maxed_show_viewers = 0;
		$hidden_viewers = $this->hide_viewers();

		if ( $hidden_viewers ) {
			foreach ( $hidden_viewers as $viewer => $hidden ) {
				if ( $hidden ) {
					$maxed_show_viewers++;
				}
			}
		}

		return ( $maxed_show_viewers >= $all_viewer_count ) ? true : false;
	}

	/**
	 * Replaces the CMB2 form with a notice if all shows have been watched by all viewers.
	 * @since  0.2.0
	 * @param  string $cmb2_form The CMB2 form that we're filtering (replacing).
	 * @return string            The original CMB2 form or a message stating that all shows have been watched.
	 */
	public function maybe_hide_form( $cmb2_form ) {
		if ( $this->all_shows_watched() ) {
			$cmb2_form = '<div class="all-shows-watched"><p>' . __( 'All shows have been watched for this week by all viewers.', 'wp-show-tracker' ) . '</p></div>';
		}

		return $cmb2_form;
	}
}
