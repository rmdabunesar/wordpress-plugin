<?php
namespace Ahn\ConsultantBooking\Controllers;

defined('ABSPATH') || die('No script kiddies please!');
use Dompdf\Dompdf;

/**
 * Class Booking
 *
 * Booking Controller
 *
 * @package Ahn\ConsultantBooking\Controllers
 */
class Booking
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_filter('manage_cb_booking_posts_columns', [self::class, 'cb_booking_custom_columns']);
        add_action('manage_cb_booking_posts_custom_column', [self::class, 'cb_booking_custom_column_content'], 10, 2);
        add_shortcode('cb_booking_form', [self::class, 'cb_booking_form_shortcode']);
    }

    /**
     * Customize Booking post type columns
     */
    public static function cb_booking_custom_columns($columns)
    {
        return [
            'cb'                   => $columns['cb'],
            'title'                => __('Booking Title', 'consultant-booking'),
            'consultant_name'      => __('Consultant', 'consultant-booking'),
            'student_name'         => __('Student', 'consultant-booking'),
            'appointment_datetime' => __('Date & Time', 'consultant-booking'),
            'payment_method'       => __('Payment', 'consultant-booking'),
        ];
    }

    /**
     * Populate custom columns for Booking post type
     */
    public static function cb_booking_custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'consultant_name':
                echo esc_html(get_post_meta($post_id, '_consultant_name', true));
                break;

            case 'student_name':
                echo esc_html(get_post_meta($post_id, '_student_name', true));
                break;

            case 'appointment_datetime':
                $datetime = get_post_meta($post_id, '_appointment_datetime', true);
                echo esc_html(date('d M Y, h:i A', strtotime($datetime)));
                break;

            case 'payment_method':
                $method = get_post_meta($post_id, '_payment_method', true);
                echo ucfirst(esc_html($method));
                break;
        }
    }

    /**
     * Booking form shortcode handler
     */
    public static function cb_booking_form_shortcode($atts)
    {

        $consultant_id = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;

        $atts = shortcode_atts([
            'consultant_id' => 0,
        ], $atts, 'cb_booking_form');

        ob_start();

        self::cb_handle_booking_submission();

        $consultant_id          = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;
        $consultant_name        = get_the_title($consultant_id);
        $consultant_fee         = get_post_meta($consultant_id, '_consultant_fee', true);
        $consultant_image       = get_the_post_thumbnail_url($consultant_id, 'medium');
        $consultant_designation = get_post_meta($consultant_id, '_consultant_designation', true);

        cb_get_template('form/booking-form', [
            'consultant_id'          => $consultant_id,
            'consultant_name'        => $consultant_name,
            'consultant_fee'         => $consultant_fee,
            'consultant_image'       => $consultant_image,
            'consultant_designation' => $consultant_designation,

        ]);

        return ob_get_clean();
    }

    /**
     * Handle booking form submission
     */
    public static function cb_handle_booking_submission()
    {
        if (isset($_POST['submit_booking'])) {
            $consultant_id   = intval($_POST['consultant_id']);
            $consultant_name = get_the_title($consultant_id);

            $student_name  = sanitize_text_field($_POST['student_name']);
            $student_email = sanitize_email($_POST['student_email']);
            $student_phone = sanitize_text_field($_POST['student_phone']);
            $datetime      = sanitize_text_field($_POST['appointment_datetime']);
            $notes         = sanitize_textarea_field($_POST['notes']);
            $payment       = sanitize_text_field($_POST['payment_method']);

            $consultant_available_times = get_post_meta($consultant_id, '_consultant_availability', true);

            // Validate availability
            $is_available = false;
            if ($consultant_available_times && is_array($consultant_available_times)) {
                $appointment_time = date('H:i', strtotime($datetime));
                $appointment_day  = date('l', strtotime($datetime));

                foreach ($consultant_available_times as $time_slot) {
                    if ($time_slot['day'] === $appointment_day &&
                        $appointment_time >= $time_slot['from'] &&
                        $appointment_time <= $time_slot['to']) {
                        $is_available = true;
                        break;
                    }
                }
            }
            if (! $is_available) {
                echo '<script>alert("Consultant is not available on the selected date. Please choose another time."); window.location.href="' . get_permalink($consultant_id) . '";</script>';
                exit;
            }

            // Prevent overlapping booking
            $slot_duration_minutes = get_option('_cb_default_slot_duration', false);
            $appointment_start     = strtotime($datetime);
            $appointment_end       = $appointment_start + ($slot_duration_minutes * 60);

            $start_bound = date('Y-m-d\TH:i', $appointment_start - ($slot_duration_minutes * 60));
            $end_bound   = date('Y-m-d\TH:i', $appointment_end);

            $existing_bookings = new \WP_Query([
                'post_type'   => 'cb_booking',
                'post_status' => 'publish',
                'meta_query'  => [
                    'relation' => 'AND',
                    [
                        'key'     => 'consultant_id',
                        'value'   => $consultant_id,
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'appointment_datetime',
                        'value'   => [$start_bound, $end_bound],
                        'compare' => 'BETWEEN',
                        'type'    => 'CHAR',
                    ],
                ],
            ]);

            if ($existing_bookings->have_posts()) {
                echo '<script>alert("Time slot is already booked."); window.location.href="' . get_permalink($consultant_id) . '";</script>';
                exit;
            }

            $booking_post = [
                'post_title'  => '',
                'post_type'   => 'cb_booking',
                'post_status' => 'publish',
                'meta_input'  => [
                    '_consultant_name'      => $consultant_name,
                    '_student_name'         => $student_name,
                    '_student_email'        => $student_email,
                    '_student_phone'        => $student_phone,
                    '_appointment_datetime' => $datetime,
                    '_notes'                => $notes,
                    '_payment_method'       => $payment,
                    '_amount'               => floatval($_POST['amount']),
                ],
            ];

            $booking_id = wp_insert_post($booking_post);

            $last_number = get_option('_cb_last_booking_number', 1000);
            $new_number  = $last_number + 1;

            update_post_meta($booking_id, '_booking_order_number', $new_number);
            update_option('_cb_last_booking_number', $new_number);

            // Send booking email with invoice
            self::cb_send_booking_email($booking_id);

            // Update title to "Booking #1003 (John Doe)"
            wp_update_post([
                'ID'         => $booking_id,
                'post_title' => 'Booking #' . $new_number . ' (' . $student_name . ')',
            ]);

            echo '<script>alert("Booking submitted successfully!"); window.location.href="' . get_permalink($consultant_id) . '";</script>';
        }
    }

    /**
     * Send booking email with PDF invoice
     */
    public static function cb_send_booking_email($booking_id)
    {
        $student_email        = get_post_meta($booking_id, '_student_email', true);
        $student_name         = get_post_meta($booking_id, '_student_name', true);
        $consultant_name      = get_post_meta($booking_id, '_consultant_name', true);
        $appointment_datetime = get_post_meta($booking_id, '_appointment_datetime', true);
        $consultant_fee       = get_post_meta($booking_id, '_amount', true);
        $booking_number       = get_post_meta($booking_id, '_booking_order_number', true);

        // Generate PDF invoice
        $dompdf = new Dompdf();
        ob_start();
        cb_get_template('invoice', [
            'student_name'         => $student_name,
            'consultant_name'      => $consultant_name,
            'appointment_datetime' => $appointment_datetime,
            'consultant_fee'       => $consultant_fee,
            'booking_number'       => $booking_number,
        ]);
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        $upload_dir    = wp_upload_dir();
        $custom_folder = $upload_dir['basedir'] . '/cb-invoice';

        if (! file_exists($custom_folder)) {
            wp_mkdir_p($custom_folder);
        }

        $filename = 'invoice_booking_' . sanitize_file_name($booking_number) . '.pdf';
        $pdf_path = $custom_folder . '/' . $filename;

        file_put_contents($pdf_path, $pdf_output);

        $subject     = 'Your Booking Invoice - Booking #' . $booking_number;
        $message     = 'Dear ' . esc_html($student_name) . ",\n\nThank you for your booking. Please find your invoice attached.\n\nBest regards,\nConsultant Booking Team";
        $headers     = ['Content-Type: text/plain; charset=UTF-8'];
        $attachments = [$pdf_path];

        wp_mail($student_email, $subject, $message, $headers, $attachments);

        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }
    }

}
