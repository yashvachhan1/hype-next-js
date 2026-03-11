<?php
/**
 * SNIPPET: MODULAR CONTACT/FORM API
 * Description: Handles Contact Forms & Pact Act Submissions.
 * Endpoint: /wcs/v1/form/submit
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/form/submit', array(
        'methods'  => 'POST',
        'callback' => 'wcs_handle_contact_form_modular',
        'permission_callback' => '__return_true'
    ));
});

function wcs_handle_contact_form_modular( $request ) {
    $params = $request->get_json_params();
    
    // Validate Basic Fields
    if ( empty($params['email']) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Email is required.' ), 400 );
    }

    $type = isset($params['type']) ? $params['type'] : 'general';
    $email = sanitize_email($params['email']);
    $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
    $name = isset($params['name']) ? sanitize_text_field($params['name']) : 'Guest';

    // Email Body
    $subject = "New Submission: " . ucfirst($type);
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    
    // Special handling for Pact Act images if needed
    if ( $type === 'pact_act' && !empty($params['image']) ) {
        $body .= "\n\n(Image attachment handling would go here)";
    }

    // Send Mail to Admin
    $admin_email = get_option('admin_email');
    $sent = wp_mail( $admin_email, $subject, $body );

    if ( $sent ) {
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Form submitted successfully.' ), 200 );
    } else {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Failed to send email.' ), 500 );
    }
}
