<?php
/**
 * Booking Form Template
 * 
 * This template is used to display the booking form for a consultant.
 */
defined('ABSPATH') || exit;

if (!$consultant_id || get_post_type($consultant_id) !== 'cb_consultant') {
  return '<div style="padding: 40px; font-size: 20px; color: red;">Invalid consultant selected.</div>';
}
?>

<div class="appointment-form">
  <div class="consultant-info group-row">
    <div class="consultant-image">
    <?php if($consultant_image): ?>
    <img src="<?php echo esc_url($consultant_image); ?>" alt="<?php echo esc_attr($consultant_name); ?>">
    <?php endif; ?>
    </div>
    <div class="consultant-details">
      <h2><?php echo esc_html($consultant_name); ?></h2>
      <p class="designation"><?php echo esc_html($consultant_designation); ?></p>
        <span>Consult Fee:</span>
        <span class="price">৳<?php echo esc_html(number_format($consultant_price, 2)); ?></span>
    </div>
  </div>

  <!-- <?php do_action('cb_before_form'); ?> -->
  <form method="post">
  <input type="hidden" name="consultant_id" value="<?php echo esc_attr($consultant_id); ?>">

  <div class="group-row">
    <div class="column">
      <label for="student_name">Your Name</label>
      <input type="text" name="student_name" required>
    </div>
    <div class="column">
      <label for="student_email">Your Email</label>
      <input type="email" name="student_email" required>
    </div>
  </div>

  <div class="group-row">
    <div class="column">
      <label for="student_phone">Your Phone</label>
      <input type="tel" name="student_phone" required>
    </div>
    <div class="column">
      <label for="appointment_datetime">Preferred Date & Time</label>
      <input type="datetime-local" name="appointment_datetime" required>  
    </div>
  </div>


  <label for="notes">Information</label>
  <textarea name="notes" rows="3" placeholder="Please provide your academic result, IELTS score, desired country"></textarea>

  <label for="payment_method">Payment Method</label>
  <select name="payment_method" required>
    <option value="">Select a payment method</option>
    <option value="cash">Cash on visit</option>
    <option value="online">Online Payment</option>
  </select>
  <br>
  <button type="submit" name="submit_booking">Book Appointment</button>
  </form>
  <!-- <?php do_action('cb_after_form'); ?> -->

</div>