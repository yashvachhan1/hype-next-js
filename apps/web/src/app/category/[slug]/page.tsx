'use client';

import { useState, useEffect, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { Search, ShoppingCart, User, Heart, ChevronDown, RotateCcw, ChevronLeft, ChevronRight, Filter, X, ShoppingBag } from 'lucide-react';
import Navbar from '@/components/Navbar';
import WpProductCard from '@/components/WpProductCard';

// --- HELPER: Recursively render category tree ---
const isActiveOrHasActiveChild = (cat: any, slug: string): boolean => {
    if (cat.slug === slug) return true;
    if (cat.children) {
        return cat.children.some((c: any) => isActiveOrHasActiveChild(c, slug));
    }
    return false;
};

const CategoryItem = ({ item, currentSlug }: { item: any, currentSlug: string }) => {
    const isActive = item.slug === currentSlug;
    const hasChildren = item.children && item.children.length > 0;

    // Auto-open if this item or a child is active
    const [isOpen, setIsOpen] = useState(isActiveOrHasActiveChild(item, currentSlug));

    // Update open state if user navigates deeply (ensure parent stays open)
    useEffect(() => {
        if (isActiveOrHasActiveChild(item, currentSlug)) {
            setIsOpen(true);
        }
    }, [currentSlug, item]);

    const toggleOpen = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsOpen(!isOpen);
    };

    return (
        <li className="select-none">
            {/* Row Container */}
            <div className={`flex items-center justify-between py-2 px-2 rounded-lg transition-colors duration-200 group ${isActive ? 'bg-purple-50' : 'hover:bg-gray-50'}`}>

                <div className="flex items-center gap-2 flex-1 min-w-0">
                    {/* Chevron / Spacer */}
                    {hasChildren ? (
                        <button
                            onClick={toggleOpen}
                            className={`p-1 rounded-md text-gray-400 hover:text-purple-600 hover:bg-purple-100 transition-colors ${isOpen ? 'bg-purple-100 text-purple-600' : ''}`}
                        >
                            {isOpen ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                        </button>
                    ) : (
                        <span className="w-6 flex-shrink-0"></span>
                    )}

                    {/* Link Name */}
                    <Link href={`/category/${item.slug}`} scroll={false} className="flex-1 min-w-0 block">
                        <span className={`text-sm font-bold uppercase truncate block ${isActive ? 'text-purple-700' : 'text-gray-600 group-hover:text-purple-600'}`}>
                            {item.name}
                        </span>
                    </Link>
                </div>

                {/* Radio Button */}
                <Link href={`/category/${item.slug}`} scroll={false} className="flex-shrink-0 ml-2">
                    <div className={`w-4 h-4 rounded-full border flex items-center justify-center transition-all ${isActive ? 'border-purple-600 bg-white' : 'border-gray-300 group-hover:border-purple-300'}`}>
                        {isActive && <div className="w-2 h-2 rounded-full bg-purple-600 animate-in zoom-in duration-200"></div>}
                    </div>
                </Link>
            </div>

            {/* Recursion - Sub List */}
            {hasChildren && (
                <div className={`overflow-hidden transition-all duration-300 ease-in-out ${isOpen ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0'}`}>
                    {/* Tree Line Container */}
                    <ul className="relative ml-5 pl-4 border-l border-gray-100 my-1 space-y-1">
                        {item.children.map((child: any) => (
                            <CategoryItem key={child.id} item={child} currentSlug={currentSlug} />
                        ))}
                    </ul>
                </div>
            )}
        </li>
    );
};

// --- SIDEBAR CONTENT COMPONENT ---
function SidebarContent({
    clearFilters,
    searchTerm,
    setSearchTerm,
    categories,
    slug,
    brands,
    selectedBrand,
    setSelectedBrand,
    minPrice,
    setMinPrice,
    maxPrice,
    setMaxPrice
}: any) {
    return (
        <div className="space-y-10">
            {/* Clear Filters Button */}
            <button onClick={clearFilters} className="flex items-center justify-center gap-2 w-full py-3 border border-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-red-50 hover:border-red-200 hover:text-red-500 transition">
                <RotateCcw size={14} /> Clear All Filters
            </button>

            {/* Quick Search */}
            <div>
                <h4 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-4">Quick Search</h4>
                <div className="relative">
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="What are you looking for?"
                        className="w-full bg-white border border-gray-200 rounded-xl py-3 pl-4 pr-10 text-sm outline-none focus:border-purple-500 shadow-sm"
                    />
                    <Search className="w-4 h-4 text-purple-600 absolute right-4 top-3.5" />
                </div>
            </div>

            {/* Shop By Category link (Dynamic Recursive Tree) */}
            <div>
                <h4 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-4">Shop By Category</h4>
                <ul className="flex flex-col gap-1">
                    {/* All Products */}
                    <Link href="/category/all" scroll={false}>
                        <li className="flex items-center justify-between p-3 rounded-lg cursor-pointer hover:bg-gray-50 text-gray-600 transition-colors">
                            <span className="text-sm font-medium pl-2">All Products</span>
                            <div className="w-4 h-4 rounded-full border border-gray-300"></div>
                        </li>
                    </Link>

                    {categories.map((c: any) => (
                        <CategoryItem key={c.id} item={c} currentSlug={slug} />
                    ))}
                </ul>
            </div>

            {/* RELEVANT BRANDS (Dynamic from API) */}
            <div>
                <h4 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-4">Relevant Brands</h4>
                {brands.length > 0 ? (
                    <ul className="space-y-3 max-h-60 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        <li
                            className={`flex items-center gap-3 cursor-pointer group ${selectedBrand === '' ? 'text-purple-600' : 'text-gray-500'}`}
                            onClick={() => setSelectedBrand('')}
                        >
                            <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${selectedBrand === '' ? 'border-purple-600' : 'border-gray-300'}`}>
                                {selectedBrand === '' && <div className="w-2 h-2 rounded-full bg-purple-600"></div>}
                            </div>
                            <span className="text-sm font-bold">All Brands</span>
                        </li>
                        {brands.map((b: any) => (
                            <li
                                key={b.id}
                                className={`flex items-center gap-3 cursor-pointer group ${selectedBrand === b.slug ? 'text-purple-600' : 'text-gray-500'}`}
                                onClick={() => setSelectedBrand(b.slug)}
                            >
                                <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${selectedBrand === b.slug ? 'border-purple-600' : 'border-gray-300'}`}>
                                    {selectedBrand === b.slug && <div className="w-2 h-2 rounded-full bg-purple-600"></div>}
                                </div>
                                <span className="text-sm font-bold">{b.name} <span className="text-xs opacity-50 ml-1">({b.count})</span></span>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-xs text-gray-400 italic">No brands found.</p>
                )}
            </div>

            {/* Price Range Slider */}
            <div>
                <h4 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-4">Price Range</h4>
                <div className="flex justify-between text-xs font-bold text-gray-900 mb-2">
                    <span>${minPrice}</span>
                    <span>${maxPrice}+</span>
                </div>
                <input
                    type="range"
                    min="0" max="15000"
                    value={maxPrice}
                    onChange={(e) => setMaxPrice(parseInt(e.target.value))}
                    className="w-full accent-purple-600 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <div className="flex justify-between mt-4">
                    <input type="number" value={minPrice} onChange={(e) => setMinPrice(parseInt(e.target.value))} className="w-20 p-2 border rounded text-xs font-bold text-center" />
                    <span className="text-gray-400">-</span>
                    <input type="number" value={maxPrice} onChange={(e) => setMaxPrice(parseInt(e.target.value))} className="w-20 p-2 border rounded text-xs font-bold text-center" />
                </div>
            </div>
        </div>
    );
}

