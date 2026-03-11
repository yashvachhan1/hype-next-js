<?php
/**
 * SNIPPET: MODULAR AUTH API - LOGIN
 * Description: Handles User Login.
 * Endpoint: /wcs/v1/auth/login
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/auth/login', array(
        'methods'  => 'POST',
        'callback' => 'wcs_auth_login',
        'permission_callback' => '__return_true'
    ));
});

function wcs_auth_login( $request ) {
    $creds = array(
        'user_login'    => $request->get_param('username'),
        'user_password' => $request->get_param('password'),
        'remember'      => true
    );

    $user = wp_signon( $creds, false );

    if ( is_wp_error( $user ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => $user->get_error_message() ), 401 );
    }

    // You might want to generate a JWT token here if using JWT Auth
    // For now, returning user data
    
    $role = reset( $user->roles );
    return new WP_REST_Response( array(
        'success' => true,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'role' => $role
        )
    ), 200 );
}
