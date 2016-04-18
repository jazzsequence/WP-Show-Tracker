<?php
/**
 * Template Tags
 *
 * Public helper functions that can be used by theme- or plugin-developers to extend or customize WP Show Tracker.
 *
 * @package WP_Show_Tracker
 */

/**
 * Get the start day for the week. Defaults to Sunday.
 * @return string The start day of the week.
 */
function wpst_get_start_day() {
	return wp_show_tracker()->helpers->get_start_day();
}

/**
 * Get the max shows for the viewer. Defaults to 0 (unlimited).
 * @param  string $viewer A valid slug for the viewer term.
 * @return int            The number of shows for that viewer. Default is 0.
 */
function wpst_get_max_shows_for( $viewer = '' ) {
	// Check if the viewer exists and return a wp_die message if it doesn't.
	wp_show_tracker()->helpers->viewer_die( $viewer );

	// If we got here, we can return the max shows for the viewer.
	return wp_show_tracker()->helpers->get_max_shows_for( $viewer );
}

/**
 * Returns the show count for the current week for the given viewer.
 * @param  string $viewer The wpst_viewer term slug.
 * @param  string $from   A from date, day or time. Gets run through strtotime so almost any valid time string will work here.
 * @return int            The total number of shows watched by this viewer this week.
 */
function wpst_get_show_count_this_week_for( $viewer = '', $from = '' ) {
	// Check if the viewer exists and return a wp_die message if it doesn't.
	wp_show_tracker()->helpers->viewer_die( $viewer );

	return wp_show_tracker()->helpers->get_show_count_this_week_for( $viewer, $from );
}
