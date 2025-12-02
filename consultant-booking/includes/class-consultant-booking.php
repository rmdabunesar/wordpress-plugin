<?php
/**
 * Class Consultant_Booking
 *
 * Main plugin class to initialize the Consultant Booking plugin
 *
 *  @package Ahn\ConsultantBooking
 */

defined( 'ABSPATH' ) || exit;

use Ahn\ConsultantBooking\Models\Consultant;
use Ahn\ConsultantBooking\Controllers\Consultant as ConsultantController;

use Ahn\ConsultantBooking\Models\Booking;
use Ahn\ConsultantBooking\Controllers\Booking as BookingController;

use Ahn\ConsultantBooking\Settings;
use Ahn\ConsultantBooking\TemplatesManager;

use Ahn\ConsultantBooking\Widgets\CbWidgetSearch;

class Consultant_Booking {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );

        add_filter( 'use_block_editor_for_post_type', [ $this, 'force_classic_editor' ], 10, 2 );

        $this->load_models();

        $this->load_controllers();

        add_action( 'admin_enqueue_scripts', [ $this, 'cb_load_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'cb_load_public_assets' ] );

        Settings::get_instance()->init();
        TemplatesManager::init();

        add_action('widgets_init', [ $this, 'register_cb_search_widget' ]);
        add_action('widgets_init', [ $this, 'register_cb__sidebar' ]);
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'consultant-booking', false, CB_PLUGIN_DIR . '/languages/' );
    }

    /**
     * Force Classic Editor for Doctor and Booking post type
     */
    public function force_classic_editor($use_block_editor, $post_type) {
        if ( 'cb_consultant' === $post_type || 'cb_booking' === $post_type) {
            return false;
        }
        return $use_block_editor;
    }
    
    /**
     * Load models
     */
    public function load_models() {
        new Consultant();
        new Booking();
    }

    /**
     * Load controllers
     */
    public function load_controllers() {
        new ConsultantController();
        new BookingController();  
    }

    /**
     * Load Admin Assets
     */
    public function cb_load_public_assets() {
       // css
        wp_enqueue_style('font-awesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css');
        wp_enqueue_style('cb-style', CB_PLUGIN_URL . 'assets/public/css/style.css', [], CB_VERSION);

        // js
        wp_enqueue_script('cb-script', CB_PLUGIN_URL . 'assets/public/js/main.js', [ 'jquery' ], CB_VERSION, true);
        wp_localize_script('cb-script', 'consultantBooking', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('consultant_booking_nonce')
        ]);
    }

    /**
     * Load Frontend Assets
     */
    public function cb_load_admin_assets() {
        // css
        wp_enqueue_style('cb-admin-style', CB_PLUGIN_URL . 'assets/admin/css/admin.css', [], CB_VERSION);

        // js
        wp_enqueue_script('cb-metabox', CB_PLUGIN_URL . 'assets/admin/js/metabox.js', [ 'jquery' ], CB_VERSION, true);
        wp_enqueue_script('cb-admin-script', CB_PLUGIN_URL . 'assets/admin/js/admin.js', ['jquery'], CB_VERSION, true);
    }

    /**
     * Register Consultants Sidebar
     */
    public function register_cb__sidebar() {
            register_sidebar( [
            'name'          => 'Booking Sidebar',
            'id'            => 'booking-sidebar',
            'before_widget' => '<div class="booking-sidebar">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="booking-title">',
            'after_title'   => '</h3>',
        ] );
    }

    /**
     * Register Consultants Search Widget
     */
    public function register_cb_search_widget() {
        register_widget(CbWidgetSearch::class);;
    }





}

/**
 * Initialize the plugin
 */
new Consultant_Booking();
