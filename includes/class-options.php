<?php
/**
 * WP Show Tracker Options
 * @version 0.1.0
 * @package WP Show Tracker
 */

require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

class WPST_Options {
	/**
	 * Parent plugin class
	 *
	 * @var    class
	 * @since  NEXT
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $key = 'wp_show_tracker_options';

	/**
	 * Options page metabox id
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $metabox_id = 'wp_show_tracker_options_metabox';

	/**
	 * Options Page title
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

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

		$this->title = __( 'Show Tracker', 'wp-show-tracker' );
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function admin_init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function add_options_page() {
		$this->options_page = add_submenu_page(
			'edit.php?post_type=wpst_show',
			sprintf( __( '%s Options', 'wp-show-tracker' ), $this->title ),
			__( 'Options', 'wp-show-tracker' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);

		// Include CMB CSS in the head to avoid FOUC.
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add custom fields to the options page.
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function add_options_page_metabox() {

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove.
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		// Get the viewers and loop through them.
		$viewers = get_terms( 'wpst_viewer', array( 'hide_empty' => false ) );

		if ( ! empty( $viewers ) && ! is_wp_error( $viewers ) ) :

			foreach ( $viewers as $viewer ) {
				$cmb->add_field( array(
					'name'    => sprintf( __( '%s Max Shows', 'wp-show-tracker' ), $viewer->name ),
					'desc'    => sprintf( __( 'Maximum number of shows for %s. Use 0 for unlimited.', 'wp-show-tracker' ), $viewer->name ),
					'id'      => $viewer->slug . '-max-shows', // no prefix needed
					'type'    => 'text_small',
					'default' => '0',
				) );
			}

		else :

			$cmb->add_field( array(
				'id'   => 'text_only',
				'desc' => sprintf( __( 'You need to set up your show tracker with some Viewers first. Click on the <a href="%s">Viewers</a> link under your <a href="%s">Shows</a> menu to add Viewers.', 'wp-show-tracker' ), 'edit-tags.php?taxonomy=wpst_viewer&post_type=wpst_show', 'edit.php?post_type=wpst_show' ),
				'type' => 'text',
				'attributes' => array( 'hidden' => 'hidden' ),
			) );

		endif;

		$cmb->add_field( array(
			'name'    => __( 'Start Week on', 'wp-show-tracker' ),
			'desc'    => __( 'What day should the week start on?', 'wp-show-tracker' ),
			'default' => 'sunday',
			'options' => array(
				'sunday'    => __( 'Sunday',' wp-show-tracker' ),
				'monday'    => __( 'Monday',' wp-show-tracker' ),
				'tuesday'   => __( 'Tuesday',' wp-show-tracker' ),
				'wednesday' => __( 'Wednesday',' wp-show-tracker' ),
				'thursday'  => __( 'Thursday',' wp-show-tracker' ),
				'friday'    => __( 'Friday',' wp-show-tracker' ),
				'saturday'  => __( 'Saturday',' wp-show-tracker' ),
			),
			'type'    => 'select',
			'id'      => 'wpst_start_day',
		) );

	}
}
