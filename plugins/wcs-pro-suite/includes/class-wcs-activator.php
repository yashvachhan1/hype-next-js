<?php

class WCS_Activator {

	public static function activate() {
        // Create default options if they don't exist
        if ( false == get_option( 'swp_role_a_name' ) ) {
            add_option( 'swp_role_a_name', 'wholesale_customer_1' );
            add_option( 'swp_role_a_percent', 20 );
            add_option( 'swp_role_a_min', 500 );
        }
	}

}
