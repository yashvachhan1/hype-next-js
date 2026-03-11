<?php
/**
 * SNIPPET: MODULAR MENU API
 * Description: Dedicated endpoint for the Main Navigation.
 * Endpoint: /wcs/v1/menu
 * Improvements: Single SQL Query (No N+1 loops), Caching.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/menu', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_menu_modular',
        'permission_callback' => '__return_true'
    ));
});

function wcs_get_menu_modular() {
    // 1. Check Cache (1 Hour)
    $cache_key = 'wcs_menu_data_v6';
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return new WP_REST_Response($cached, 200);
    }

    global $wpdb;

    // 2. Define Menu Structure
    $order = array(
        'DEVICES', 
        'E-JUICES', 
        'COILS/PODS', 
        'DISPOSABLES', 
        'HEMP', 
        'NICOTINE POUCHES', 
        'SMOKESHOP', 
        'VAPE DEALS', 
        'KRATOM/MUSHROOMS', 
        'BRANDS'
    );

    // 3. Optimized Fetch: Get ALL populated categories in ONE query
    // avoiding the 10+ query loop issue.
    $sql = "
        SELECT t.term_id, t.name, t.slug, tt.parent 
        FROM {$wpdb->terms} t 
        INNER JOIN {$wpdb->term_taxonomy} tt ON (t.term_id = tt.term_id) 
        WHERE tt.taxonomy = 'product_cat' AND tt.count > 0
    ";
    
    $all_cats = $wpdb->get_results( $sql );

    // 4. Build In-Memory Map
    $cats_by_parent = [];
    $slug_map = []; // Mapping for Top-Level Lookup



    // 5. Construct Final Menu Tree (Recursive)
    $menu = array();

    // Helper: Recursive Tree Builder
    if (!function_exists('wcs_build_menu_tree')) {
        function wcs_build_menu_tree($parent_id, &$cats_by_parent) {
            if (!isset($cats_by_parent[$parent_id])) return array();
            
            $branch = array();
            foreach ($cats_by_parent[$parent_id] as $child) {
                $node = $child;
                $node['children'] = wcs_build_menu_tree($child['id'], $cats_by_parent);
                if (empty($node['children'])) unset($node['children']); // Cleanup empty arrays
                $branch[] = $node;
            }
            return $branch;
        }
    }
    
    // Helper: Normalize Key (Strip spaces/symbols for robust matching)
    // "Coils / Pods" -> "coilspods"
    // "Coils/Pods" -> "coilspods"
    if (!function_exists('wcs_normalize_key')) {
        function wcs_normalize_key($str) {
            return preg_replace('/[^a-z0-9]/', '', strtolower($str));
        }
    }

    // MAP BUILDER: Use Normalized Keys
    // AND: Apply Keyword-Based Mapping to bridge naming gaps
    foreach ( $all_cats as $c ) {
        $cats_by_parent[ $c->parent ][] = array( 
            'id' => (int)$c->term_id, 
            'name' => $c->name, 
            'slug' => $c->slug 
        );
        
        $norm = wcs_normalize_key($c->name);
        $slug_map[ $norm ] = $c;

        // KEYWORD OVERRIDES (Fix for "Coils/Pods", "Kratom", etc.)
        if ( stripos($c->name, 'Coil') !== false || stripos($c->name, 'Pod') !== false ) {
            $slug_map['coilspods'] = $c; 
        }
        if ( stripos($c->name, 'Kratom') !== false || stripos($c->name, 'Mashroom') !== false || stripos($c->name, 'Mushroom') !== false ) {
            $slug_map['kratommashroom'] = $c;
        }
        if ( stripos($c->name, 'Disposa') !== false ) {
            $slug_map['disposables'] = $c;
        }
        if ( stripos($c->name, 'Vape') !== false && stripos($c->name, 'Deal') !== false ) {
            $slug_map['vapedeals'] = $c;
        }
    }

    foreach ( $order as $cat_name ) {
        // Handle "Fake" Link Items
        if ( $cat_name === 'BRANDS' || $cat_name === 'VAPE DEALS' ) {
            $menu[] = array( 
                'id' => rand(9000,9999), 
                'name' => $cat_name, 
                'type' => 'link',
                'children' => array() 
            );
            continue;
        }

        // LOOKUP: Use Normalized Key
        $key = wcs_normalize_key($cat_name);
        $term_obj = isset($slug_map[$key]) ? $slug_map[$key] : null;

        if ( ! $term_obj ) {
            // Debug/Fallback: Try generic fallback if "Mashroom" -> "Mushroom" typo exists
            // But usually normalization handles simple spacing/symbol issues
            continue;
        }

        $tid = $term_obj->term_id;
        
        // RECURSIVE BUILD: Get Children -> Grandchildren -> ...
        $children = wcs_build_menu_tree($tid, $cats_by_parent);

        // Custom Injection for HEMP -> COA
        if ( $cat_name === 'HEMP' ) {
            $children[] = array( 
                'id' => 8888, 
                'name' => 'COA', 
                'slug' => 'coa', 
                'type' => 'custom', 
                'children' => array() 
            );
        }

        $menu[] = array( 
            'id' => $tid, 
            'name' => $term_obj->name, 
            'slug' => $term_obj->slug, 
            'type' => 'category', 
            'children' => $children 
        );
    }

    // 6. Cache & Return
    set_transient($cache_key, $menu, HOUR_IN_SECONDS);
    return new WP_REST_Response($menu, 200);
}
