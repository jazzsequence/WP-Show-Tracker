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
	 * @since 0.5.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.5.0
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
	 * @since  0.5.0
	 * @return void
	 */
	public function hooks() {
		add_shortcode( 'wpst', array( $this, 'wpst_shortcode' ) );
		add_shortcode( 'wpst-stats', array( $this, 'stats_shortcode' ) );
		add_shortcode( 'wpst-most-watched', array( $this, 'most_watched_shortcode' ) );
		add_shortcode( 'wp-show-tracker', array( $this, 'show_tracker_form' ) );
		add_action( 'cmb2_after_init', array( $this, 'handle_frontend_new_post_form_submission' ) );
	}

	/**
	 * Renders the shortcode to display show counts.
	 * @since  0.5.0
	 * @param  array  $atts    Shortcode attributes array.
	 * @param  string $content Content inside the shortcode. Not used so set to null.
	 * @return string          Shortcode output.
	 */
	public function wpst_shortcode( $atts, $content = null ) {
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
			$show_count_message .= '<tr><td colspan=2>' . __( 'No valid viewer to display.', 'wp-show-tracker' ) . '</td></tr>';
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
	 * Renders the shortcode to display stats.
	 * @since  0.5.1
	 * @param  array  $atts    Shortcode attributes array.
	 * @param  string $content Content inside the shortcode. Not used so set to null.
	 * @return string          Shortcode output.
	 */
	public function stats_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'viewer' => '',
			'since'  => 'week',
		), $atts );

		$viewer = get_term_by( 'slug', sanitize_title( $atts['viewer'] ), 'wpst_viewer' );

		$high_count = wpst()->helpers->get_highest_show_count();

		$stats = '<div class="wpst-stats"><table class="wpst-stats-for-user-table">';
		$stats .= '<thead>';
		if ( $viewer ) {
			$stats .= '<tr><th colspan=2><div class="aligncenter">' . sprintf( __( 'Shows for %s', 'wp-show-tracker' ), str_replace( '-', ' ', ucwords( $atts['viewer'] ) ) ) . '</div></th></tr>';
		}
		$stats .= '
				<tr>
					<th>' . __( 'Title', 'wp-show-tracker' ) . '</th>
					<th>' . __( 'Count', 'wp-show-tracker' ) . '</th>
				</tr>
			</thead>
		<tbody>';

		$shows = ( $viewer ) ? wpst()->helpers->get_unique_show_list( $viewer->slug ) : array();

		// Loop through our shows.
		foreach ( $shows as $show ) {
			// Filter out empty title strings.
			if ( '' !== $show ) {
				$show_count = wpst()->helpers->count_unique_shows( $show, $viewer->slug );
				$stats .= '
				<tr>
					<td>' . esc_attr( $show ) . '</td>
					<td>
						<div class="progress-bar-wrapper">
							<div class="count">' . absint( $show_count ) . '</div>
							<div class="progress">
								<span class="progress-bar" style="width: ' . ( absint( $show_count ) / absint( $high_count ) * 100 ) . '%"></span>
							</div>
						</div>
					</td>
				</tr>';
			}
		}

		$stats .= '</tbody></table>';

		// Only display the show count message if there was a viewer.
		if ( $viewer ) {
			$stats .= '<div class="alignright"><em>';
			if ( 'alltime' == $atts['since'] || '' == $atts['since'] ) {
				$stats .= __( 'Total shows watched', 'wp-show-tracker' );
			} else {
				$start_day = ( 'week' == $atts['since'] ) ? strtotime( sprintf( 'last %s', wpst()->helpers->get_start_day() ) ) : strtotime( $atts['since'] );
				$start     = ( strtotime( 'today' ) == strtotime( $start_day ) ) ? strtotime( 'today midnight' ) : $start_day;
				$stats .= sprintf( __( 'Shows watched since %s', 'wp-show-tracker' ), date( get_option( 'date_format' ), $start ) );
			}
			$stats .= '</em></div>';
		} else {
			$stats .= '<div class="alignright"><em>' . __( 'No valid viewer to display.', 'wp-show-tracker' ) . '</em></em>';
		}

		$stats .= '</div>';

		$output = $stats;

		// Render the shortcode output.
		ob_start();
		echo $output; // WPCS: XSS ok. Everything is already sanitized.
		return ob_get_clean();
	}

	public function most_watched_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'viewer' => 'all',
		), $atts );

		$viewer = ( 'all' == $atts['viewer'] ) ? false : get_term_by( 'slug', sanitize_title( $atts['viewer'] ), 'wpst_viewer' );

		$output = '<div class="wpst-most-watched"><table class="wpst-most-watched-for-';
		$output .= ( $viewer ) ? $viewer->slug . '-table">' : 'everyone-table">';
		$output .= '<thead>';
		$output .= ( $viewer ) ? '<tr><th colspan=2><div class="aligncenter">' . sprintf( __( 'Most Watched Show for %s', 'wp-show-tracker' ), str_replace( '-', ' ', ucwords( $atts['viewer'] ) ) ) . '</div></th></tr>' : '<tr><th colspan=2><div class="aligncenter">' . __( 'Most Watched Show', 'wp-show-tracker' ) . '</div></th></tr>';
		$output .= '
				<tr>
					<th>' . __( 'Title', 'wp-show-tracker' ) . '</th>
					<th>' . __( 'Count', 'wp-show-tracker' ) . '</th>
				</tr>
			</thead>
		<tbody>';

		$show_count = wpst()->helpers->get_highest_show_count( $viewer->slug );
		$show_title = wpst()->helpers->get_most_watched( $viewer->slug );
		$output = '<tr class="show-count" id="show-count-for-' . $slug . '">';
		$output .= '<td>' . wp_kses_post( $show_title ) . '</td> ';
		$output .= '<td>' . absint( $show_count ) . '</td>';
		$output .= '</tr>';

		// Render the shortcode output.
		ob_start();
		echo $output; // WPCS: XSS ok. Everything is already sanitized.
		return ob_get_clean();
	}

	/**
	 * Renders the output of the show count this week for viewer.
	 * @since  0.5.0
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


	/**
	 * Handle the cmb-frontend-form shortcode
	 * @since  0.1.0
	 * @param  array $atts Array of shortcode attributes.
	 * @return string      Form html
	 */
	public function show_tracker_form( $atts = array() ) {

		// Get CMB2 metabox object.
		$cmb = $this->cmb2_get();

		// Get $cmb object_types.
		$post_types = $cmb->prop( 'object_types' );

		// Current user.
		$user_id = get_current_user_id();

		// Parse attributes.
		$atts = shortcode_atts( array(
			'post_author' => $user_id ? $user_id : 1, // Current user, or admin.
			'post_status' => 'publish',
			'post_type'   => 'wpst_show',
		), $atts, 'wp-show-tracker' );

		/*
		 * Let's add these attributes as hidden fields to our cmb form
		 * so that they will be passed through to our form submission
		 */
		foreach ( $atts as $key => $value ) {
			$cmb->add_hidden_field( array(
				'field_args'  => array(
					'id'    => "atts[$key]",
					'type'  => 'hidden',
					'default' => $value,
				),
			) );
		}

		// Initiate our output variable.
		$output = '';

		// Get any submission errors.
		if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
			// If there was an error with the submission, add it to our ouput.
			$output .= apply_filters( 'wpst_submission_error_message', '<h3>' . sprintf( __( 'There was an error in the submission: %s', 'wp-show-tracker' ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h3>' );
		}

		// If the post was submitted successfully, notify the user.
		if ( isset( $_GET['post_submitted'] ) && ( $post = get_post( absint( $_GET['post_submitted'] ) ) ) ) {

			// Get submitter's name.
			$name = get_user_meta( $user_id, 'display_name' );
			$name = $name ? ' '. $name : '';

			$terms = wp_get_object_terms( $post->ID, 'wpst_viewer' );
			$viewer = $terms[0]->name;

			// Add notice of submission to our output.
			$output .= apply_filters( 'wpst_successful_post_message', '<h3>' . sprintf( __( 'Thank you%1$s, %2$s has been entered for %3$s.', 'wds-post-submit' ), esc_html( $name ), '<span class="show-title" id="' . $post->post_name . '-' . $post->ID . '"><em>' . $post->post_title . '</em></span>', $viewer ) . '</h3>' );
		}

		$output .= apply_filters( 'wpst_before_show_form', '' );

		// Get our form.
		$output .= apply_filters( 'wpst_cmb2_post_form', cmb2_get_metabox_form( $cmb, 'fake-oject-id', array( 'save_button' => __( 'Submit Show', 'wp-show-tracker' ) ) ) );

		return $output;
	}

	/**
	 * Gets the front-end-post-form cmb instance
	 * @since  0.1.0
	 * @return CMB2 object
	 */
	public function cmb2_get() {
		// Use ID of metabox in wpst_show post type. See WPST_Show.
		$metabox_id = 'wpst_show_metabox';

		// Post/object ID is not applicable since we're using this form for submission.
		$object_id  = 'fake-oject-id';

		// Get CMB2 metabox object.
		return cmb2_get_metabox( $metabox_id, $object_id );
	}


	/**
	 * Sanitizes the viewer slug and makes sure the term exists.
	 * @since  0.1.0
	 * @param  string $viewer The slug for the viewer passed from the form.
	 * @return string         The sanitized viewer slug.
	 */
	public function sanitize_viewer( $viewer = '' ) {
		// If no viewer is passed, we have a problem.
		if ( '' == $viewer ) {
			return new WP_Error( 'post_data_missing', __( 'Show needs a viewer.', 'wp-show-tracker' ) );
		}

		$viewer = sanitize_title( $viewer );

		if ( ! term_exists( $viewer, 'wpst_viewer' ) ) {
			return new WP_Error( 'post_data_missing', __( 'Viewer does not exist.', 'wp-show-tracker' ) );
		}

		return $viewer;
	}

	/**
	 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
	 * @since  0.1.0
	 * @return mixed
	 */
	public function handle_frontend_new_post_form_submission() {

		// If no form submission, bail.
		if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
			return false;
		}

		// Get CMB2 metabox object.
		$cmb = $this->cmb2_get();

		$post_data = array();

		// Get our shortcode attributes and set them as our initial post_data args.
		if ( isset( $_POST['atts'] ) ) {
			foreach ( (array) $_POST['atts'] as $key => $value ) {
				$post_data[ $key ] = sanitize_text_field( $value );
			}
			unset( $_POST['atts'] );
		}

		// Check security nonce.
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
			return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( 'Security check failed.', 'wp-show-tracker' ) ) );
		}

		// Check title submitted.
		if ( empty( $_POST['submitted_post_title'] ) ) {
			return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'New post requires a title.', 'wp-show-tracker' ) ) );
		}

		// And that the title is not the default title.
		if ( $cmb->get_field( 'submitted_post_title' )->default() == $_POST['submitted_post_title'] ) {
			return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'Please enter a new title.', 'wp-show-tracker' ) ) );
		}

		// Make sure we have a viewer set.
		if ( $cmb->get_field( 'wpst_show_viewer' )->default() == $_POST['wpst_show_viewer'] ) {
			return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'Show needs a viewer.', 'wp-show-tracker' ) ) );
		}

		/**
		 * Fetch sanitized values
		 */
		$sanitized_values                     = $cmb->get_sanitized_values( $_POST );

		// Set our post data arguments.
		$post_data['post_title']       = $sanitized_values['submitted_post_title'];
		$post_data['wpst_show_viewer'] = $this->sanitize_viewer( $_POST['wpst_show_viewer'] );
		$post_data['post_content']     = ''; // No post content but can't be NULL.
		unset( $sanitized_values['submitted_post_title'] );

		// Create the new post.
		$new_submission_id = wp_insert_post( $post_data, true );

		// If we hit a snag, update the user.
		if ( is_wp_error( $new_submission_id ) ) {
			return $cmb->prop( 'submission_error', $new_submission_id );
		}

		/**
		 * Other than post_type and post_status, we want
		 * our uploaded attachment post to have the same post-data
		 */
		unset( $post_data['post_type'] );
		unset( $post_data['post_status'] );

		// Loop through remaining (sanitized) data, and save to post-meta.
		foreach ( $sanitized_values as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = array_filter( $value );
				if ( ! empty( $value ) ) {
					update_post_meta( $new_submission_id, $key, $value );
				}
			} else {
				update_post_meta( $new_submission_id, $key, $value );
			}

			// Add the viewer to the show.
			wp_set_object_terms( $new_submission_id, $post_data['wpst_show_viewer'], 'wpst_viewer' );
		}

		/*
		 * Redirect back to the form page with a query variable with the new post ID.
		 * This will help double-submissions with browser refreshes
		 */
		wp_redirect( esc_url_raw( add_query_arg( 'post_submitted', $new_submission_id ) ) );
		exit;
	}
}
