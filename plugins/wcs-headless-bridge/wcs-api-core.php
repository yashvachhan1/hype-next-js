<?php
/**
 * SNIPPET: MODULAR CORE API
 * Description: Global System Settings, Error Handling, and CORS.
 * Note: Include this early.
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. GLOBAL SHUTDOWN HANDLER (Catches Fatal Errors)
if ( ! function_exists( 'wcs_shutdown_handler_modular' ) ) {
    function wcs_shutdown_handler_modular() {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
            if ( ob_get_length() ) ob_clean();
            header( 'Content-Type: application/json; charset=UTF-8' );
            echo json_encode( array( 
                'code' => 'fatal_server_error', 
                'message' => 'Server timeout or crash', 
                'debug' => $error['message'] . ' on line ' . $error['line']
            ));
            exit;
        }
    }
}
register_shutdown_function( 'wcs_shutdown_handler_modular' );

// 2. SYSTEM SETTINGS
add_action( 'init', function() {
    // Increase limits for heavy API work
    @ini_set( 'memory_limit', '2048M' ); 
    @ini_set( 'max_execution_time', 1200 ); 
    @set_time_limit( 1200 );
    
    // Allow CORS (Optional, usually handled by WP Headers plugin, but good safety)
    header("Access-Control-Allow-Origin: *");
});
