<?php
namespace Ahn\ConsultantBooking\Models;

defined('ABSPATH') || exit;

/**
 * Class Consultats
 *
 * Model for managing consultants in the Consultant Booking plugin.
 *
 * @package Ahn\ConsultantBooking\Models
 */
class Consultant
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', [$this, 'cb_register_post_type_consultant']);
        add_action('add_meta_boxes', [self::class, 'cb_add_consultant_meta_boxes']);
    }

    /**
     * Register Custom Post Type: Consultant
     */
    public function cb_register_post_type_consultant()
    {
        $labels = [
            'name'               => __('Consultants', 'consultant-booking'),
            'singular_name'      => __('Consultant', 'consultant-booking'),
            'add_new'            => __('Add New', 'consultant-booking'),
            'add_new_item'       => __('Add New Consultant', 'consultant-booking'),
            'edit_item'          => __('Edit Consultant', 'consultant-booking'),
            'new_item'           => __('New Consultant', 'consultant-booking'),
            'view_item'          => __('View Consultant', 'consultant-booking'),
            'view_items'         => __('View Consultants', 'consultant-booking'),
            'search_items'       => __('Search Consultants', 'consultant-booking'),
            'not_found'          => __('No consultants found', 'consultant-booking'),
            'not_found_in_trash' => __('No consultants found in Trash', 'consultant-booking'),
            'all_items'          => __('All Consultants', 'consultant-booking'),
            'archives'           => __('Consultant Archives', 'consultant-booking'),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => true,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'show_in_rest'    => true,
            'rest_base'       => 'consultants',
            'supports'        => ['title', 'editor', 'thumbnail'],
            'has_archive'     => true,
            'rewrite'         => ['slug' => 'consultants'],
            'capability_type' => 'post',
        ];

        register_post_type('cb_consultant', $args);
    }

    /**
     * Add meta boxes for consultant information
     */
    public static function cb_add_consultant_meta_boxes()
    {
        add_meta_box(
            'consultant_info',
            __('Consultant Information', 'consultant-booking'),
            [self::class, 'cb_render_consultant_meta_box'],
            'cb_consultant',
            'normal',
            'default'
        );
    }

    /**
     * Render the consultant information meta box
     */
    public static function cb_render_consultant_meta_box($post)
    {
        wp_nonce_field('save_consultant_meta', 'consultant_meta_nonce');

        // Get saved values
        $socials = get_post_meta($post->ID, '_consultant_socials', true);
        $socials = is_array($socials) ? $socials : [];

        $availability = get_post_meta($post->ID, '_consultant_availability', true);
        $availability = is_array($availability) ? $availability : [];

        $phone       = get_post_meta($post->ID, '_consultant_phone', true);
        $email       = get_post_meta($post->ID, '_consultant_email', true);
        $price       = get_post_meta($post->ID, '_consultant_fee', true);
        $designation = get_post_meta($post->ID, '_consultant_designation', true);

        echo '<label>Designation / Specialty:</label>';
        echo '<input type="text" name="consultant_designation" value="' . esc_attr($designation) . '" /><br>';

        echo '<label>Phone: </label><input type="text" name="consultant_phone" value="' . esc_attr($phone) . '" /><br>';
        echo '<label>Email: </label><input type="email" name="consultant_email" value="' . esc_attr($email) . '" /><br>';
        echo '<label>Visiting Price: </label><input type="number" name="consultant_fee" value="' . esc_attr($price) . '" /><br><br>';

        echo '<h4>Social Media Links</h4>';
        echo '<div id="social-repeater">';
        foreach ($socials as $i => $s) {
            echo '<div class="social-row">';
            self::social_dropdown($s['platform'] ?? '', $s['url'] ?? '', $i);
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-social">Add Social Link</button><br><br>';

        echo '<h4>Weekly Availability</h4>';
        echo '<div id="availability-container">';
        foreach ($availability as $i => $a) {
            self::availability_row($a['day'] ?? '', $a['from'] ?? '', $a['to'] ?? '', $i);
        }
        echo '</div>';
        echo '<button type="button" id="add-availability">Add Availability</button>';
    }

    /**
     * Render a single social media dropdown row
     */
    private static function social_dropdown($platform = '', $url = '', $index = 0)
    {
        $platforms = [
            'fa-facebook-f'  => 'Facebook',
            'fa-linkedin-in' => 'LinkedIn',
            'fa-instagram'   => 'Instagram',
            'fa-youtube'     => 'YouTube',
        ];
        echo '<select name="consultant_socials[' . $index . '][platform]">';
        foreach ($platforms as $key => $val) {
            $selected = $platform === $key ? 'selected' : '';
            echo "<option value='$key' $selected>$val</option>";
        }
        echo '</select>';
        echo '<input type="url" name="consultant_socials[' . $index . '][url]" value="' . esc_attr($url) . '" placeholder="https://..." />';
        echo '<button type="button" class="remove-row">Remove</button>';
    }

    /**
     * Render a single availability row
     */
    private static function availability_row($day = '', $from = '', $to = '', $index = 0)
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        echo '<div class="availability-row">';
        echo '<label>Day:</label>';
        echo '<select name="consultant_availability[' . $index . '][day]">';
        foreach ($days as $d) {
            $selected = $d === $day ? 'selected' : '';
            echo "<option value='$d' $selected>$d</option>";
        }
        echo '</select>';

        echo '<label>From:</label>';
        echo '<input type="time" name="consultant_availability[' . $index . '][from]" value="' . esc_attr($from) . '" />';

        echo '<label>To:</label>';
        echo '<input type="time" name="consultant_availability[' . $index . '][to]" value="' . esc_attr($to) . '" />';

        echo '<button type="button" class="remove-row">Remove</button>';
        echo '</div>';
    }

}
