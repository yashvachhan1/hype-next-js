<?php
$is_logged_in = is_user_logged_in() ? 'true' : 'false';
$login_url = wp_login_url(get_permalink());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Grid with Filters</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 
         * Shop Complete Styles
         * Color Theme: #A101F6 (Primary), #510E7A (Dark)
         */

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        /* 
         * LAYOUT CONTAINER 
         * Uses Flexbox for Sidebar (Left) and Grid (Right)
         */
        .shop-container-wrapper {
            display: flex;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
            align-items: flex-start;
        }

        /* Responsive Breakpoint */
        @media (max-width: 900px) {
            .shop-container-wrapper {
                flex-direction: column;
            }

            .shop-sidebar-wrapper {
                width: 100% !important;
                /* Full width on mobile */
                margin-bottom: 30px;
            }

            .shop-grid-container {
                width: 100% !important;
            }
        }

        /* ========================
           SIDEBAR STYLES (Copied & Refined)
           ======================== */
        .shop-sidebar-wrapper {
            width: 300px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(161, 1, 246, 0.1);
            transition: all 0.3s ease;
        }

        .shop-sidebar-wrapper:hover {
            box-shadow: 0 15px 50px rgba(161, 1, 246, 0.12);
        }

        .filter-widget {
            margin-bottom: 30px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 30px;
        }

        .filter-widget:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .widget-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Search */
        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            border-color: #A101F6;
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #A101F6;
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .search-box button:hover {
            background: #510E7A;
        }

        /* Categories */
        .category-list,
        .sub-category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sub-category-list {
            padding-left: 15px;
            border-left: 2px solid #f0f0f0;
            margin-left: 5px;
            margin-top: 5px;
        }

        .category-item {
            margin-bottom: 8px;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            transition: color 0.2s;
        }

        .category-header:hover {
            color: #A101F6;
        }

        .cat-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .cat-radio {
            accent-color: #A101F6;
            margin: 0;
            cursor: pointer;
        }

        .cat-count {
            font-size: 11px;
            color: #999;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 10px;
        }

        .toggle-icon {
            font-size: 10px;
            color: #ccc;
            padding: 5px;
            cursor: pointer;
            width: 15px;
            text-align: center;
        }

        .toggle-icon:hover {
            color: #A101F6;
        }

        /* Price Slider */
        .price-inputs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }

        .range-slider {
            width: 100%;
            position: relative;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
        }

        .range-progress {
            position: absolute;
            left: 0;
            right: 0;
            height: 100%;
            background: #A101F6;
            border-radius: 2px;
        }

        .range-input {
            position: relative;
        }

        .range-input input {
            position: absolute;
            top: -6px;
            height: 5px;
            width: 100%;
            background: none;
            pointer-events: none;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
        }

        .range-input input::-webkit-slider-thumb {
            height: 18px;
            width: 18px;
            border-radius: 50%;
            border: 3px solid #A101F6;
            background: #fff;
            pointer-events: auto;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Colors */
        .color-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .color-item {
            cursor: pointer;
            position: relative;
        }

        .color-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #f0f0f0;
            display: block;
            transition: all 0.2s;
        }

        .color-item.active .color-circle {
            border-color: #A101F6;
            transform: scale(1.1);
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #A101F6;
        }

        .color-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            white-space: nowrap;
            margin-bottom: 5px;
        }

        .color-item:hover .color-tooltip {
            opacity: 1;
        }

        /* ========================
           GRID STYLES (Copied & Refined)
           ======================== */
        .shop-grid-container {
            flex-grow: 1;
            /* Allow grid to fill remaining space */
            width: 100%;
            /* Fallback */
        }

        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .result-count {
            font-size: 14px;
            color: #555;
        }

        .sort-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            color: #333;
            outline: none;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
            width: 100%;
        }

        /* Product Card */
        .product-card {
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
            border-color: #e0e0e0;
        }

        .product-img-wrapper {
            position: relative;
            height: 220px;
            background: #f9f9f9;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .product-actions {
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            transition: bottom 0.3s ease;
        }

        .product-card:hover .product-actions {
            bottom: 0;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #A101F6;
            color: #fff;
            border-color: #A101F6;
        }

        .product-info {
            padding: 16px;
            text-align: center;
        }

        .product-category {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .product-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 42px;
        }

        .product-title a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s;
        }

        .product-title a:hover {
            color: #A101F6;
        }

        .product-price {
            font-weight: 700;
            color: #A101F6;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .product-price del {
            color: #ccc;
            font-size: 13px;
            font-weight: 400;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 15px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #A101F6;
        }

        /* View Toggle */
        .view-toggle-wrapper {
            display: flex;
            gap: 10px;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 6px;
            margin-right: auto;
            margin-left: 15px;
        }

        .view-toggle-btn {
            padding: 8px 20px;
            border: none;
            background: transparent;
            color: #666;
            cursor: pointer;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .view-toggle-btn:hover {
            color: #A101F6;
        }

        .view-toggle-btn.active {
            background: #A101F6;
            color: #fff;
        }

        /* Brands Grid Styles */
        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }

        .brand-card {
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .brand-card:hover {
            box-shadow: 0 10px 30px rgba(161, 1, 246, 0.15);
            transform: translateY(-5px);
            border-color: #A101F6;
        }

        .brand-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #A101F6, #510E7A);
            transition: left 0.3s ease;
        }

        .brand-card:hover::before {
            left: 0;
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
        }

        .brand-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: grayscale(100%);
            transition: filter 0.3s;
        }

        .brand-card:hover .brand-logo img {
            filter: grayscale(0%);
        }

        .brand-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .brand-card:hover .brand-name {
            color: #A101F6;
        }

        .brand-count {
            font-size: 13px;
            color: #999;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
            padding: 20px 0;
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:hover:not(:disabled) {
            border-color: #A101F6;
            color: #A101F6;
            background: #f9f0ff;
        }

        .page-btn.active {
            background: #A101F6;
            color: #fff;
            border-color: #A101F6;
        }

        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .page-dots {
            padding: 0 8px;
            color: #999;
        }

        /* Loading */
        .loading-grid {
            text-align: center;
            padding: 50px;
            color: #999;
            width: 100%;
            grid-column: 1 / -1;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #A101F6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive - Disable sticky on mobile */
        @media (max-width: 900px) {
            .shop-sidebar-wrapper {
                position: static;
                max-height: none;
            }
        }
    </style>
</head>

<body>

    <div class="shop-container-wrapper">

        <!-- SIDEBAR (Left) -->
        <div class="shop-sidebar-wrapper">
            <!-- Search -->
            <div class="filter-widget">
                <div class="widget-title">Filter By:</div>
                <div class="search-box">
                    <input type="text" id="filter-search" placeholder="Search Products...">
                    <button id="btn-search"><i class="fa fa-search"></i></button>
                </div>
            </div>

            <!-- Categories -->
            <div class="filter-widget">
                <div class="widget-title">
                    Filter by Categories
                    <i class="fa fa-minus" style="font-size:12px; color:#aaa;"></i>
                </div>
                <ul class="category-list" id="category-list-container">
                    <li class="category-item">
                        <div class="category-header">
                            <label class="cat-name" style="cursor:pointer; flex:1;">
                                <input type="radio" name="cat_filter" value="" class="cat-radio" checked onchange="setCategory('')">
                                All Products
                            </label>
                        </div>
                    </li>
                    <?php foreach ( $categories as $cat ) : ?>
                        <li class="category-item">
                            <div class="category-header">
                                <label class="cat-name" style="cursor:pointer; flex:1;">
                                    <input type="radio" name="cat_filter" value="<?php echo esc_attr( $cat->slug ); ?>" class="cat-radio" onchange="setCategory('<?php echo esc_attr( $cat->slug ); ?>')">
                                    <?php echo esc_html( $cat->name ); ?>
                                </label>
                                <span class="cat-count"><?php echo esc_html( $cat->count ); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Brands (New) -->
            <div class="filter-widget">
                <div class="widget-title">
                    Filter by Brand
                    <i class="fa fa-minus" style="font-size:12px; color:#aaa;"></i>
                </div>
                <ul class="category-list" id="brand-list-container">
                    <?php foreach ( $brands as $brand ) : ?>
                        <li class="category-item">
                            <div class="category-header" style="cursor: pointer;" onclick="setBrand('<?php echo esc_attr( $brand->slug ); ?>')">
                                <label class="cat-name" style="flex:1;">
                                    <input type="radio" name="brand_filter" value="<?php echo esc_attr( $brand->slug ); ?>" class="cat-radio">
                                    <?php echo esc_html( $brand->name ); ?>
                                </label>
                                <span class="cat-count"><?php echo esc_html( $brand->count ); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Price -->
            <div class="filter-widget">
                <div class="widget-title">
                    Filter by Prices
                    <i class="fa fa-minus" style="font-size:12px; color:#aaa;"></i>
                </div>
                <div class="price-filter-container">
                    <div class="range-slider">
                        <div class="range-progress"></div>
                    </div>
                    <div class="range-input">
                        <input type="range" class="range-min" min="0" max="1000" value="0">
                        <input type="range" class="range-max" min="0" max="1000" value="1000">
                    </div>
                    <div class="price-inputs" style="margin-top:20px;">
                        <span>Price: $<span id="min-price-display">0</span> — $<span
                                id="max-price-display">1000</span></span>
                    </div>
                </div>
            </div>


            <button onclick="resetFilters()"
                style="width:100%; padding:10px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#555; border-radius:4px;">Reset
                Filters</button>

        </div>


        <!-- MAIN GRID (Right) -->
        <div class="shop-grid-container">
            <!-- Header -->
            <div class="shop-header">
                <div class="result-count" id="result-count">Loading products...</div>

                <!-- View Toggle: Products / Brands -->
                <div class="view-toggle-wrapper">
                    <button class="view-toggle-btn active" onclick="switchView('products')" id="products-view-btn">
                        <i class="fa fa-box"></i> Products
                    </button>
                    <button class="view-toggle-btn" onclick="switchView('brands')" id="brands-view-btn">
                        <i class="fa fa-tag"></i> Brands
                    </button>
                </div>

                <select class="sort-select" id="sort-select" onchange="updateSort(this.value)">
                    <option value="popularity">Sort by popularity</option>
                    <option value="date">Sort by latest</option>
                    <option value="price">Sort by price: low to high</option>
                    <option value="price-desc">Sort by price: high to low</option>
                    <option value="name">Sort by name (A-Z)</option>
                    <option value="count">Sort by product count</option>
                </select>
            </div>

            <!-- Grid -->
            <div class="products-grid" id="products-grid">
                <!-- Loading State -->
                <div class="loading-grid">
                    <div class="loading-spinner"></div>
                    <p>Fetching products...</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination-container" style="margin-top: 30px; display: flex; justify-content: center;">
                <div class="pagination" id="pagination"></div>
            </div>
        </div>

    </div>

    <style>
        /* Pagination Styles */
        .pagination { display: flex; gap: 8px; flex-wrap: wrap; }
        .page-btn {
            padding: 8px 16px; border: 1px solid #ddd; background: #fff;
            color: #510E7A; cursor: pointer; border-radius: 6px; font-weight: 600;
            transition: all 0.2s;
        }
        .page-btn:hover { background: #510E7A; color: #fff; border-color: #510E7A; }
        .page-btn.active { background: #510E7A; color: #fff; border-color: #510E7A; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>

    <!-- PRODUCT CARD TEMPLATE (Optimization) -->
    <template id="product-card-template">
        <div class="product-card">
            <div class="product-img-wrapper">
                <a href="#" class="link-wrapper">
                    <img src="" class="product-img main-img">
                    <img src="" class="product-img hover-img">
                </a>
                <span class="sale-badge" style="display:none;">Sale!</span>
                <div class="product-actions">
                    <button class="action-btn" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                    <button class="action-btn" title="Quick View"><i class="fa fa-eye"></i></button>
                </div>
            </div>
            <div class="product-info">
                <div class="product-category"></div>
                <h3 class="product-title"><a href="#"></a></h3>
                <div class="product-price"></div>
                <button class="add-to-cart-btn">View Product</button>
            </div>
        </div>
    </template>

    <!-- MAIN SCRIPT -->
    <script>
        const ajaxurl = wcs_shop_ajax.ajax_url;
        const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
        
        // State
        let currentFilters = {
            action: 'wcs_filter_products',
            search: '',
            category: '',
            brand: '',
            min_price: 0,
            max_price: 1000,
            sort: 'popularity',
            page: 1
        };

        let currentView = 'products'; // 'products' or 'brands'

        // DOM Elements
        const gridContainer = document.getElementById('products-grid');
        const countLabel    = document.getElementById('result-count');
        let debounceTimer   = null;

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            // Read URL Params
            const params = new URLSearchParams(window.location.search);
            if (params.get('s')) {
                currentFilters.search = params.get('s');
                document.getElementById('filter-search').value = currentFilters.search;
            }
            if (params.get('product_cat')) currentFilters.category = params.get('product_cat');
            if (params.get('min_price'))   currentFilters.min_price = params.get('min_price');
            if (params.get('max_price'))   currentFilters.max_price = params.get('max_price');

            refreshView();
        });

        /**
         * Refresh View based on status
         */
        function refreshView() {
            if (currentView === 'brands') {
                fetchBrands();
            } else {
                fetchProducts();
            }
        }

        /**
         * AJAX Product Fetching
         */
        function fetchProducts() {
            gridContainer.className = 'products-grid';
            gridContainer.style.opacity = '0.5';
            countLabel.innerText = 'Updating...';

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: currentFilters,
                success: function(response) {
                    gridContainer.style.opacity = '1';
                    if (response.success) {
                        gridContainer.innerHTML = response.data.html;
                        countLabel.innerText = `Showing ${response.data.total} results`;
                        renderPagination(response.data.current, response.data.max_pages);
                    } else {
                        gridContainer.innerHTML = '<div class="no-products">Error loading products.</div>';
                    }
                },
                error: function() {
                    gridContainer.style.opacity = '1';
                    gridContainer.innerHTML = '<div class="no-products">Server error.</div>';
                }
            });
        }

        /**
         * AJAX Brands Fetching
         */
        function fetchBrands() {
            gridContainer.className = 'brands-grid';
            gridContainer.style.opacity = '0.5';
            countLabel.innerText = 'Loading brands...';
            document.getElementById('pagination').innerHTML = '';

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wcs_get_brands_grid',
                    sort: currentFilters.sort
                },
                success: function(response) {
                    gridContainer.style.opacity = '1';
                    if (response.success) {
                        gridContainer.innerHTML = response.data.html;
                        countLabel.innerText = 'Showing all brands';
                    } else {
                        gridContainer.innerHTML = '<div class="no-products">Error loading brands.</div>';
                    }
                }
            });
        }

        /**
         * Render Pagination
         */
        function renderPagination(current, max) {
            const container = document.getElementById('pagination');
            container.innerHTML = '';
            if (max <= 1) return;

            // Page buttons logic
            for (let i = 1; i <= max; i++) {
                if (i === 1 || i === max || (i >= current - 2 && i <= current + 2)) {
                    const btn = document.createElement('button');
                    btn.className = 'page-btn' + (i === current ? ' active' : '');
                    btn.innerText = i;
                    btn.onclick = () => {
                        currentFilters.page = i;
                        fetchProducts();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    };
                    container.appendChild(btn);
                } else if (i === current - 3 || i === current + 3) {
                    const dots = document.createElement('span');
                    dots.innerText = '...';
                    dots.style.padding = '8px';
                    container.appendChild(dots);
                }
            }
        }

        /**
         * View Switching
         */
        function switchView(view) {
            currentView = view;
            document.getElementById('products-view-btn').classList.toggle('active', view === 'products');
            document.getElementById('brands-view-btn').classList.toggle('active', view === 'brands');
            refreshView();
        }

        /**
         * Actions & Filters
         */
        function setCategory(slug) {
            currentFilters.category = slug;
            currentFilters.page = 1;
            if (currentView === 'brands') currentView = 'products'; // Switch to products when cat selected
            switchView('products');
        }

        function setBrand(slug) {
            currentFilters.brand = slug;
            currentFilters.page = 1;
            
            // UI: Check the radio button programmatically
            const radio = document.querySelector(`input[name="brand_filter"][value="${slug}"]`);
            if (radio) radio.checked = true;

            fetchProducts();
        }

        function updateSort(val) {
            currentFilters.sort = val;
            currentFilters.page = 1;
            fetchProducts();
        }

        function resetFilters() {
            currentFilters = {
                action: 'wcs_filter_products',
                search: '',
                category: '',
                brand: '',
                min_price: 0,
                max_price: 1000,
                sort: 'popularity',
                page: 1
            };
            
            document.getElementById('filter-search').value = '';
            document.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
            const allProductsRadio = document.querySelector('input[name="cat_filter"][value=""]');
            if (allProductsRadio) allProductsRadio.checked = true;

            fetchProducts();
        }

        // Search Handling
        document.getElementById('btn-search').addEventListener('click', () => {
            currentFilters.search = document.getElementById('filter-search').value;
            currentFilters.page = 1;
            fetchProducts();
        });
        document.getElementById('filter-search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentFilters.search = document.getElementById('filter-search').value;
                currentFilters.page = 1;
                fetchProducts();
            }
        });

        // Price Filter (Debounced)
        const rangeInp = document.querySelectorAll(".range-input input");
        const progress = document.querySelector(".range-progress");
        
        rangeInp.forEach(input => {
            input.addEventListener("input", e => {
                let min = parseInt(rangeInp[0].value);
                let max = parseInt(rangeInp[1].value);
                
                document.getElementById('min-price-display').innerText = min;
                document.getElementById('max-price-display').innerText = max;
                progress.style.left = (min / rangeInp[0].max) * 100 + "%";
                progress.style.right = 100 - (max / rangeInp[1].max) * 100 + "%";

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentFilters.min_price = min;
                    currentFilters.max_price = max;
                    currentFilters.page = 1;
                    fetchProducts();
                }, 500);
            });
        });
    </script>
</body>

</html>