<?php

function handle_booking_form()
{
    session_start();

    //rate limit form submissions
    $ip_address = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
    $submission_time = time();
    $limit = 1; // Limit for submissions
    $time_frame = 60 * 30; // 30 minutes in seconds

    // Initialize if not set
    if (!isset($_SESSION['submissions'])) {
        $_SESSION['submissions'] = [];
    }

    // Clean up old submissions
    foreach ($_SESSION['submissions'] as $key => $time) {
        if ($submission_time - $time > $time_frame) {
            unset($_SESSION['submissions'][$key]);
        }
    }

    // Check rate limit
    if (count($_SESSION['submissions']) < $limit) {
        $_SESSION['submissions'][] = $submission_time;
        // Process form submission here
    } else {
        // Block submission or return an error
        trigger_error('Booking Form submission error: Rate limit exceeded.', E_USER_ERROR);
        echo "You're doing that too much. Please try again later.";
    }

    $unit_id = $_POST['unit_id'];

    // Check for nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_form_nonce_action')) {
        trigger_error('Booking Form submission error: Nonce check failed.', E_USER_ERROR);
        wp_die('Nonce security check failed');
    }
    // Check for honeypot field
    if (!empty($_POST['honeypot'])) {
        trigger_error('Booking Form submission error: Honeypot field filled.', E_USER_ERROR);
        wp_die('Honeypot security check failed');
    }


    // Check if the form data is set
    if (!isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['move_in_date'])) {
        // Handle the case where the form fields are not set (redirect or display an error message)
        wp_die('Form submission error: Fields are not set.');
    }
    if (!isset($unit_id)) {
        // Handle the case where the first name is not set (redirect or display an error message)
        trigger_error('Booking Form submission error: Unit ID is not set.', E_USER_ERROR);
    }

    // Sanitize each form field
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $booking_link = sanitize_text_field($_POST['booking_link']);

    // Validate each form field
    if (empty($_POST['move_in_date'])) {
        // Handle the error appropriately
        wp_die('Please fill all required fields.');
    }

    $move_in_date = get_move_in_date($_POST);

    $move_in_date_string = construct_move_in_date_string($move_in_date);

    $size_string = construct_size_string($unit_id);

    $type_string = construct_unit_type_string($unit_id);

    // Validate each form field
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($move_in_date)) {
        // Handle the error appropriately
        wp_die('Please fill all required fields.');
    }
    //get information about the unit
    $unit_price = get_post_meta($unit_id, 'price', true);

    $supplier_unit_id = get_post_meta($unit_id, 'supplier_unit_id', true);

    $rel_lokation_id = get_post_meta($unit_id, 'rel_lokation', true);

    $supplier_email = get_post_meta($rel_lokation_id, 'email_address', true);

    $lokation_name = get_the_title($rel_lokation_id);

    $department_address = get_post_meta($rel_lokation_id, 'address', true);

    $supplier_booking_email_disabled = get_post_meta($rel_lokation_id, 'supplier_booking_email_disabled', true);
    $direct_booking_active = get_post_meta($rel_lokation_id, 'direct_booking_active', true);

    //create a new post of the "booking" type
    $booking_post_id = wp_insert_post(array(
        'post_title' => 'ERROR: booking post title not set',
        'post_type' => 'booking',
        'post_status' => 'publish',
        'meta_input' => array(
            'time_of_booking' => date('Y-m-d H:i:s'),
            'customer_first_name' => $first_name,
            'customer_last_name' => $last_name,
            'customer_email_address' => $email,
            'customer_phone' => $phone,
            'move_in_date' => $move_in_date[0],
            'move_in_date_string' => $move_in_date_string,
            'move_in_date_unknown' => $move_in_date[1],
            'supplier_booking_email_disabled' => $supplier_booking_email_disabled,
            'direct_booking_active' => $direct_booking_active,
            'unit_link' => $unit_id,
            'booking_link' => $booking_link,
            'supplier_unit_id' => $supplier_unit_id,
            'supplier_email_address' => $supplier_email,
            'department_name' => $lokation_name,
            'rel_lokation' => $rel_lokation_id,
            'unit_price' => $unit_price,
            'department_address' => $department_address,
            'unit_size_string' => $size_string,
            'unit_type_string' => $type_string,
        )
    ));

    //return success message, include the booking id
    echo json_encode(array(
        'success' => true,
        'booking_id' => $booking_post_id,
    ));

    //update booking post title
    $post_title = "Reservation " . construct_post_title($first_name, $last_name, $type_string, $supplier_unit_id,   $unit_id, $size_string, $booking_post_id);
    wp_update_post(array(
        'ID' => $booking_post_id,
        'post_title' => $post_title,
    ));

    exit();
}

