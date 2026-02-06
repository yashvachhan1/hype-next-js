
/**
 * Standalone WCS Shop Filter (Dynamic Brands)
 * Bundled version for usage in Code Snippets or functions.php
 * Shortcode: [woo_custom_suite_shop]
 */
if ( ! class_exists( 'Standalone_WCS_Shop_Filter' ) ) {
    class Standalone_WCS_Shop_Filter {
        public function __construct() {
            // AJAX Handlers
            add_action( 'wp_ajax_wcs_filter_products', array( $this, 'ajax_filter_products' ) );
            add_action( 'wp_ajax_nopriv_wcs_filter_products', array( $this, 'ajax_filter_products' ) );
            add_action( 'wp_ajax_wcs_get_relevant_brands', array( $this, 'ajax_get_relevant_brands' ) );
            add_action( 'wp_ajax_nopriv_wcs_get_relevant_brands', array( $this, 'ajax_get_relevant_brands' ) );
            
            // Register Shortcode
            add_shortcode( 'woo_custom_suite_shop', array( $this, 'render_shop_shortcode' ) );
        }
        public function render_shop_shortcode() {
            ob_start();
            $categories = get_transient( 'wcs_shop_categories' );
            if ( false === $categories ) {
                $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
                set_transient( 'wcs_shop_categories', $categories, DAY_IN_SECONDS );
            }
            ?>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
                .shop-container-wrapper { display: flex; gap: 40px; max-width: 1600px; margin: 0 auto; padding: 40px 20px; font-family: 'Outfit', sans-serif; align-items: flex-start; background: #fcfcfc; }
                .shop-sidebar-wrapper { width: 330px; background: #fff; border-radius: 24px; padding: 35px; box-shadow: 0 15px 45px rgba(0,0,0,0.04); position: sticky; top: 20px; border: 1px solid #f0f0f0; }
                .filter-widget { margin-bottom: 35px; border-bottom: 1px solid #f9f9f9; padding-bottom: 30px; }
                .filter-widget:last-child { border-bottom: none; }
                .widget-title { font-weight: 800; margin-bottom: 20px; font-size: 15px; color: #111; text-transform: uppercase; letter-spacing: 1px; transition: color 0.3s ease; }
                .widget-title:hover { color: #A101F6; }
                
                /* Search Box */
                .search-box-wrapper { position: relative; }
                .search-box-wrapper input { width: 100%; padding: 14px 18px; border: 2px solid #f0f0f0; border-radius: 14px; font-family: inherit; font-size: 15px; transition: 0.3s; }
                .search-box-wrapper input:focus { border-color: #A101F6; outline: none; background: #fff; }
                .search-btn { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #bbb; cursor: pointer; }
                /* Hierarchical Categories Accordion */
                .category-list { list-style: none; padding: 0; margin: 0; }
                .category-node { margin-bottom: 6px; }
                .cat-row-wrapper { display: flex; align-items: center; gap: 4px; padding: 2px 0; border-radius: 8px; transition: 0.2s; position: relative; }
                .cat-row-wrapper:hover { background: rgba(161, 1, 246, 0.03); }
                
                .cat-toggle-zone { flex: 1; display: flex; align-items: center; gap: 4px; cursor: pointer; min-height: 32px; }
                .cat-name-label { font-size: 14px; color: #444; font-weight: 500; transition: 0.2s; flex: 1; line-height: 1.2; }
                .cat-toggle-zone:hover .cat-name-label { color: #A101F6; }
                
                .cat-radio { accent-color: #A101F6; width: 17px; height: 17px; cursor: pointer; margin-left: auto; flex-shrink: 0; position: relative; z-index: 2; margin-right: 5px; }
                
                .cat-toggle, .cat-toggle-placeholder { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
                .cat-toggle { color: #bbb; transition: 0.3s; background: #f8f8f8; border-radius: 6px; font-size: 10px; pointer-events: none; }
                .cat-toggle-zone:hover .cat-toggle { background: #eee; color: #A101F6; }
                
                .sub-cat-list { list-style: none; padding-left: 15px; margin: 2px 0 5px 14px; border-left: 1.5px solid #eee; display: none; }
                .category-node.open > .cat-row-wrapper .cat-toggle { transform: rotate(90deg); color: #A101F6; background: rgba(161, 1, 246, 0.08); }
                /* Guest Pricing Styles */
                .login-price-notice { font-size: 14px; color: #444; margin-bottom: 12px; display: block; font-weight: 500; }
                .login-red-link { color: #ff0000 !important; font-weight: 700 !important; text-decoration: none !important; cursor: pointer; }
                .login-red-link:hover { text-decoration: underline !important; }
                /* Scrollable Lists */
                #sidebar-brands-list { max-height: 400px; overflow-y: auto; padding-right: 10px; }
                #sidebar-brands-list::-webkit-scrollbar { width: 5px; }
                #sidebar-brands-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
                #sidebar-brands-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
                #sidebar-brands-list::-webkit-scrollbar-thumb:hover { background: #A101F6; }
                
                #sidebar-brands-list .category-item { display: flex; width: 100%; align-items: center; padding: 6px 0; cursor: pointer; border-bottom: 1px solid #f9f9f9; transition: 0.2s; }
                #sidebar-brands-list .category-item:hover { background: rgba(161, 1, 246, 0.03); padding-left: 5px; }
                #sidebar-brands-list .category-item span { flex: 1; font-size: 14px; font-weight: 500; color: #444; }
                /* Brand Header - Logo Only (Bigger) */
                #brand-header-display { margin-bottom: 35px; padding: 30px; background: #fff; border-radius: 20px; border: 1px solid #eee; display: none; align-items: center; justify-content: center; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
                .selected-brand-logo { max-width: 250px; max-height: 100px; object-fit: contain; }
                
                /* Price Slider Alignment Fix */
                .price-range-slider { padding: 0 5px; }
                .price-inputs { display: flex; justify-content: space-between; margin-bottom: 40px; font-weight: 800; color: #1a1a1a; font-size: 16px; }
                .slider-container { position: relative; height: 6px; background: #f0f0f0; border-radius: 10px; margin: 0 10px; }
                .slider-progress { position: absolute; height: 100%; background: #A101F6; border-radius: 10px; }
                .slider-inputs { position: relative; height: 0; width: calc(100% + 24px); left: -12px; top: -3px; }
                .slider-inputs input { position: absolute; width: 100%; height: 6px; top: 0; background: none; pointer-events: none; -webkit-appearance: none; margin: 0; }
                .slider-inputs input::-webkit-slider-thumb { height: 24px; width: 24px; border-radius: 50%; background: #fff; pointer-events: auto; -webkit-appearance: none; cursor: pointer; border: 5px solid #A101F6; box-shadow: 0 4px 12px rgba(161, 1, 246, 0.25); position: relative; z-index: 5; }
                /* Grid Layout: 4 Columns */
                .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
                @media (max-width: 1400px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
                @media (max-width: 1000px) { .products-grid { grid-template-columns: repeat(2, 1fr); } }
                .product-card { background: #fff; border: 1px solid #f2f2f2; border-radius: 24px; padding: 0; text-align: center; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative; display: flex; flex-direction: column; overflow: hidden; }
                .product-card:hover { box-shadow: 0 25px 50px rgba(0,0,0,0.07); transform: translateY(-8px); border-color: transparent; }
                
                .product-img-wrapper { width: 100%; aspect-ratio: 1 / 1; margin: 0; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; }
                .product-img-wrapper img { width: 100%; height: 100%; transition: 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); object-fit: cover; }
                .hover-img { position: absolute !important; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transform: scale(1.1); object-fit: cover; }
                .has-hover:hover .main-img { opacity: 0; transform: scale(0.9); }
                .has-hover:hover .hover-img { opacity: 1; transform: scale(1); }
                
                .product-content { padding: 20px; display: flex; flex-direction: column; flex: 1; }
                .product-title { font-size: 15px; color: #111; height: 44px; overflow: hidden; font-weight: 700; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 12px; transition: color 0.3s ease; }
                .product-title:hover { color: #A101F6; cursor: pointer; }
                .product-price { color: #A101F6; font-weight: 800; font-size: 20px; margin-bottom: 20px; }
                .login-price-notice { color: #888; font-size: 13px; font-weight: 600; font-style: italic; }
                
                .view-details-btn { display: block; width: 100%; border: none; background: #A101F6; color: #fff; padding: 14px 0; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 13px; margin-top: auto; letter-spacing: 0.5px; text-transform: uppercase; }
                .view-details-btn:hover { background: #111; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
                
                /* Clear Filters Button */
                .clear-all-btn { display: block !important; width: 100% !important; background: #f5f5f5 !important; color: #333 !important; padding: 14px 0 !important; border-radius: 12px !important; font-weight: 700 !important; cursor: pointer !important; transition: 0.3s !important; border: 1px solid #ddd !important; font-size: 13px !important; text-transform: uppercase !important; letter-spacing: 0.8px !important; margin-bottom: 25px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important; }
                .clear-all-btn:hover { background: #fee !important; color: #f44 !important; border-color: #fcc !important; box-shadow: 0 4px 12px rgba(255,68,68,0.15) !important; transform: translateY(-2px); }
                .clear-all-btn i { margin-right: 8px !important; color: inherit !important; }
                /* Mobile Filter Toggle Button */
                .mobile-filter-bar { display: none; align-items: center; justify-content: space-between; margin-bottom: 25px; width: 100%; gap: 15px; }
                .sidebar-toggle-btn { display: flex; align-items: center; gap: 12px; background: #fff; border: 1.5px solid #eee; padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: 700; color: #111; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: 0.3s; }
                .sidebar-toggle-btn:hover { background: #f9f9f9; border-color: #A101F6; color: #A101F6; box-shadow: 0 8px 20px rgba(161, 1, 246, 0.1); }
                .sidebar-toggle-btn i { font-size: 18px; }
                /* Sidebar Drawer Styles */
                .sidebar-close-btn { display: none; position: absolute; top: 15px; right: 15px; width: 44px; height: 44px; background: #f8f8f8; border-radius: 50%; align-items: center; justify-content: center; cursor: pointer; color: #333; transition: 0.3s; z-index: 10; font-size: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .sidebar-close-btn:hover { background: #f0f0f0; color: #ff0000; transform: rotate(90deg); }
                .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 9998; backdrop-filter: blur(4px); opacity: 0; transition: 0.3s; pointer-events: none; }
                .sidebar-overlay.active { display: block; opacity: 1; pointer-events: auto; }
                body.noscroll { overflow: hidden; }

                /* Wishlist Heart on Card */
                .wcs-shop-heart { 
                    position: absolute; 
                    top: 15px; 
                    right: 15px; 
                    width: 35px; 
                    height: 35px; 
                    background: #fff; 
                    border-radius: 50%; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    z-index: 10; 
                    cursor: pointer; 
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
                    transition: 0.2s; 
                    color: #ccc;
                }
                .wcs-shop-heart:hover { transform: scale(1.1); color: #ff4444; }
                .wcs-shop-heart.active { color: #ff4444; }
                .wcs-shop-heart .dashicons { font-size: 20px; width: 20px; height: 20px; }

                /* Responsive Media Queries */
                @media (max-width: 1024px) {
                    .mobile-filter-bar { display: flex; }
                    #result-count { display: none; } /* Hide on mobile like screenshot or keep compact */
                    .shop-sidebar-wrapper { 
                        position: fixed; top: 0; left: -350px; width: 320px; height: 100vh; z-index: 9999; 
                        box-shadow: 20px 0 60px rgba(0,0,0,0.15); border-radius: 0; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
                        overflow-y: auto; padding: 70px 25px 40px; border: none;
                    }
                    .shop-sidebar-wrapper.active { left: 0; }
                    .sidebar-close-btn { display: flex; }
                    .products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 15px; }
                    .shop-container-wrapper { padding: 25px 15px; flex-direction: column; }
                    .shop-grid-container { width: 100%; }
                }
                @media (max-width: 600px) {
                    .products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px; }
                    .product-card { padding: 15px 10px; border-radius: 18px; }
                    .product-img-wrapper { height: 180px; }
                    .product-title { font-size: 13px; height: 38px; }
                    .product-price { font-size: 17px; }
                    .view-details-btn { padding: 10px 0; font-size: 11px; }
                    .sidebar-toggle-btn { flex: 1; justify-content: center; }
                }
            </style>
            <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
            <div class="shop-container-wrapper">
                <div class="shop-sidebar-wrapper" id="shop-sidebar">
                    <div class="sidebar-close-btn" onclick="toggleSidebar()"><i class="fa fa-times"></i></div>
                    <button class="clear-all-btn" onclick="clearAllFilters()"><i class="fa fa-refresh"></i> Clear All Filters</button>
                    <!-- Search Widget -->
                    <div class="filter-widget">
                        <div class="widget-title">Quick Search</div>
                        <div class="search-box-wrapper">
                            <input type="text" id="shop-search-input" placeholder="What are you looking for?">
                            <button class="search-btn" onclick="fetchProducts()"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                    <!-- Categories -->
                    <div class="filter-widget">
                        <div class="widget-title">Shop by Category</div>
                        <ul class="category-list">
                            <li>
                                <div class="cat-row-wrapper">
                                    <div class="cat-toggle-zone">
                                        <div class="cat-toggle-placeholder"></div>
                                        <span class="cat-name-label">All Products</span>
                                    </div>
                                    <input type="radio" name="cat_filter" value="" checked onchange="setCategory('')" class="cat-radio">
                                </div>
                            </li>
                            <?php 
                            foreach($categories as $cat) {
                                $this->render_category_recursive($cat);
                            } 
                            ?>
                        </ul>
                    </div>
                    <!-- Brands -->
                    <div class="filter-widget">
                        <div class="widget-title">Relevant Brands</div>
                        <ul class="category-list" id="sidebar-brands-list"><li>Loading...</li></ul>
                    </div>
                    <!-- Price Filter -->
                    <div class="filter-widget">
                        <div class="widget-title">Price Range</div>
                        <div class="price-range-slider">
                            <div class="price-inputs">
                                <span>$ <span id="min-price-txt">0</span></span>
                                <span>$ <span id="max-price-txt">5000</span>+</span>
                            </div>
                            <div class="slider-container"><div id="slider-progress" class="slider-progress"></div></div>
                            <div class="slider-inputs">
                                <input type="range" id="min-price-range" min="0" max="5000" value="0">
                                <input type="range" id="max-price-range" min="0" max="5000" value="5000">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="shop-grid-container" style="flex:1;">
                    <div id="brand-header-display">
                        <img id="header-brand-logo" src="" class="selected-brand-logo" alt="Brand Logo">
                    </div>
                    <div class="mobile-filter-bar">
                        <div class="sidebar-toggle-btn" onclick="toggleSidebar()">
                            <i class="fa fa-bars"></i>
                            <span>Sidebar</span>
                        </div>
                    </div>
                    <div id="result-count" style="margin-bottom:25px; font-weight: 700; color: #999; font-size: 14px; text-transform: uppercase; letter-spacing:1px;">Showing results...</div>
                    <div id="products-grid" class="products-grid"></div>
                    <div id="pagination" style="display:flex; justify-content:center; gap:10px; margin-top:50px;"></div>
                </div>
            </div>
            <script>
                (function($){
                    const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
                    let filters = { action: 'wcs_filter_products', category: '', brand: '', min_price: 0, max_price: 5000, search: '', page: 1 };
                    let currentBrands = [];
                    window.clearAllFilters = function() {
                        filters = { action: 'wcs_filter_products', category: '', brand: '', min_price: 0, max_price: 5000, search: '', page: 1 };
                        $('#shop-search-input').val('');
                        $('#min-price-range').val(0);
                        $('#max-price-range').val(5000);
                        $('#min-price-txt').text('0');
                        $('#max-price-txt').text('5000');
                        $('#slider-progress').css({'left': '0%', 'right': '0%'});
                        $('input[type="radio"]').prop('checked', false);
                        $('input[name="cat_filter"][value=""]').prop('checked', true);
                        $('input[name="brand_filter"][value=""]').prop('checked', true);
                        $('#brand-header-display').hide();
                        
                        // Get base shop page
                        let basePath = window.location.pathname;
                        if(basePath.includes('/brand/')) {
                            basePath = basePath.substring(0, basePath.indexOf('/brand/'));
                        }
                        if(basePath.includes('/product-category/')) {
                            basePath = basePath.substring(0, basePath.indexOf('/product-category/'));
                        }
                        const baseURL = window.location.origin + (basePath || '/shop/');
                        window.history.replaceState({filters: filters}, '', baseURL);
                        
                        fetchProducts();
                        fetchRelevantBrands();
                        if($('#shop-sidebar').hasClass('active')) toggleSidebar();
                    }
                    
                    window.toggleSidebar = function() {
                        $('#shop-sidebar').toggleClass('active');
                        $('.sidebar-overlay').toggleClass('active');
                        $('body').toggleClass('noscroll');
                    }
                    window.fetchProducts = function() {
                        filters.search = $('#shop-search-input').val();
                        $('#products-grid').css('opacity', '0.5');
                        $.post(ajaxurl, filters, function(res) {
                            $('#products-grid').css('opacity', '1').html(res.data.html);
                            $('#result-count').text('Showing ' + res.data.total + ' premium items');
                            renderPagination(res.data.current, res.data.max_pages);
                            
                            // Apply Wishlist Active State (Persistence)
                            if(res.data.wishlist) {
                                res.data.wishlist.forEach(function(pid){
                                    $('.wcs-wishlist-btn[data-id="'+pid+'"]').addClass('active');
                                });
                            }
                        });
                    }
                    window.fetchRelevantBrands = function(callback) {
                        $.post(ajaxurl, { action: 'wcs_get_relevant_brands', category: filters.category }, function(res) {
                            if (res.success) {
                                currentBrands = res.data.brands;
                                let html = '<li class="category-item"><span>All Brands</span><input type="radio" name="brand_filter" value="" ' + (filters.brand === "" ? 'checked' : '') + ' onchange="setBrand(\'\')" class="cat-radio"></li>';
                                currentBrands.forEach(b => {
                                    const checked = b.slug === filters.brand ? 'checked' : '';
                                    html += `<li class="category-item"><span>${b.name}</span><input type="radio" name="brand_filter" value="${b.slug}" ${checked} onchange="setBrand('${b.slug}')" class="cat-radio"></li>`;
                                });
                                $('#sidebar-brands-list').html(html);
                                if(callback) callback();
                            }
                        });
                    }
                    window.setCategory = function(s) { 
                        filters.category = s; 
                        filters.brand = ''; 
                        $('#brand-header-display').hide(); 
                        updateURL();
                        fetchProducts(); 
                        fetchRelevantBrands(); 
                    }
                    window.setBrand = function(slug) { 
                        filters.brand = slug; 
                        const brand = currentBrands.find(b => b.slug === slug);
                        if(brand && slug !== '') {
                            if(brand.logo) {
                                $('#header-brand-logo').attr('src', brand.logo);
                                $('#brand-header-display').css('display', 'flex');
                            } else {
                                $('#brand-header-display').hide();
                            }
                        } else { 
                            $('#brand-header-display').hide(); 
                        }
                        updateURL();
                        fetchProducts(); 
                    }
                    
                    function updateURL() {
                        let newPath = '';
                        let params = new URLSearchParams();
                        
                        // Scenario: Both Category and Brand
                        if(filters.category && filters.brand) {
                            newPath = '/product-category/' + filters.category + '/';
                            params.set('brand', filters.brand);
                        }
                        // Scenario: Only Brand
                        else if(filters.brand) {
                            newPath = '/brand/' + filters.brand + '/';
                        }
                        // Scenario: Only Category
                        else if(filters.category) {
                            newPath = '/product-category/' + filters.category + '/';
                        }
                        // Scenario: No filters or Search only
                        else {
                            let basePath = window.location.pathname;
                            if(basePath.includes('/brand/')) basePath = basePath.substring(0, basePath.indexOf('/brand/'));
                            if(basePath.includes('/product-category/')) basePath = basePath.substring(0, basePath.indexOf('/product-category/'));
                            newPath = basePath || '/shop/';
                        }
                        
                        if(filters.search) params.set('search', filters.search);
                        
                        if(!newPath.endsWith('/')) newPath += '/';
                        const newURL = window.location.origin + newPath + (params.toString() ? '?' + params.toString() : '');
                        
                        if(window.location.href !== newURL) {
                            window.history.replaceState({filters: filters}, '', newURL);
                        }
                    }
                    function initFromURL() {
                        const params = new URLSearchParams(window.location.search);
                        const pathSegments = window.location.pathname.split('/').filter(s => s !== '');
                        
                        // Support for Query Params
                        let cat = params.get('product_cat') || params.get('category') || params.get('cat');
                        let brand = params.get('pwb-brand') || params.get('brand') || params.get('product_brand');
                        // Support for Pretty Permalinks (Nested Path based)
                        // Picks the LAST segment after the trigger word
                        const brandIdx = pathSegments.indexOf('brand');
                        if (brandIdx !== -1 && pathSegments.length > brandIdx + 1) {
                            brand = pathSegments[pathSegments.length - 1];
                        }
                        const catIdx = pathSegments.indexOf('product-category');
                        if (catIdx !== -1 && pathSegments.length > catIdx + 1) {
                            cat = pathSegments[pathSegments.length - 1];
                        }
                        if(cat) {
                            filters.category = cat;
                            const radio = $(`.cat-radio[value="${cat}"]`);
                            if(radio.length) {
                                $('.cat-radio[value=""]').prop('checked', false);
                                radio.prop('checked', true);
                                const node = radio.closest('.category-node');
                                node.addClass('open').parents('.category-node').addClass('open');
                                node.parents('.sub-cat-list').show();
                                node.children('.sub-cat-list').show();
                            }
                        }
                        if(brand) {
                            filters.brand = brand;
                        }
                        
                        fetchProducts();
                        fetchRelevantBrands(function() {
                            if(filters.brand) {
                                const brandRadio = $(`.cat-radio[name="brand_filter"][value="${filters.brand}"]`);
                                if(brandRadio.length) {
                                    brandRadio.prop('checked', true);
                                }
                                // Show brand logo header after brands are loaded
                                const selectedBrand = currentBrands.find(b => b.slug === filters.brand);
                                if(selectedBrand && selectedBrand.logo) {
                                    $('#header-brand-logo').attr('src', selectedBrand.logo);
                                    $('#brand-header-display').css('display', 'flex');
                                }
                            }
                        });
                    }
                    $('#shop-search-input').on('keyup', function() { clearTimeout(window.st); window.st = setTimeout(fetchProducts, 800); });
                    $('#min-price-range, #max-price-range').on('input', function(){
                        let min = parseInt($('#min-price-range').val()), max = parseInt($('#max-price-range').val());
                        if(min > max - 200) { if(this.id === 'min-price-range') $('#min-price-range').val(max - 200); else $('#max-price-range').val(min + 200); return; }
                        $('#min-price-txt').text(min); $('#max-price-txt').text(max);
                        $('#slider-progress').css({'left': (min/50) + '%', 'right': (100 - max/50) + '%'});
                        filters.min_price = min; filters.max_price = max;
                        clearTimeout(window.pt); window.pt = setTimeout(fetchProducts, 600);
                    });
                    function renderPagination(current, max) {
                        const container = document.getElementById('pagination'); container.innerHTML = '';
                        if (max <= 1) return;
                        for (let i = 1; i <= max; i++) {
                            const btn = document.createElement('button');
                            btn.className = (i === current ? 'view-details-btn active' : 'view-details-btn');
                            btn.style.width = '45px'; btn.style.padding = '12px 0';
                            if(i === current) btn.style.background = '#111';
                            btn.innerText = i;
                            btn.onclick = () => { filters.page = i; fetchProducts(); window.scrollTo({top:0, behavior:'smooth'}); };
                            container.appendChild(btn);
                        }
                    }
                    $(document).ready(function() {
                        initFromURL();
                        // Robust Accordion Toggle (Namespace prevents duplicates, off() clears old)
                        $(document).off('click.wcs_toggle').on('click.wcs_toggle', '.cat-toggle-zone', function(e) {
                            const node = $(this).closest('.category-node');
                            if(node.hasClass('has-children')) {
                                e.preventDefault();
                                e.stopPropagation();
                                node.toggleClass('open');
                                node.children('.sub-cat-list').stop(true, true).slideToggle(200);
                            }
                        });
                        // Keep selection separate
                        $(document).off('click.wcs_radio').on('click.wcs_radio', '.cat-radio', function(e) {
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                        });
                    });
                })(jQuery);
            </script>
            <?php
            return ob_get_clean();
        }
        private function render_category_recursive($cat) {
            $children = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $cat->term_id ) );
            $has_children = !empty($children);
            ?>
            <li class="category-node <?php echo $has_children ? 'has-children' : ''; ?>">
                <div class="cat-row-wrapper">
                    <div class="cat-toggle-zone">
                        <?php if($has_children): ?>
                            <div class="cat-toggle"><i class="fa fa-chevron-right"></i></div>
                        <?php else: ?>
                            <div class="cat-toggle-placeholder"></div>
                        <?php endif; ?>
                        <span class="cat-name-label"><?php echo $cat->name; ?></span>
                    </div>
                    <input type="radio" name="cat_filter" value="<?php echo $cat->slug; ?>" onchange="setCategory('<?php echo $cat->slug; ?>')" class="cat-radio">
                </div>
                <?php if($has_children): ?>
                    <ul class="sub-cat-list">
                        <?php foreach($children as $child) $this->render_category_recursive($child); ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
        }
        public function ajax_get_relevant_brands() {
            $cat = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
            
            // Try cache (Versioned to force refresh after logic updates)
            $cache_key = 'wcs_brands_v2_' . ( $cat ? $cat : 'all' );
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                wp_send_json_success( array( 'brands' => $cached ) );
                return;
            }

            global $wpdb;
            $data = array();

            if ( $cat ) {
                // Get the term object to find ID and Children
                $term = get_term_by('slug', $cat, 'product_cat');
                if ($term) {
                    // Get all children (subcategories)
                    $children = get_term_children( $term->term_id, 'product_cat' );
                    if ( is_wp_error($children) ) $children = array();
                    $term_ids = array_merge( array( $term->term_id ), $children );
                    $ids_str = implode(',', array_map('intval', $term_ids));

                    // REFINED SQL: Use EXISTS for better performance and reliability
                    // Finds Brands that have at least one product in the selected category tree
                    $query = "
                        SELECT DISTINCT t.term_id, t.name, t.slug 
                        FROM {$wpdb->terms} t
                        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                        WHERE tt.taxonomy = 'pwb-brand'
                        AND EXISTS (
                            SELECT 1 
                            FROM {$wpdb->term_relationships} tr
                            INNER JOIN {$wpdb->term_relationships} tr_cat ON tr.object_id = tr_cat.object_id
                            INNER JOIN {$wpdb->term_taxonomy} tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
                            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
                            AND tt_cat.taxonomy = 'product_cat'
                            AND tt_cat.term_id IN ($ids_str)
                        )
                        ORDER BY t.name ASC
                    ";
                    
                    $brands = $wpdb->get_results($query);
                } else {
                    $brands = array();
                }
            } else {
                // No category selected, get all brands
                $brands = get_terms( array('taxonomy' => 'pwb-brand', 'hide_empty' => true) );
            }

            if( !empty($brands) && !is_wp_error($brands) ) {
                foreach($brands as $b) { 
                    $logo_id = get_term_meta( $b->term_id, 'pwb_brand_image', true );
                    $data[] = array('slug' => $b->slug, 'name' => $b->name, 'logo' => $logo_id ? wp_get_attachment_url( $logo_id ) : ''); 
                }
            }

            set_transient( $cache_key, $data, HOUR_IN_SECONDS );
            wp_send_json_success(array('brands' => $data));
        }
        private function get_wishlist_ids() {
            if ( is_user_logged_in() ) {
                $uid = get_current_user_id();
                $items = get_user_meta( $uid, '_wcs_wishlist_items', true );
                return ! empty( $items ) ? $items : array();
            } else {
                if ( isset( $_COOKIE['wcs_wishlist_items'] ) ) {
                    $items = json_decode( stripslashes( $_COOKIE['wcs_wishlist_items'] ), true );
                    return ! empty( $items ) ? $items : array();
                }
            }
            return array();
        }

        public function ajax_filter_products() {
            $min = isset($_POST['min_price']) ? floatval($_POST['min_price']) : 0; 
            $max = isset($_POST['max_price']) ? floatval($_POST['max_price']) : 5000;
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            
            $args = array('post_type' => 'product', 'posts_per_page' => 12, 'paged' => $page, 'post_status' => 'publish', 
                'meta_query' => array(array('key' => '_price', 'value' => array($min, $max), 'type' => 'numeric', 'compare' => 'BETWEEN'))
            );
            if($search) $args['s'] = $search;
            $tx = array('relation' => 'AND');
            if(!empty($_POST['category'])) $tx[] = array('taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $_POST['category']);
            if(!empty($_POST['brand'])) $tx[] = array('taxonomy' => 'pwb-brand', 'field' => 'slug', 'terms' => $_POST['brand']);
            if(count($tx) > 1) $args['tax_query'] = $tx;
            
            // Check transient cache (skip for search queries)
            // FIXED: Include user role in cache key so Guests dont see Wholesale prices and vice-versa
            $user_key = 'guest';
            if ( is_user_logged_in() ) {
                $u = wp_get_current_user();
                $user_key = implode('_', $u->roles);
            }
            $cache_key = 'wcs_shop_' . $user_key . '_' . md5(serialize(array_filter($_POST)));

            if(!$search) {
                $cached = get_transient($cache_key);
                if(false !== $cached) {
                    $cached['wishlist'] = $this->get_wishlist_ids();
                    wp_send_json_success($cached);
                    return;
                }
            }
            
            $query = new WP_Query($args); $html = '';
            $is_logged_in = is_user_logged_in();
            $login_url = $is_logged_in ? '' : get_permalink(get_option('woocommerce_myaccount_page_id'));
            if($query->have_posts()) {
                while($query->have_posts()){ $query->the_post(); $p = wc_get_product(get_the_ID()); $gallery = $p->get_gallery_image_ids();
                    $hover_img = !empty($gallery) ? wp_get_attachment_image_url($gallery[0], 'medium_large') : '';
                    $has_hover = !empty($hover_img) ? 'has-hover' : '';
                    
                    $price_html = $is_logged_in ? $p->get_price_html() : '<div class="login-price-notice">Please <a href="'.$login_url.'" class="login-red-link">log in</a> to view the price.</div>';
                    
                    $case = get_post_meta(get_the_ID(), 'master_case_count', true);
                    $html .= '<div class="product-card">
                        <div class="product-img-wrapper '.$has_hover.'">
                            <div class="wcs-wishlist-btn wcs-shop-heart" data-id="'.get_the_ID().'"><span class="dashicons dashicons-heart"></span></div>
                            <a href="'.get_permalink().'">
                                '.$p->get_image('medium_large', array('class' => 'main-img', 'loading' => 'lazy')).'
                                '.($hover_img ? '<img src="'.$hover_img.'" class="hover-img" loading="lazy">' : '').'
                            </a>
                        </div>
                        <div class="product-content">
                            <a href="'.get_permalink().'" style="text-decoration:none; color:inherit;"><div class="product-title">'.get_the_title().'</div></a>
                            <div class="product-price">'.$price_html.'</div>
                            '.($case ? '<div style="font-size:12px; color:#A101F6; font-weight:700; margin-bottom:15px;">CASE COUNT: '.$case.'</div>' : '').'
                            <a href="'.get_permalink().'" class="view-details-btn">View details</a>
                        </div>
                    </div>';
                }
                wp_reset_postdata();
            }
            
            $response = array('html' => $html ?: '<div style="grid-column:1/-1; padding:100px; text-align:center;">No match found.</div>', 'total' => $query->found_posts, 'max_pages' => $query->max_num_pages, 'current' => $page);
            
            if(!$search) set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
            $response['wishlist'] = $this->get_wishlist_ids();
            wp_send_json_success($response);
        }
    }
    new Standalone_WCS_Shop_Filter();
}