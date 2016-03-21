<?php
/**
 * CMB2 Front End Form
 *
 * Fork of CMB2 front-end form snippet available here: https://github.com/WebDevStudios/CMB2-Snippet-Library/blob/master/front-end/cmb2-front-end-submit.php
 *
 * @link http://webdevstudios.com/2015/03/30/use-cmb2-to-create-a-new-post-submission-form/ Original tutorial
 * @package WP_Show_Tracker
 */

/**
 * Gets the front-end-post-form cmb instance
 *
 * @return CMB2 object
 */
function wpst_frontend_cmb2_get() {
	// Use ID of metabox in wpst_show post type. See WPST_Show.
	$metabox_id = 'wpst_show_metabox';

	// Post/object ID is not applicable since we're using this form for submission.
	$object_id  = 'fake-oject-id';

	// Get CMB2 metabox object.
	return cmb2_get_metabox( $metabox_id, $object_id );
}

/**
 * Handle the cmb-frontend-form shortcode
 *
 * @param  array $atts Array of shortcode attributes.
 * @return string      Form html
 */
function wpst_do_frontend_form_submission_shortcode( $atts = array() ) {

	// Get CMB2 metabox object.
	$cmb = wpst_frontend_cmb2_get();

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
add_shortcode( 'wp-show-tracker', 'wpst_do_frontend_form_submission_shortcode' );

/**
 * Sanitizes the viewer slug and makes sure the term exists.
 * @param  string $viewer The slug for the viewer passed from the form.
 * @return string         The sanitized viewer slug.
 */
function wpst_sanitize_viewer( $viewer = '' ) {
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
 *
 * @return mixed
 */
function wpst_handle_frontend_new_post_form_submission() {

	// If no form submission, bail.
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}

	// Get CMB2 metabox object.
	$cmb = wpst_frontend_cmb2_get();

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
	$sanitized_values['wpst_show_viewer'] = wpst_sanitize_viewer( $_POST['wpst_show_viewer'] );

	// Set our post data arguments.
	$post_data['post_title']   = $sanitized_values['submitted_post_title'];
	$post_data['post_content'] = ''; // No post content but can't be NULL.
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
	}

	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
	wp_redirect( esc_url_raw( add_query_arg( 'post_submitted', $new_submission_id ) ) );
	exit;
}
add_action( 'cmb2_after_init', 'wpst_handle_frontend_new_post_form_submission' );