function construct_post_title($first_name, $last_name, $type_string, $supplier_unit_id, $unit_id, $size_string, $booking_post_id)
{
    if ($supplier_unit_id) {
        $unit_id_string = '#' . $unit_id . ' (' . $supplier_unit_id . ')';
    } else {
        $unit_id_string = '#' . $unit_id;
    }
    return $booking_post_id . ': ' . $first_name . ' ' . $last_name . ' - ' . $type_string . ' (' . $unit_id_string . ' - ' . $size_string . ')';
}

function get_move_in_date($post)
{
    if ($post['move_in_date'] == "future") {
        $move_in_date_unknown = true;
        $move_in_date = new DateTime('3000-01-01');
        $move_in_date = $move_in_date->format('m-d-Y');
    } else {
        $move_in_date_unknown = false;
        $move_in_date = new DateTime($post['move_in_date']);
        $move_in_date = $move_in_date->format('d-m-Y');
    }
    return array($move_in_date, $move_in_date_unknown);
}

function construct_move_in_date_string($move_in_date)
{

    // Initialize the string to hold the move-in date information
    $move_in_date_string = "";

    // Check if the move-in date is unknown
    if (!empty($move_in_date[1]) && $move_in_date[1]) {
        // If move-in date is marked as unknown
        return "Kunden har ikke angivet indflytningsdato";
    }
    //replace - with / in the date
    $move_in_date = str_replace('-', '/', $move_in_date[0]);
    // Create a DateTime object from the move-in date string
    $move_in_date_obj = DateTime::createFromFormat('d/m/Y', $move_in_date);

    // Format the move-in date in the desired format
    $move_in_date = $move_in_date_obj->format('j. F Y');

    // Translate the month name
    $move_in_date_string = translate_month($move_in_date);

    return $move_in_date_string;
}

function translate_month($date)
{
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $danish_months = array('januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december');
    return str_replace($english_months, $danish_months, $date);
}

function construct_size_string($unit_id)
{
    $rel_type = get_post_meta($unit_id, 'rel_type', true);
    $m2 = get_post_meta($rel_type, 'm2', true);
    $m3 = get_post_meta($rel_type, 'm3', true);
    $size = "";

    // Check and format both m2 and m3 values if they exist
    if ($m2 && $m2) {
        // Both m2 and m3 exist
        $size = str_replace('.', ',', $m2) . " m2 / " . str_replace('.', ',', $m3) . " m3";
    } elseif ($m2) {
        // Only m2 exists
        $size = str_replace('.', ',', $m2) . " m2";
    } elseif ($m3) {
        // Only m3 exists
        $size = str_replace('.', ',', $m3) . " m3";
    }
    return $size;
}

function construct_unit_type_string($unit_id)
{
    $rel_type = get_post_meta($unit_id, 'rel_type', true);
    $type = get_post_meta($rel_type, 'unit_type', true);

    $unit_type_string = "";

    switch ($type) {
        case "indoor":
            $unit_type_string = "Indendørs depotrum";
            break;
        case "container":
            $unit_type_string = "Container";
            break;
        case "unit_in_container":
            $unit_type_string = "Depotrum i container";
            break;
        case "garage":
            $unit_type_string = "Garage";
            break;
        case "classic_storage":
            $unit_type_string = "Opmagasinering";
            break;
        case "big_box":
            $unit_type_string = "Big box";
            break;
        case "motorcycle":
            $unit_type_string = "Motorcykel";
            break;
        case "car":
            $unit_type_string = "Bil";
            break;
        case "autocamper":
            $unit_type_string = "Autocamper";
            break;
    }
    return $unit_type_string;
}
add_action('wp_ajax_nopriv_booking_form_action', 'handle_booking_form');
add_action('wp_ajax_booking_form_action', 'handle_booking_form');
