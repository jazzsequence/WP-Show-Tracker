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
		), $atts );

		// Render the shortcode output.
		ob_start();
		echo $output; // WPCS: XSS ok. Everything is already sanitized.
		return ob_get_clean();
	}

	/**
	 * Renders the output of the show count this week for viewer.
	 * @param  object $viewer The viewer WP_Term object.
	 * @param  string $slug   The viewer term slug.
	 * @return string         The output to display.
	 */
	private function get_show_count_this_week_for_viewer( $viewer, $slug ) {
		$show_count_this_week = wpst()->helpers->get_show_count_this_week_for( $viewer_slug );
		$output = '<div class="alert alert-info"><p><span class="show-count" id="show-count-for-' . $viewer_slug . '">';
		$output .= '<label for="' . $viewer_slug . '">' . esc_attr( $viewer->name ) . ':</label>' . absint( $show_count_this_week );
		$output .= '</span></p></div>';

		return $output;
	}
}
