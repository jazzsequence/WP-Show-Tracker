<?php
/**
 * WP Show Tracker Shortcodes
 * @version <%= version %>
 * @package WP Show Tracker
 */

class WPST_Shortcodes {
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
		add_shortcode( 'wpst', array( $this, 'shortcode' ) );
	}

	/**
	 * Renders the shortcode to display stats.
	 * @param  array  $atts    Shortcode attributes array.
	 * @param  string $content Content inside the shortcode. Not used so set to null.
	 * @return string          Shortcode output.
	 */
	public function shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'viewer' => '',
			'from'   => 'week',
		), $atts );

		$show_count_message = '<div class="wpst-shows"><table class="wpst-shows-for-user-table">
			<thead>
				<th>' . __( 'Viewer', 'wp-show-tracker' ) . '</th>
				<th>' . __( 'Count', 'wp-show-tracker' ) . '</th>
			</thead>
		<tbody>';

		$has_viewer = true;

		// Get the viewer, make sure it's valid.
		$viewer_slug = sanitize_title( $atts['viewer'] );
		if ( 'all' !== $viewer_slug && $viewer = get_term_by( 'slug', $viewer_slug, 'wpst_viewer' ) ) {
			$show_count_message .= $this->the_show_count_for_viewer( $viewer, $viewer_slug, $atts['from'] );
		} elseif ( 'all' == $viewer_slug ) {
			$viewers = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );
			foreach ( $viewers as $viewer ) {
				$show_count_message .= $this->the_show_count_for_viewer( $viewer, $viewer_slug, $atts['from'] );
			}
		} else {
			$show_count_message .= '<tr colspan=2><td>' . __( 'No valid viewer to display.', 'wp-show-tracker' ) . '</td></tr>';
			$has_viewer = false;
		}

		$show_count_message .= '</tbody></table>';

		// Only display the show count message if there was a viewer.
		if ( $has_viewer ) {
			$show_count_message .= '<div class="alignright"><em>';
			if ( 'alltime' == $atts['from'] ) {
				$show_count_message .= __( 'Total shows watched', 'wp-show-tracker' );
			} else {
				$start_day = ( 'week' == $atts['from'] ) ? strtotime( sprintf( 'last %s', wpst()->helpers->get_start_day() ) ) : strtotime( $atts['from'] );
				$start     = ( strtotime( 'today' ) == strtotime( $start_day ) ) ? strtotime( 'today midnight' ) : $start_day;
				$show_count_message .= sprintf( __( 'Shows watched since %s', 'wp-show-tracker' ), date( get_option( 'date_format' ), $start ) );
			}
			$show_count_message .= '</em></div>';
		}

		$show_count_message .= '</div>';

		$output = $show_count_message;

		// Render the shortcode output.
		ob_start();
		echo $output; // WPCS: XSS ok. Everything is already sanitized.
		return ob_get_clean();
	}

	/**
	 * Renders the output of the show count this week for viewer.
	 * @param  object $viewer The viewer WP_Term object.
	 * @param  string $slug   The viewer term slug.
	 * @param  string $from   A from date, day or time.
	 * @return string         The output to display.
	 */
	private function the_show_count_for_viewer( $viewer, $slug, $from ) {
		$show_count = wpst()->helpers->get_show_count_for( $slug, $from );
		$output = '<tr class="show-count" id="show-count-for-' . $slug . '">';
		$output .= '<td>' . esc_attr( $viewer->name ) . '</td> ';
		$output .= '<td>' . absint( $show_count ) . '</td>';
		$output .= '</tr>';

		return $output;
	}
}
