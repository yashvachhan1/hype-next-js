<?php
/**
 * CUSTOM API ENDPOINTS FOR HEADLESS REACT FRONTEND
 * Route: /wp-json/wcs/v1/app-data
 */

class WCS_API_Endpoints {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wcs/v1', '/app-data', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_app_data' ),
            'permission_callback' => '__return_true', // Public Data
        ) );
    }

    public function get_app_data( $request ) {
        
        // 1. MEGA MENU HIERARCHY
        $menu_structure = $this->get_mega_menu_tree();

        // 2. TRENDING PRODUCTS (Filtered by Category)
        $trending = $this->get_products_by_query( array( 
            'limit' => 4, 
            'status' => 'publish', 
            'category' => array('trending-products'), 
            'orderby' => 'date', 
            'order' => 'DESC' 
        ) );

        // 3. BRAND LIST
        $brands = $this->get_all_brands();

        return new WP_REST_Response( array(
            'site_name' => get_bloginfo( 'name' ),
            'logo_url'  => 'https://2z4.30b.myftpupload.com/wp-content/uploads/2026/01/Group-2104-1-2.png',
            'menu'      => $menu_structure,
            'trending'  => $trending,
            'brands'    => $brands
        ), 200 );
    }

    // Helper: Build Mega Menu Tree directly from categories
    private function get_mega_menu_tree() {
        $order = array(
            'DEVICES', 'E-JUICES', 'COILS / PODS', 'DISPOSABLES', 
            'HEMP', 'NICOTINE POUCHES', 'SMOKESHOP', 
            'VAPE DEALS', 'KRATOM/ MASHROOM', 'BRANDS'
        );

        $menu = array();

        foreach ( $order as $cat_name ) {
            // Special Custom Links
            if ( $cat_name === 'BRANDS' ) {
                $menu[] = array( 'id' => 9991, 'name' => 'BRANDS', 'type' => 'link', 'children' => array() );
                continue;
            }
            if ( $cat_name === 'VAPE DEALS' ) {
                $menu[] = array( 'id' => 9992, 'name' => 'VAPE DEALS', 'type' => 'link', 'children' => array() );
                continue;
            }

            $term = get_term_by( 'name', $cat_name, 'product_cat' );
            if ( ! $term ) continue;

            $children_terms = get_terms( array( 
                'taxonomy' => 'product_cat', 
                'parent' => $term->term_id, 
                'hide_empty' => true 
            ) );

            $level2 = array();
            foreach ( $children_terms as $child ) {
                $grands = get_terms( array( 
                    'taxonomy' => 'product_cat', 
                    'parent' => $child->term_id, 
                    'hide_empty' => true 
                ) );
                
                $level3 = array();
                foreach ( $grands as $g ) {
                    $level3[] = array( 'id' => $g->term_id, 'name' => $g->name, 'slug' => $g->slug );
                }

                $level2[] = array(
                    'id' => $child->term_id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'children' => $level3
                );
            }

            // HEMP Special Injection
            if ( $cat_name === 'HEMP' ) {
                $level2[] = array( 'id' => 8888, 'name' => 'COA', 'slug' => 'coa', 'type' => 'custom', 'children' => array() );
            }

            $menu[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'type' => 'category',
                'children' => $level2
            );
        }

        return $menu;
    }

    // Helper: Standardized Product Format for React
    private function get_products_by_query( $args ) {
        $products = wc_get_products( $args );
        $data = array();

        foreach ( $products as $p ) {
            $data[] = array(
                'id' => $p->get_id(),
                'name' => $p->get_name(),
                'price_html' => $p->get_price_html(),
                'image' => wp_get_attachment_image_url( $p->get_image_id(), 'medium_large' ),
                'slug' => $p->get_slug(),
                'stock_status' => $p->get_stock_status(),
                'case_count' => get_post_meta( $p->get_id(), 'master_case_count', true )
            );
        }
        return $data;
    }

    private function get_all_brands() {
        $brands = get_terms( array('taxonomy' => 'pwb-brand', 'hide_empty' => true) );
        $data = array();
        if( !empty($brands) && !is_wp_error($brands) ) {
            foreach($brands as $b) { 
                $logo_id = get_term_meta( $b->term_id, 'pwb_brand_image', true );
                $data[] = array(
                    'id' => $b->term_id, 
                    'slug' => $b->slug, 
                    'name' => $b->name, 
                    'logo' => $logo_id ? wp_get_attachment_url( $logo_id ) : ''
                ); 
            }
        }
        return $data;
    }
}
