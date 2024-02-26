<?php


function handle_new_booking($post_id)
{
    trigger_error('handle_new_booking triggered', E_USER_NOTICE);
    $booking_post = get_post($post_id);
    if ($booking_post->post_type === 'booking') {
        notify_supplier($booking_post);
        notify_admin($booking_post);
    }
}

add_action('handle_new_booking_cron', 'handle_new_booking');

function schedule_new_booking_handler($new_status, $old_status, $post)
{
    if ($post->post_type === 'booking' && 'publish' === $new_status && 'publish' !== $old_status) {
        trigger_error('handle_new_booking scheduler scheduled a handle_new_booking_cron job', E_USER_NOTICE);
        wp_schedule_single_event(time() + 20, 'handle_new_booking_cron', array($post->ID));
    }
}
add_action('transition_post_status', 'schedule_new_booking_handler', 10, 3);

function notify_supplier($booking_post)
{
    $booking_info = gather_booking_info($booking_post);

    $supplier_booking_email_disabled = get_post_meta($booking_post->ID, 'supplier_booking_email_disabled', true);
    if ($supplier_booking_email_disabled) {
        return;
    }

    $booking_notification_email_sent_to_supplier = get_post_meta($booking_post->ID, 'booking_notification_email_sent_to_supplier', true);
    if ($booking_notification_email_sent_to_supplier) {
        return;
    }

    // Construct the email body
    $email_body = "<b>Ny reservation:</b> " . $booking_post->post_title . "<br><br>";
    $email_body .= "<b>Tidspunkt:</b> " . $booking_info['time_of_booking'] . "<br>";

    $email_body .= "<h3>Enhed</h3>";
    if ($booking_info['supplier_unit_id']) {
        $email_body .= "<b>Enhedens ID hos udbyder:</b> " . $booking_info['supplier_unit_id'] . "<br>";
        $email_body .= "<b>Enhedens ID hos tjekdepot:</b> #" . $booking_info['unit_link'] . "<br>";
    } else {
        $email_body .= "<b>Enhedens ID hos tjekdepot:</b> #" . $booking_info['unit_link'] . "<br>";
    }
    $email_body .= "<b>Pris:</b> " . $booking_info['unit_price'] . "<br>";
    $email_body .= "<b>Afdeling:</b> " . $booking_info['lokation_name'] . "<br>";

    $email_body .= "<h3>Kunde</h3>";
    $email_body .= "<b>Kundens navn:</b> " . $booking_info['first_name'] . " "  . $booking_info['last_name'] . "<br>";
    $email_body .= "<b>Kundens email-adresse:</b> " . $booking_info['email'] . "<br>";
    $email_body .= "<b>Kundens telefonnummer:</b> " . $booking_info['phone'] . "<br>";

    $email_body .= "<h3>Indflytning</h3>";
    $email_body .= "<b>Indflytningsdato:</b> " . $booking_info['move_in_date_string'] . "<br>";

    $email_body .= "<h3>Diverse</h3>";

    if ($booking_info['booking_link']) {
        $email_body .= "<b>Booking-link:</b> " . $booking_info['booking_link'] . "<br>";
    }

    $supplier_email = $booking_info['supplier_email'];

    $to = $supplier_email;
    $subject = "Ny reservation fra Tjekdepot.dk";
    $headers = array(
        'From: tjekdepot.dk <system@tjekdepot.dk>',
        'Content-Type: text/html; charset=UTF-8',
    );

    wp_mail($to, $subject, $email_body, $headers);
    update_post_meta($booking_post->ID, 'booking_notification_email_sent_to_supplier', "1");

    trigger_error('Sent booking email notification to supplier', E_USER_NOTICE);
}

function notify_admin($booking_post)
{
    $booking_notification_email_sent_to_admin = get_post_meta($booking_post->ID, 'booking_notification_email_sent_to_admin', true);

    if ($booking_notification_email_sent_to_admin) {
        return;
    }

    $booking_info = gather_booking_info($booking_post);

    // Construct the email body
    $email_body = "<b>Ny reservation:</b> " . $booking_post->post_title . "<br><br>";
    $email_body .= "<b>Tidspunkt for reservation:</b> " .  $booking_info['time_of_booking'] . "<br>";

    $email_body .= "<h3>Enhed</h3>";
    $email_body .= "<b>Enhedens pris:</b> " . $booking_info['unit_price'] . "<br>";
    $email_body .= "<b>Enhedens ID:</b> " . $booking_info['unit_link'] . "<br>";
    $email_body .= "<b>Enhedens ID hos supplier:</b> " . $booking_info['supplier_unit_id'] . "<br>";
    $email_body .= "<b>Lokationens navn:</b> " . $booking_info['lokation_name'] . "<br>";
    $email_body .= "<b>Lokationens ID:</b> " . $booking_info['rel_lokation'] . "<br>";
    $email_body .= "<b>Udlejerens email-adresse:</b> " . $booking_info['supplier_email'] . "<br>";

    $email_body .= "<h3>Kunde</h3>";
    $email_body .= "<b>Kundens fornavn:</b> " . $booking_info['first_name'] . "<br>";
    $email_body .= "<b>Kundens efternavn:</b> " . $booking_info['last_name'] . "<br>";
    $email_body .= "<b>Kundens emailadresse:</b> " . $booking_info['email'] . "<br>";
    $email_body .= "<b>Kundens telefonnummer:</b> " . $booking_info['phone'] . "<br>";

    $email_body .= "<h3>Indflytningsdato</h3>";
    $email_body .= "<b>Indflytningsdato:</b> " . $booking_info['move_in_date'] . "<br>";
    $email_body .= "<b>Indflytningsdato ukendt?:</b> " . $booking_info['move_in_date_unknown'] . "<br>";

    $email_body .= "<h3>Diverse</h3>";
    $email_body .= "<b>Eventuelt booking-link:</b> " . $booking_info['booking_link'] . "<br><br>";

    $email_body .= "<b>Udlejerens adresse:</b> " . $booking_info['department_address'] . "<br>";
    $email_body .= "<b>Er booking email til leverandøren deaktiveret?</b> " . $booking_info['supplier_booking_email_disabled'] . "<br>";
    $email_body .= "<b>Er direkte booking aktiv?</b> " . $booking_info['direct_booking_active'] . "<br>";

    email_admin($email_body, 'Ny reservation: ' . $booking_post->post_title);

    update_post_meta($booking_post->ID, "booking_notification_email_sent_to_admin", "1");

    trigger_error('Sent booking email notification to admin', E_USER_NOTICE);
}

