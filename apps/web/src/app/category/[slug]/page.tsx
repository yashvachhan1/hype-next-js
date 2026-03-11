'use client';

import { useState, useEffect, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { Search, ShoppingCart, User, Heart, ChevronDown, RotateCcw, ChevronLeft, ChevronRight } from 'lucide-react';
import Navbar from '@/components/Navbar';

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

// PRODUCT CARD
function ProductCard({ p, user }: { p: any, user: any }) {
    const imageSrc = p.image || 'https://placehold.co/400x400/png?text=No+Image';

    return (
        <div className="bg-white rounded-3xl p-5 shadow-[0_5px_30px_-5px_rgba(0,0,0,0.05)] hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col items-center text-center relative group h-full">
            {/* Heart Icon */}
            <button className="absolute top-4 right-4 text-gray-300 hover:text-red-500 transition z-10">
                <Heart size={20} fill="currentColor" className="opacity-20 hover:opacity-100" />
            </button>

            {/* Image */}
            <div className="w-full h-48 mb-4 relative flex items-center justify-center p-2">
                <img
                    src={imageSrc}
                    className="max-h-full max-w-full object-contain group-hover:scale-110 transition duration-500"
                    onError={(e) => { (e.target as HTMLImageElement).src = 'https://placehold.co/400x400/png?text=No+Image'; }}
                />
            </div>

            {/* Content */}
            <div className="flex-1 w-full flex flex-col items-center">
                {/* Tag */}
                <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Premium Item</span>

                <h3 className="font-bold text-gray-900 text-sm leading-snug mb-3 line-clamp-2 px-2 min-h-[40px]">{p.name}</h3>

                {/* Price / Login Logic */}
                <div className="mt-auto w-full mb-4">
                    {user ? (
                        <div className="flex flex-col items-center">
                            {(p.raw_price || p.regular_price) ? (
                                <div className="flex items-center gap-2">
                                    <span className="text-xl font-black text-gray-900">${Number(p.raw_price || p.regular_price).toFixed(2)}</span>
                                    {p.regular_price && (p.raw_price || p.regular_price) < p.regular_price && (
                                        <span className="text-xs text-gray-400 line-through font-bold">${Number(p.regular_price).toFixed(2)}</span>
                                    )}
                                </div>
                            ) : (
                                <span className="text-xl font-black text-gray-900" dangerouslySetInnerHTML={{ __html: p.price }}></span>
                            )}
                        </div>
                    ) : (
                        <div className="flex flex-col gap-0.5">
                            <span className="text-[11px] text-gray-400 italic font-medium">Please <Link href="/login" className="text-red-500 font-bold hover:underline">log in</Link> to view the price.</span>
                        </div>
                    )}
                </div>

                {/* View Details Button */}
                <Link href={`/product/${p.slug}`} className="w-full bg-[#aa00ff] hover:bg-[#8e00d6] text-white font-bold text-xs py-3.5 rounded-xl uppercase tracking-wider transition-all shadow-lg shadow-purple-200 block text-center">
                    View Details
                </Link>
            </div>
        </div>
    )
}

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
        <main className="min-h-screen bg-[#FDFDFD] font-sans text-gray-800">
            <Navbar />

            <div className="container mx-auto px-6 py-10 flex flex-col lg:flex-row gap-12">

                {/* --- SIDEBAR --- */}
                <div className="w-full lg:w-72 flex-shrink-0 space-y-10">

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
                            <Link href="/" scroll={false}>
                                <li className="flex items-center justify-between p-3 rounded-lg cursor-pointer hover:bg-gray-50 text-gray-600 transition-colors">
                                    <span className="text-sm font-medium pl-2">All Products</span>
                                    <div className="w-4 h-4 rounded-full border border-gray-300"></div>
                                </li>
                            </Link>

                            {categories.map((c) => (
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
                                {brands.map((b) => (
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
                            min="0" max="5000"
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

                {/* --- MAIN CONTENT --- */}
                <div className="flex-1">
                    {/* Header Bar */}
                    <div className="mb-8 flex flex-col sm:flex-row justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <span className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 sm:mb-0">
                            {productsLoading ? 'Searching...' : `Showing ${products.length} Premium Items`}
                        </span>
                        {/* Sort */}
                        <div className="flex items-center gap-2">
                            <span className="text-[10px] font-bold text-gray-400 uppercase">Sort By:</span>
                            <select className="text-xs font-bold text-gray-900 bg-transparent outline-none cursor-pointer hover:text-purple-600">
                                <option>Default Sorting</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                            </select>
                        </div>
                    </div>

                    {/* Grid */}
                    {productsLoading ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {[1, 2, 3, 4, 5, 6, 7, 8].map(i => <div key={i} className="h-[400px] bg-gray-100 rounded-3xl animate-pulse"></div>)}
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 min-h-[500px]">
                                {products.length > 0 ? (
                                    products.map(p => <ProductCard key={p.id} p={p} user={user} />)
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
        </main>
    )
}
