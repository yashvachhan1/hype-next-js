<?php
/**
 * RE-DESIGNED MEGA MENU: Combined Desktop & Mobile Header
 * Desktop: Purple bar (1025px+)
 * Mobile: Logo + FiboSearch + Hamburger (<1025px)
 * Includes: 3-Level Mobile Hierarchy (Accordion)
 * Use: [wcs_mega_menu]
 */

if ( ! class_exists( 'WCS_Unified_Menu' ) ) {
    class WCS_Unified_Menu {

        public function __construct() {
            add_shortcode( 'wcs_mega_menu', array( $this, 'render_unified_menu' ) );
            add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_count_fragments' ) );
        }

        public function render_unified_menu() {
            $order = array(
                'DEVICES', 'E-JUICES', 'COILS / PODS', 'DISPOSABLES', 
                'HEMP', 'NICOTINE POUCHES', 'SMOKESHOP', 
                'VAPE DEALS', 'KRATOM/ MASHROOM', 'BRANDS'
            );

            // Fetch Data
            $trending = wc_get_products( array( 'limit' => 3, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
            // Hardcoded Logo from User
            $logo_url = 'https://2z4.30b.myftpupload.com/wp-content/uploads/2026/01/Group-2104-1-2.png';
            $cart_count = ( is_object( WC()->cart ) ) ? WC()->cart->get_cart_contents_count() : 0;

            ob_start();
            $this->render_styles();
            ?>
            
            <!-- 1. MOBILE HEADER (< 1025px) -->
            <div class="wcs-mobile-header">
                <div class="wcs-mob-container">
                    <div class="wcs-mob-logo">
                        <a href="/"><img src="<?php echo esc_url($logo_url); ?>" alt="Logo"></a>
                    </div>
                    <div class="wcs-mob-search-area">
                        <?php echo do_shortcode('[fibosearch]'); ?>
                    </div>
                    
                    <!-- Mobile Cart Icon -->
                    <div class="wcs-mob-cart wcs-side-cart-trigger">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#A101F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span class="wcs-cart-qty"><?php echo $cart_count; ?></span>
                    </div>

                    <div class="wcs-mob-trigger" onclick="wcsToggleDrawer()">
                        <div class="wcs-hamb-box">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP MEGA BAR (>= 1025px) -->
            <div class="wcs-mega-bar-full">
                <nav class="wcs-main-nav">
                    <ul class="wcs-top-ul">
                        <?php foreach ( $order as $cat_name ) : 
                            $term = get_term_by('name', $cat_name, 'product_cat');
                            $url = $term ? get_term_link($term) : '#';
                            if($cat_name == 'BRANDS') $url = '/brands';
                            if($cat_name == 'VAPE DEALS') $url = '/vape-deals';
                            $children = $term ? $this->get_sub_hierarchy($term->term_id) : array();
                            
                            // Inject COA into HEMP Children
                            if ($cat_name == 'HEMP') {
                                $children[] = array(
                                    'is_coa' => true,
                                    'term' => (object) array('name' => 'COA')
                                );
                            }
                        ?>
                            <li class="wcs-top-li <?php echo !empty($children) ? 'has-mega' : ''; ?>">
                                <a href="<?php echo esc_url($url); ?>" class="wcs-top-a">
                                    <?php echo esc_html($cat_name); ?>
                                    <?php if (!empty($children)) : ?><span class="wcs-arrow"></span><?php endif; ?>
                                </a>
                                <?php if (!empty($children)) : ?>
                                    <div class="wcs-mega-content">
                                        <div class="wcs-mega-grid">
                                            <div class="wcs-cats-area">
                                                <?php foreach ( array_chunk( $children, ceil( count( $children ) / 3 ) ) as $col ) : ?>
                                                    <div class="wcs-col">
                                                        <?php foreach ( $col as $child ) : ?>
                                                            <div class="wcs-cat-group">
                                                                <?php 
                                                                    $c_url = isset($child['is_coa']) ? '/COA/' : get_term_link( $child['term'] );
                                                                ?>
                                                                <a href="<?php echo esc_url( $c_url ); ?>" class="wcs-cat-head"><?php echo esc_html( $child['term']->name ); ?></a>
                                                                <ul class="wcs-sub-list">
                                                                    <?php if (!empty($child['children'])) : foreach ( $child['children'] as $grand ) : ?>
                                                                        <li><a href="<?php echo esc_url( get_term_link( $grand['term'] ) ); ?>"><?php echo esc_html( $grand['term']->name ); ?></a></li>
                                                                    <?php endforeach; endif; ?>
                                                                </ul>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="wcs-sidebar">
                                                <h4 class="wcs-side-title">TRENDING IN <?php echo esc_html($cat_name); ?></h4>
                                                <?php 
                                                $cat_products = wc_get_products( array( 
                                                    'limit'    => 3, 
                                                    'status'   => 'publish', 
                                                    'category' => array( $cat_name ), 
                                                    'orderby'  => 'date', 
                                                    'order'    => 'DESC' 
                                                ) );
                                                // Fallback to global trending if category is empty
                                                if(empty($cat_products)) $cat_products = $trending;
                                                
                                                foreach ( $cat_products as $p ) : ?>
                                                    <a href="<?php echo get_permalink($p->get_id()); ?>" class="wcs-side-item">
                                                        <div class="wcs-side-img"><?php echo $p->get_image(); ?></div>
                                                        <div class="wcs-side-info"><p><?php echo $p->get_name(); ?></p><span><?php echo $p->get_price_html(); ?></span></div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>

            <!-- 3. MOBILE SIDE DRAWER (HIERARCHICAL ACCORDION) -->
            <div class="wcs-drawer-overlay" id="wcsDrawerOverlay" onclick="wcsToggleDrawer()"></div>
            <div class="wcs-side-drawer" id="wcsSideDrawer">
                <div class="wcs-drawer-header">
                    <img src="<?php echo esc_url($logo_url); ?>" style="max-height:40px;">
                    <div class="wcs-drawer-close" onclick="wcsToggleDrawer()">&times;</div>
                </div>
                <div class="wcs-drawer-content">
                    <div class="wcs-acc-menu">
                        <?php foreach($order as $m) : 
                             $t = get_term_by('name', $m, 'product_cat');
                             $u = $t ? get_term_link($t) : '#';
                             if($m == 'BRANDS') $u = '/brands';
                             if($m == 'VAPE DEALS') $u = '/vape-deals';
                             $level2 = $t ? $this->get_sub_hierarchy($t->term_id) : array();
                             
                             if ($m == 'HEMP') {
                                $level2[] = array(
                                    'is_coa' => true,
                                    'term' => (object) array('name' => 'COA')
                                );
                             }
                        ?>
                            <div class="wcs-acc-item">
                                <div class="wcs-acc-head">
                                    <a href="<?php echo esc_url($u); ?>"><?php echo esc_html($m); ?></a>
                                    <?php if(!empty($level2)): ?>
                                        <span class="wcs-acc-toggle" onclick="wcsToggleAcc(this)"></span>
                                    <?php endif; ?>
                                </div>
                                <?php if(!empty($level2)): ?>
                                    <div class="wcs-acc-body">
                                        <?php foreach($level2 as $sub): ?>
                                            <div class="wcs-sub-acc-item">
                                                <div class="wcs-sub-acc-head">
                                                    <?php 
                                                        $s_url = isset($sub['is_coa']) ? '/COA/' : get_term_link($sub['term']);
                                                    ?>
                                                    <a href="<?php echo esc_url($s_url); ?>"><?php echo esc_html($sub['term']->name); ?></a>
                                                    <?php if(!empty($sub['children'])): ?>
                                                        <span class="wcs-acc-toggle" onclick="wcsToggleAcc(this)"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if(!empty($sub['children'])): ?>
                                                    <div class="wcs-acc-body">
                                                        <?php foreach($sub['children'] as $grand): ?>
                                                            <div class="wcs-grand-link">
                                                                <a href="<?php echo esc_url(get_term_link($grand['term'])); ?>"><?php echo esc_html($grand['term']->name); ?></a>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- EXTRA LINKS AT BOTTOM OF HAMBURGER ONLY -->
                        <div class="wcs-acc-item">
                            <div class="wcs-acc-head">
                                <a href="/new-arrivals">NEW ARRIVALS</a>
                            </div>
                        </div>
                        <div class="wcs-acc-item">
                            <div class="wcs-acc-head">
                                <a href="/best-sellers">BEST SELLERS</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wcs-drawer-footer">
                    <a href="/my-account" class="wcs-acc-btn">My Account</a>
                    <a href="/contact-us" class="wcs-acc-btn">Support</a>
                </div>
            </div>

            <script>
                function wcsToggleDrawer() {
                    document.getElementById('wcsSideDrawer').classList.toggle('active');
                    document.getElementById('wcsDrawerOverlay').classList.toggle('active');
                    if(document.getElementById('wcsSideDrawer').classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
                function wcsToggleAcc(el) {
                    const body = el.parentElement.nextElementSibling;
                    el.classList.toggle('active');
                    if (body) {
                        body.classList.toggle('active');
                    }
                }

                // Cart Trigger Support
                jQuery(document).ready(function($) {
                    $('.wcs-side-cart-trigger').on('click', function(e) {
                        e.preventDefault();
                        if($('.xoo-wsc-cart-trigger').length) {
                            $('.xoo-wsc-cart-trigger').trigger('click');
                        } else if ($('.elementor-menu-cart__toggle').length) {
                            $('.elementor-menu-cart__toggle').trigger('click');
                        } else {
                            window.location.href = '/cart/';
                        }
                    });
                });
            </script>
            <?php
            return ob_get_clean();
        }

        public function cart_count_fragments( $fragments ) {
            $cart_count = WC()->cart->get_cart_contents_count();
            $fragments['span.wcs-cart-qty'] = '<span class="wcs-cart-qty">' . $cart_count . '</span>';
            return $fragments;
        }

        private function get_sub_hierarchy($parent_id) {
            $subs = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent_id, 'hide_empty' => true ) );
            $tree = array();
            foreach ( $subs as $s ) {
                $grands = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $s->term_id, 'hide_empty' => true ) );
                $grandchildren = array();
                foreach($grands as $g) { $grandchildren[] = array('term' => $g); }
                $tree[] = array('term' => $s, 'children' => $grandchildren);
            }
            return $tree;
        }

        private function render_styles() {
            ?>
            <style>
                /* MOBILE HEADER (< 1025px) */
                .wcs-mobile-header { display: none; padding: 10px 15px; background: #fff; border-bottom: 2px solid #f8f8f8; position: sticky; top: 0; z-index: 9999; }
                .wcs-mob-container { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
                .wcs-mob-logo img { max-height: 40px; width: auto; display: block; }
                .wcs-mob-search-area { flex: 1; }
                .wcs-mob-search-area .dgwt-wcas-search-wrapp { margin: 0 !important; }
                
                .wcs-hamb-box { width: 38px; height: 38px; border: 1.5px solid #eee; border-radius: 6px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px; cursor: pointer; flex-shrink: 0; }
                .wcs-hamb-box span { display: block; width: 20px; height: 2px; background: #A101F6; border-radius: 2px; }

                /* Mobile Cart styles */
                .wcs-mob-cart {
                    position: relative;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 38px;
                    height: 38px;
                    cursor: pointer;
                    flex-shrink: 0;
                }
                .wcs-cart-qty {
                    position: absolute;
                    top: 0;
                    right: -4px;
                    background: #ff0000;
                    color: #fff;
                    font-size: 10px;
                    font-weight: 800;
                    min-width: 16px;
                    height: 16px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1.5px solid #fff;
                }

                /* DESKTOP MEGA BAR (>= 1025px) */
                .wcs-mega-bar-full { display: none; background: #2D004B; width: 100%; position: relative; z-index: 999; font-family: 'Inter', sans-serif; }
                .wcs-main-nav { max-width: 1400px; margin: 0 auto; min-height: 50px; display: flex; align-items: center; }
                .wcs-top-ul { list-style: none; margin: 0; padding: 0; display: flex; width: 100%; justify-content: center; }
                .wcs-top-a { color: #fff; text-decoration: none; padding: 15px 12px; display: flex; align-items: center; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
                .wcs-top-li:hover .wcs-top-a { color: #A101F6; background: #fff; }
                .wcs-arrow { border: solid #fff; border-width: 0 1.5px 1.5px 0; display: inline-block; padding: 2px; transform: rotate(45deg); margin-left: 6px; margin-top:-2px; opacity: 0.8; }
                .wcs-top-li:hover .wcs-arrow { border-color: #A101F6; }

                .wcs-mega-content { position: absolute; top: 100%; left: 50%; transform: translateX(-50%); min-width: 1100px; background: #fff; opacity: 0; visibility: hidden; transition: 0.2s; box-shadow: 0 15px 40px rgba(0,0,0,0.1); border-radius: 0 0 8px 8px; z-index: 10001; }
                .wcs-top-li:hover .wcs-mega-content { opacity: 1; visibility: visible; }
                .wcs-mega-grid { display: flex; padding: 25px; max-height: 550px; overflow-y: auto; }

                /* Professional Scrollbar for Mega Menu */
                .wcs-mega-grid::-webkit-scrollbar { width: 6px; }
                .wcs-mega-grid::-webkit-scrollbar-track { background: #f9f9f9; }
                .wcs-mega-grid::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
                .wcs-mega-grid::-webkit-scrollbar-thumb:hover { background: #A101F6; }
                .wcs-cats-area { flex: 1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: left; }
                .wcs-cat-head { font-size: 13px; font-weight: 800; color: #000; text-transform: uppercase; margin-bottom: 10px; display: block; border-bottom: 1px solid #eee; padding-bottom: 5px; text-decoration: none;}
                .wcs-sub-list { list-style: none; padding: 0; margin: 0; }
                .wcs-sub-list a { font-size: 12px; color: #666; text-decoration: none; display: block; margin-bottom: 5px; }
                .wcs-sub-list a:hover { color: #A101F6; }
                .wcs-sidebar { width: 300px; background: #fafafa; padding: 20px; border-radius: 8px; margin-left: 20px; text-align: left; position: sticky; top: 0; height: fit-content; }
                .wcs-side-title { font-size: 12px; font-weight: 800; border-bottom: 2px solid #A101F6; margin-bottom: 15px; display: inline-block; }
                .wcs-side-item { display: flex; gap: 10px; margin-bottom: 10px; text-decoration: none; align-items: center; background:#fff; padding:5px; border-radius:5px; border:1px solid #eee;}
                .wcs-side-img img { width: 45px; height: 45px; object-fit: contain; }
                .wcs-side-info p { margin: 0; font-size: 11px; font-weight: 700; color: #333; }

                /* DRAWER & ACCORDION */
                .wcs-drawer-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 10000; display: none; }
                .wcs-drawer-overlay.active { display: block; }
                .wcs-side-drawer { position: fixed; top: 0; right: -320px; width: 300px; height: 100%; background: #fff; z-index: 10001; transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); display: flex; flex-direction: column; text-align: left; }
                .wcs-side-drawer.active { right: 0; }
                
                .wcs-drawer-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
                .wcs-drawer-close { font-size: 30px; cursor: pointer; color:#999; }
                
                .wcs-drawer-content { flex: 1; overflow-y: auto; padding: 15px 20px; }
                .wcs-acc-item { border-bottom: 1px solid #f8f8f8; }
                .wcs-acc-head { display: flex; justify-content: space-between; align-items: center; }
                .wcs-acc-head a { display: block; flex: 1; padding: 15px 0; font-size: 13px; font-weight: 800; color: #000; text-decoration: none; text-transform: uppercase; }
                
                .wcs-acc-toggle { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
                .wcs-acc-toggle::after { content: '+'; font-size: 20px; color: #A101F6; }
                .wcs-acc-toggle.active::after { content: '−'; }
                
                .wcs-acc-body { display: none; padding-left: 15px; margin-bottom: 10px; }
                .wcs-acc-body.active { display: block; }
                
                .wcs-sub-acc-item { border-left: 2px solid #eee; margin-bottom: 5px; }
                .wcs-sub-acc-head { display: flex; justify-content: space-between; align-items: center; padding-left: 10px; }
                .wcs-sub-acc-head a { flex: 1; padding: 10px 0; font-size: 13px; font-weight: 700; color: #333; text-decoration: none; }
                
                .wcs-grand-link { padding: 5px 10px 5px 15px; }
                .wcs-grand-link a { font-size: 12px; color: #666; text-decoration: none; }

                .wcs-drawer-footer { padding: 20px; border-top: 1px solid #f0f0f0; background: #fafafa; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .wcs-acc-btn { display: block; background: #fff; color: #000; border: 1px solid #eee; text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 800; }

                /* RESPONSIVE SWITCH */
                @media (max-width: 1024px) { .wcs-mobile-header { display: block; } }
                @media (min-width: 1025px) { .wcs-mega-bar-full { display: block; } }
            </style>
            <?php
        }
    }
    new WCS_Unified_Menu();
}
