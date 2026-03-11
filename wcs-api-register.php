<?php
/**
 * SNIPPET: MODULAR AUTH API - REGISTER
 * Description: Handles User Registration.
 * Endpoint: /wcs/v1/auth/register
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/auth/register', array(
        'methods'  => 'POST',
        'callback' => 'wcs_auth_register',
        'permission_callback' => '__return_true'
    ));
});

function wcs_auth_register( $request ) {
    $email = $request->get_param('email');
    $password = $request->get_param('password');
    $first_name = $request->get_param('first_name');
    $last_name = $request->get_param('last_name');

    if ( empty($email) || empty($password) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Email and Password are required.' ), 400 );
    }

    if ( email_exists($email) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Email already exists.' ), 400 );
    }

    $user_id = wc_create_new_customer( $email, $email, $password );

    if ( is_wp_error( $user_id ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => $user_id->get_error_message() ), 400 );
    }

    if ( $first_name ) update_user_meta( $user_id, 'first_name', $first_name );
    if ( $last_name ) update_user_meta( $user_id, 'last_name', $last_name );

    return new WP_REST_Response( array( 'success' => true, 'message' => 'Registration successful.', 'user_id' => $user_id ), 200 );
}