// NO LOCAL SHOP_GRID_STYLES - We will rely on WpProductCard's global styles to avoid conflicts

// Global cache to prevent sidebar flickering on navigation
let globalCategoriesCache: any[] = [];

// MAIN PAGE
export default function CategoryPage() {
    const params = useParams();
    const router = useRouter();
    const slug = params?.slug as string;

    // States
    const [products, setProducts] = useState<any[]>([]);
    const [productsLoading, setProductsLoading] = useState(true);
    const [brands, setBrands] = useState<any[]>([]);
    const [categories, setCategories] = useState<any[]>(globalCategoriesCache); // Cache Init
    const [user, setUser] = useState<any>(null);
    const timeoutRef = useRef<any>(null);

    // Filters
    const [selectedBrand, setSelectedBrand] = useState('');
    const [minPrice, setMinPrice] = useState(0);
    const [maxPrice, setMaxPrice] = useState(5000);
    const [searchTerm, setSearchTerm] = useState('');

    // Pagination
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [refreshTrigger, setRefreshTrigger] = useState(0);
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    useEffect(() => {
        const body = document.body;
        if (isSidebarOpen) {
            body.style.overflow = 'hidden';
            body.style.paddingRight = 'var(--removed-body-scroll-bar-size)'; // Handle scrollbar shift if next-themes or similar is used
        } else {
            body.style.overflow = '';
            body.style.paddingRight = '';
        }
        return () => {
            body.style.overflow = '';
            body.style.paddingRight = '';
        };
    }, [isSidebarOpen]);

    useEffect(() => {
        const stored = localStorage.getItem('user');
        if (stored) setUser(JSON.parse(stored));

        // Fetch Categories (Menu)
        async function fetchCategories() {
            try {
                const res = await fetch('/api/wp/wcs/v1/app-data');
                const data = await res.json();
                if (data.menu) {
                    setCategories(data.menu);
                    globalCategoriesCache = data.menu; // Update Cache
                }
            } catch (err) { console.error(err); }
        }

        if (globalCategoriesCache.length === 0) {
            fetchCategories();
        } else {
            fetchCategories(); // Optional: Refresh quietly
        }
    }, []);

    // 1. Fetch Brands when Category Mounts
    useEffect(() => {
        async function fetchBrands() {
            try {
                const res = await fetch(`/api/wp/wcs/v1/brands?category=${slug}`);
                const data = await res.json();
                if (data.brands) setBrands(data.brands);
            } catch (err) { console.error(err); }
        }
        if (slug) fetchBrands();
    }, [slug]);

    // Reset Page on Filter Change
    useEffect(() => {
        setPage(1);
    }, [slug, selectedBrand, searchTerm]);

    // 2. Fetch Products with ROBUST RETRY
    useEffect(() => {
        let isMounted = true;
        const fetchProducts = async (retryCount = 0) => {
            setProductsLoading(true);
            try {
                // Construct Query API URL
                const query = new URLSearchParams({
                    category: slug || '',
                    brand: selectedBrand || '',
                    min_price: minPrice.toString(),
                    max_price: maxPrice.toString(),
                    search: searchTerm || '',
                    page: page.toString()
                });

                const res = await fetch(`/api/wp/wcs/v1/products?${query.toString()}`);

                if (!res.ok) throw new Error('API Failed');

                const data = await res.json();

                if (isMounted) {
                    setProducts(data.products || []);
                    setTotalPages(data.pages || 1);
                }
            } catch (err) {
                console.error("Fetch Error:", err);
                // Retry Logic (up to 3 times)
                if (retryCount < 3 && isMounted) {
                    console.log(`Retrying... (${retryCount + 1})`);
                    setTimeout(() => fetchProducts(retryCount + 1), 2000); // Wait 2s then retry
                }
            } finally {
                if (isMounted) setProductsLoading(false);
            }
        };

        // Debounce
        if (timeoutRef.current) clearTimeout(timeoutRef.current);
        timeoutRef.current = setTimeout(() => fetchProducts(0), 500); // 500ms debounce

        return () => {
            isMounted = false;
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, [slug, selectedBrand, minPrice, maxPrice, searchTerm, page, refreshTrigger]);

    const triggerRefresh = () => setRefreshTrigger(prev => prev + 1);

    const clearFilters = () => {
        setSelectedBrand('');
        setMinPrice(0);
        setMaxPrice(5000);
        setSearchTerm('');
        setPage(1);
    };

    return (
        <main className="min-h-screen bg-white font-sans text-gray-800">
            <Navbar />

            {/* Mobile Filter Toggle */}
            <div className="lg:hidden sticky top-[72px] z-30 bg-white border-b border-gray-100 px-4 py-3 flex items-center justify-between">
                <button
                    onClick={() => setIsSidebarOpen(true)}
                    className="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-full text-[11px] font-bold uppercase tracking-wider shadow-lg"
                >
                    <Filter size={14} /> Filters
                </button>
                <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    {productsLoading ? '...' : `${products.length} Items`}
                </span>
            </div>

            <div className="container mx-auto px-4 py-6 md:py-10 flex flex-col lg:flex-row gap-8 md:gap-12">

                {/* --- SIDEBAR (Desktop) --- */}
                <div className="hidden lg:block w-72 flex-shrink-0 space-y-10">
                    <SidebarContent
                        clearFilters={clearFilters}
                        searchTerm={searchTerm}
                        setSearchTerm={setSearchTerm}
                        categories={categories}
                        slug={slug}
                        brands={brands}
                        selectedBrand={selectedBrand}
                        setSelectedBrand={setSelectedBrand}
                        minPrice={minPrice}
                        setMinPrice={setMinPrice}
                        maxPrice={maxPrice}
                        setMaxPrice={setMaxPrice}
                    />
                </div>

                {/* --- SIDEBAR (Mobile Drawer) --- */}
                {isSidebarOpen && (
                    <div className="fixed inset-0 z-[100] lg:hidden">
                        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setIsSidebarOpen(false)}></div>
                        <div className="fixed inset-y-0 left-0 w-[85%] max-w-xs bg-white shadow-2xl flex flex-col animate-in slide-in-from-left duration-300">
                            <div className="flex items-center justify-between p-6 border-b">
                                <h3 className="font-black text-lg uppercase tracking-tight">Filters</h3>
                                <button onClick={() => setIsSidebarOpen(false)} className="p-2 hover:bg-gray-100 rounded-full transition">
                                    <X size={20} />
                                </button>
                            </div>
                            <div className="flex-1 overflow-y-auto p-6">
                                <SidebarContent
                                    clearFilters={clearFilters}
                                    searchTerm={searchTerm}
                                    setSearchTerm={setSearchTerm}
                                    categories={categories}
                                    slug={slug}
                                    brands={brands}
                                    selectedBrand={selectedBrand}
                                    setSelectedBrand={setSelectedBrand}
                                    minPrice={minPrice}
                                    setMinPrice={setMinPrice}
                                    maxPrice={maxPrice}
                                    setMaxPrice={setMaxPrice}
                                />
                            </div>
                            <div className="p-6 border-t bg-gray-50">
                                <button
                                    onClick={() => setIsSidebarOpen(false)}
                                    className="w-full py-4 bg-[#8b00ff] text-white rounded-xl font-black uppercase tracking-wider shadow-lg shadow-purple-200"
                                >
                                    Show {products.length} Products
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* --- MAIN CONTENT --- */}
                <div className="flex-1">

                    {/* Section Header (Matches Home) */}
                    <div className="wcs-section-header mb-10 pt-4">
                        <h2 className="text-2xl md:text-4xl font-black text-[#111] text-center uppercase tracking-tight">
                            {slug === 'all' ? 'All Products' : slug.replace('-', ' ')}
                        </h2>
                        <div style={{ width: '50px', height: '5px', background: '#A101F6', margin: '15px auto 0', borderRadius: '10px' }}></div>
                    </div>

                    {/* Header Bar (Controls) */}
                    <div className="mb-8 flex flex-col sm:flex-row justify-between items-center bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                        <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 sm:mb-0">
                            {productsLoading ? 'Loading Inventory...' : `${products.length} Premium Items Available`}
                        </span>
                        {/* Sort */}
                        <div className="flex items-center gap-2">
                            <span className="text-[10px] font-bold text-gray-400 uppercase">Sort:</span>
                            <select className="text-[11px] font-black text-gray-900 bg-transparent outline-none cursor-pointer hover:text-purple-600 uppercase tracking-wider">
                                <option>Newest First</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                            </select>
                        </div>
                    </div>

                    {/* Grid */}
                    <div className="wcs-rounded-grid-wrap" style={{ padding: '0px' }}>
                        {productsLoading ? (
                            <div className="wcs-rounded-grid">
                                {[1, 2, 3, 4, 5, 6, 7, 8].map(i => <div key={i} className="aspect-[1.15/1] bg-gray-50 rounded-3xl animate-pulse"></div>)}
                            </div>
                        ) : (
                            <>
                                <div className="wcs-rounded-grid min-h-[500px]">
                                    {products.length > 0 ? (
                                        products.map(p => <WpProductCard key={p.id} p={p} user={user} />)
                                    ) : (
                                        <div className="col-span-full py-20 flex flex-col items-center justify-center text-center bg-white rounded-3xl border border-dashed border-gray-200 h-full">
                                            <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                <Search className="text-gray-300" />
                                            </div>
                                            <h3 className="font-bold text-gray-900 mb-1">No products found</h3>
                                            <p className="text-sm text-gray-500">Try changing filters or category.</p>
                                            <div className="flex flex-col gap-3 mt-4 items-center">
                                                <button onClick={clearFilters} className="text-purple-600 font-bold text-sm underline">Clear All Filters</button>
                                                <button onClick={triggerRefresh} className="px-6 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-black transition shadow-lg">Reload Data</button>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Pagination Controls */}
                                {totalPages > 1 && (
                                    <div className="mt-12 flex justify-center items-center gap-4">
                                        <button
                                            onClick={() => setPage(p => Math.max(1, p - 1))}
                                            disabled={page === 1}
                                            className="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-purple-600 hover:text-white hover:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <ChevronLeft size={18} />
                                        </button>

                                        <div className="flex items-center gap-2">
                                            {Array.from({ length: totalPages }, (_, i) => i + 1).map(p => (
                                                <button
                                                    key={p}
                                                    onClick={() => setPage(p)}
                                                    className={`w-10 h-10 rounded-full font-bold text-xs transition-all ${page === p
                                                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-200'
                                                        : 'bg-white text-gray-600 border border-gray-200 hover:border-purple-600'
                                                        }`}
                                                >
                                                    {p}
                                                </button>
                                            ))}
                                        </div>

                                        <button
                                            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                                            disabled={page === totalPages}
                                            className="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-purple-600 hover:text-white hover:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <ChevronRight size={18} />
                                        </button>
                                    </div>
                                )}
                            </>
                        )}
                    </div>

                </div>
            </div>
        </main>
    );
}