function gather_booking_info($post)
{
    $unit_link = get_post_meta($post->ID, 'unit_link', true);
    $rel_type = get_post_meta($unit_link, 'rel_type', true);
    $first_name = get_post_meta($post->ID, 'customer_first_name', true);
    $last_name = get_post_meta($post->ID, 'customer_last_name', true);
    $email = get_post_meta($post->ID, 'customer_email_address', true);
    $phone = get_post_meta($post->ID, 'customer_phone', true);
    $time_of_booking = get_post_meta($post->ID, 'time_of_booking', true);
    $move_in_date = get_post_meta($post->ID, 'move_in_date', true);
    $move_in_date_string = get_post_meta($post->ID, 'move_in_date_string', true);
    $move_in_date_unknown = get_post_meta($post->ID, 'move_in_date_unknown', true);
    $supplier_booking_email_disabled = get_post_meta($post->ID, 'supplier_booking_email_disabled', true);
    $direct_booking_active = get_post_meta($post->ID, 'direct_booking_active', true);
    $booking_link = get_post_meta($post->ID, 'booking_link', true);
    $supplier_unit_id = get_post_meta($post->ID, 'supplier_unit_id', true);
    $supplier_email = get_post_meta($post->ID, 'supplier_email_address', true);
    $lokation_name = get_post_meta($post->ID, 'department_name', true);
    $rel_lokation = get_post_meta($post->ID, 'rel_lokation', true);
    $unit_price = get_post_meta($post->ID, 'unit_price', true);
    $department_address = get_post_meta($post->ID, 'department_address', true);
    $price = get_post_meta($unit_link, 'price', true);
    $m2 = get_post_meta($rel_type, 'm2', true);
    $m3 = get_post_meta($rel_type, 'm3', true);
    $unit_type =  get_post_meta($rel_type, 'unit_type', true);
    $unit_size_string = get_post_meta($unit_link, 'size_string', true);
    $unit_type_string = get_post_meta($rel_type, 'type_string', true);

    return array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'time_of_booking' => $time_of_booking,
        'move_in_date' => $move_in_date,
        'move_in_date_string' => $move_in_date_string,
        'move_in_date_unknown' => $move_in_date_unknown,
        'supplier_booking_email_disabled' => $supplier_booking_email_disabled,
        'direct_booking_active' => $direct_booking_active,
        'unit_link' => $unit_link,
        'booking_link' => $booking_link,
        'supplier_unit_id' => $supplier_unit_id,
        'supplier_email' => $supplier_email,
        'lokation_name' => $lokation_name,
        'rel_lokation' => $rel_lokation,
        'unit_price' => $unit_price,
        'department_address' => $department_address,
        'price' => $price,
        'm2' => $m2,
        'm3' => $m3,
        'unit_type' => $unit_type,
        'unit_size_string' => $unit_size_string,
        'unit_type_string' => $unit_type_string,
    );
}

$supplier_email_template = '<!DOCTYPE html>
<html lang="en">
<head>
<style>
    body {
        background-color: #f7f7f7; /* Slightly darker background */
        font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;

    }
    .header, .content, .footer {
        width: 600px;
        margin: 0 auto;
    }
    .header img, .content img {
        display: block;
        max-width: 100%;
        height: auto;
        border: 0;
        outline: none;
    }
    .content {
        height: 100vh;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center;
        align-items: center;
        background-color: #ffffff;
        //set the padding to 1rem on all sides
        padding: 1rem;
        margin: 1rem;
    }
    ?>
</style>
</head>
<body>
    <div class="header">
        <img src="header-image-url.png" alt="">
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
        <div class="content">
            <table class="content-table" align="center" cellpadding="0" cellspacing="0" border="0" style="width: 600px;">
                <tr>
                    <td class="header">
                        <img src="header-image-url.png" alt="">
                    </td>
                </tr>
                <tr>
                    <td>
                        <h1>Ny reservation fra tjekdepot.dk</h1>
                        <img src="content-image-url.png" alt="">
                        <p>We want to wish you much health, love, happiness. Be free in your dreams. Wish you to always stay young, to have original and brave ideas, may you have a great deal of success in everything you do! Be happy!</p>
                        <h2>ENJOY 20% OFF your next purchase</h2>
                        <p>GRAB YOUR CODE: DVS-650</p>
                        <p>VALID THROUGH: 30.09.2021</p>
                        <a href="#" class="button">SHOP NOW</a>
                    </td>
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>
    <div class="footer">
        <p>tjekdepot.dk</p>
        <p>Mejlgade 16, 8000 Aarhus C</p>
        <a href="#">Besøg os</a> | <a href="#">Privatlivspolitik</a> | <a href="#">Vilkår</a>
    </div>
</body>
</html>
';
