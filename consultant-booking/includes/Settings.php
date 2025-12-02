<?php
namespace Ahn\ConsultantBooking;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Class
 * 
 * Handles the admin settings page and related functionalities.
 * 
 * @package ConsultantBooking
 */
class Settings {
    private static $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'cb_add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'cb_settings_app_enqueue_assets' ] );
        add_action( 'rest_api_init', [ $this, 'register_settings_api' ] );
    }

    public function cb_add_admin_menu(): void {
        add_menu_page(
            __( 'Appointment', 'consultant-booking' ),
            __( 'Appointment', 'consultant-booking' ),
            'manage_options',
            'cb-booking-settings',
            [ $this, 'render_settings_page' ],
            'dashicons-schedule',
            26
        );

        add_submenu_page(
            'cb-booking-settings',
            __( 'Consultants', 'consultant-booking' ),
            __( 'Consultants', 'consultant-booking' ),
            'edit_posts',
            'edit.php?post_type=cb_consultant'
        );

        add_submenu_page(
            'cb-booking-settings',
            __( 'Booking List', 'consultant-booking' ),
            __( 'Booking List', 'consultant-booking' ),
            'edit_posts',
            'edit.php?post_type=cb_booking'
        );

        add_submenu_page(
            'cb-booking-settings',
            __( 'Settings', 'consultant-booking' ),
            __( 'Settings', 'consultant-booking' ),
            'manage_options',
            'cb-booking-settings',
            [ $this, 'render_settings_page' ]
        );

        remove_submenu_page( 'cb-booking-settings', 'cb-booking-settings' );
    }

    public function render_settings_page(): void {
        $settings_file = CB_PLUGIN_DIR . 'includes/Views/admin/settings.php';
        
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__( 'Appointment Settings', 'consultant-booking' ) . '</h1>';
            echo '<p>' . esc_html__( 'Settings page not found.', 'consultant-booking' ) . '</p></div>';
        }
    }

    public function cb_settings_app_enqueue_assets(string $hook): void {
        if ( 'toplevel_page_cb-booking-settings' !== $hook ) {
            return;
        }

        $handle      = 'cb-settings-app';
        $script_path = CB_PLUGIN_DIR . 'assets/admin/build/index.js';
        $script_url  = CB_PLUGIN_URL . 'assets/admin/build/index.js';
        $asset_file  = CB_PLUGIN_DIR . 'assets/admin/build/index.asset.php';

        $asset_data = file_exists($asset_file)
            ? include $asset_file
            : ['dependencies' => [], 'version' => file_exists($script_path) ? filemtime($script_path) : time()];

        wp_enqueue_script(
            $handle,
            $script_url,
            $asset_data['dependencies'],
            $asset_data['version'],
            true
        );

        wp_enqueue_style('wp-components');

        wp_localize_script(
            $handle,
            'CB_DATA',
            [
                'apiBase' => esc_url_raw( rest_url( 'consultant-booking/v1/settings' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            ]
        );
    } 
    
    public function register_settings_api(): void {
        register_rest_route('consultant-booking/v1', '/settings', [
            [
                'methods'             => 'GET',
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                'callback'            => [$this, 'get_settings'],
            ],
            [
                'methods'             => 'POST',
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                'callback'            => [$this, 'update_settings'],
                'args'                => $this->get_rest_args(),
            ],
        ]);


    } 

    private function get_setting_keys(): array {
        return [
            'booking_enabled'           => false,
            'default_consultant_status' => 'active',
            'default_slot_duration'     => 15,
            'slot_interval_type'        => 'fixed',
            'max_bookings_per_slot'     => 1,
            'admin_email'               => get_option( 'admin_email' ),
            'user_email_subject'        => 'Your appointment is confirmed',
            'notification_sender_name'  => 'Consultant Booking',
            'working_days'              => [],
            'working_hours_start'       => '09:00',
            'working_hours_end'         => '17:00',
            'currency_code'             => 'USD',
            'currency_position'         => 'left',
            'consultants_per_page'      => 10,
            'booking_page_id'           => 0,
            'store_id'               => '',
            'signature_key'          => '',
            'sandbox_mode'           => '1',
        ];
    }

    private function get_rest_args(): array {
        return [
            'booking_enabled' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
            'default_consultant_status' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'default_slot_duration' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'slot_interval_type' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'max_bookings_per_slot' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'admin_email' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_email',
            ],
            'user_email_subject' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'notification_sender_name' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'working_days' => [
                'type'              => 'array',
                'items'             => [
                    'type' => 'string',
                ],
                'sanitize_callback' => [ $this, 'sanitize_working_days' ],
            ],
            'working_hours_start' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'working_hours_end' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'currency_code' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'currency_position' => [
                'type'              => 'string',
                'enum'              => ['left', 'right'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'consultants_per_page' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'booking_page_id' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'store_id' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'signature_key' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'sandbox_mode' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    public function sanitize_working_days( $value ): array {
        return array_map( 'sanitize_text_field', (array) $value );
    }

    public function get_settings(): array {
        $keys = $this->get_setting_keys();
        $data = [];

        foreach ( $keys as $key => $default ) {
            $data[ $key ] = get_option( "_cb_{$key}", $default );
        }

        return $data;
    }

    public function update_settings( \WP_REST_Request $request ): array {
        $data = [];
        $keys = $this->get_setting_keys();

        foreach ( $keys as $key => $default ) {
            if ( $request->has_param( $key ) ) {
                $value = $request->get_param( $key );
                update_option( "_cb_{$key}", $value );
                $data[ $key ] = $value;
            }
        }

        return $data;
    }
}
