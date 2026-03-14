'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { Search, ShoppingCart, User, Menu, Heart, Phone, MessageCircle, ChevronDown } from 'lucide-react';
import { useCart } from '@/context/CartContext';
import CartSidebar from '@/components/CartSidebar';

import { fetchMenu } from '@/api/menu';
import { fetchSettings } from '@/api/settings';
import { fetchCart } from '@/api/cart';



const MAIN_MENU_ITEMS = [
    'Devices',
    'E-Juices',
    'Coils / Pods',
    'Disposables',
    'Hemp',
    'Nicotine Pouches',
    'Smokeshop',
    'Vape Deals',
    'Kratom/ Mashroom',
    'Brands'
];

export default function Navbar() {
    const [menu, setMenu] = useState<any[]>([]);
    const [settings, setSettings] = useState<any>(null); // For Logo, Wholesale Rules
    const [results, setResults] = useState<any[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [user, setUser] = useState<any>(null);
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isCartOpen, setIsCartOpen] = useState(false);
    const { cartCount } = useCart();

    // Debounced Search Handler
    useEffect(() => {
        const timeoutId = setTimeout(async () => {
            if (searchQuery.length < 2) {
                setResults([]);
                setIsSearching(false);
                return;
            }

            setIsSearching(true);
            try {
                const res = await fetch(`/api/wp/wcs/v1/search?q=${searchQuery}`);
                const data = await res.json();
                setResults(Array.isArray(data) ? data : []);
            } catch (err) {
                console.error(err);
                setResults([]);
            } finally {
                setIsSearching(false);
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const handleSearch = (q: string) => {
        setSearchQuery(q);
    };

    // STANDARD FETCH: Parallel (Modular API)
    useEffect(() => {
        const stored = localStorage.getItem('user');
        if (stored) setUser(JSON.parse(stored));

        // 1. Menu
        fetchMenu().then(data => {
            if (Array.isArray(data)) setMenu(data);
        });

        // 2. Settings
        fetchSettings().then(data => {
            setSettings(data);
            if (data?.wholesale_rules) localStorage.setItem('wholesale_rules', JSON.stringify(data.wholesale_rules));
        });

        // 3. Cart
        fetchCart().then(data => {
            // no-op or context update 
        });

    }, []);

    // Helper to find matching dynamic category
    const getDynamicData = (name: string) => {
        if (!menu || menu.length === 0) return null;

        // Normalize name for comparison (remove spaces, special chars, lowercase)
        const normalize = (s: string) => s.toLowerCase().replace(/[^a-z0-9]/g, '');
        const target = normalize(name);

        // 1. Try Exact/Partial Match
        let match = menu.find(item => {
            const itemNorm = normalize(item.name);
            return itemNorm === target || itemNorm.includes(target) || target.includes(itemNorm);
        });

        // 2. Alias Fallback (If visual name differs from DB name)
        if (!match) {
            if (target.includes('coil')) match = menu.find(i => normalize(i.name).includes('coil'));
            if (target.includes('kratom')) match = menu.find(i => normalize(i.name).includes('kratom'));
            if (target.includes('mashroom') || target.includes('mushroom')) match = menu.find(i => normalize(i.name).includes('mushroom') || normalize(i.name).includes('mashroom'));
        }

        return match;
    };


    return (
        <div className="w-full font-sans bg-white sticky top-0 z-50 shadow-[0_2px_20px_rgba(0,0,0,0.04)]">
            {/* Top Strip */}
            <div className="bg-black text-white text-[10px] md:text-xs py-2.5 text-center font-bold tracking-widest uppercase">
                ⚠️ Warning: This product contains nicotine. Nicotine is an addictive chemical.
            </div>

            {/* Main Header */}
            <div className="container mx-auto px-4 py-3 flex items-center justify-between gap-4">

                {/* 1. Logo */}
                <Link href="/" className="flex-shrink-0">
                    <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png" alt="Hype" className="h-10 md:h-12 w-auto object-contain hover:opacity-80 transition" />
                </Link>

                {/* 2. Search Bar (Live) */}
                <div className="hidden lg:flex flex-1 max-w-xl relative mx-4">
                    <input
                        type="text"
                        placeholder="Search for products, flavors..."
                        className="w-full bg-white border border-gray-200 rounded-full py-2.5 px-6 pl-12 text-sm outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-sm transition-all text-gray-700 placeholder-gray-400"
                        value={searchQuery}
                        onChange={(e) => handleSearch(e.target.value)}
                        onFocus={() => searchQuery.length >= 2 && results.length > 0 && setResults(results)} // Re-show results if input is focused and query is valid
                        onBlur={() => setTimeout(() => setResults([]), 100)} // Hide results after a short delay to allow click on link
                        suppressHydrationWarning
                    />
                    <Search className="w-4 h-4 text-gray-400 absolute left-4 top-3" />

                    {/* Search Results Dropdown */}
                    {searchQuery.length >= 2 && results.length > 0 && (
                        <div className="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-[60]">
                            {results.map((p: any) => (
                                <Link key={p.id} href={`/product/${p.slug}`} className="flex items-center gap-4 p-3 hover:bg-purple-50 transition border-b border-gray-50 last:border-0" onClick={() => setResults([])}>
                                    <img src={p.image || '/placeholder.png'} className="w-12 h-12 object-contain rounded-md bg-white border border-gray-100" />
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-800 line-clamp-1">{p.name}</h4>
                                        <div className="text-xs font-bold text-purple-600">
                                            ${p.price}
                                            {p.price < p.regular_price && <span className="ml-2 text-red-500 line-through text-[10px]">${p.regular_price}</span>}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                    {isSearching && searchQuery.length >= 2 && (
                        <div className="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-[60] p-3 text-sm text-gray-500">
                            Searching...
                        </div>
                    )}
                    {!isSearching && searchQuery.length >= 2 && results.length === 0 && (
                        <div className="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-[60] p-3 text-sm text-gray-500">
                            No results found.
                        </div>
                    )}
                </div>

                {/* 3. Contact Info (Hidden on smaller screens) */}
                <div className="hidden xl:flex items-center gap-6 flex-shrink-0">

                    {/* WhatsApp */}
                    <a href="#" className="flex items-center gap-3 group">
                        <div className="w-9 h-9 bg-[#25D366] rounded-full flex items-center justify-center text-white shadow-sm group-hover:scale-110 transition">
                            <MessageCircle className="w-5 h-5 fill-current" />
                        </div>
                        <div className="flex flex-col leading-none">
                            <span className="text-[10px] font-black text-[#8b00ff] uppercase mb-0.5">Click Here To Join!</span>
                            <span className="text-[10px] font-bold text-gray-500">Our Whatsapp Community</span>
                        </div>
                    </a>

                    {/* Support */}
                    <a href="tel:18668189598" className="flex items-center gap-3 group">
                        <div className="w-9 h-9 bg-[#FF0000] rounded-full flex items-center justify-center text-white shadow-sm group-hover:scale-110 transition">
                            <Phone className="w-5 h-5 fill-current" />
                        </div>
                        <div className="flex flex-col leading-none">
                            <span className="text-[10px] font-black text-[#8b00ff] uppercase mb-0.5">Customer Support</span>
                            <span className="text-[10px] font-bold text-gray-500">1 (866) 818-9598</span>
                        </div>
                    </a>
                </div>

                {/* 4. Action Buttons (Hidden on small laptops) */}
                <div className="hidden 2xl:flex items-center gap-3 flex-shrink-0 mx-2">
                    <Link href="/category/new-arrivals" className="bg-[#8b00ff] text-white px-5 py-2 rounded-full text-[11px] font-black uppercase tracking-wider hover:bg-[#7000cc] shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                        New Arrivals
                    </Link>
                    <Link href="/category/best-sellers" className="bg-[#8b00ff] text-white px-5 py-2 rounded-full text-[11px] font-black uppercase tracking-wider hover:bg-[#7000cc] shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                        Best Sellers
                    </Link>
                </div>

                {/* 5. Icons */}
                <div className="flex items-center gap-5">

                    {/* User */}
                    <div className="relative group z-50">
                        <Link href={user ? "/account" : "/login"} className="flex items-center gap-1 text-[#8b00ff] hover:text-[#7000cc] transition">
                            <div className="p-1">
                                <User className="w-7 h-7" strokeWidth={2.5} />
                            </div>
                        </Link>

                        {/* Dropdown Menu (Logged In Only) */}
                        {user && (
                            <div className="absolute left-1/2 -translate-x-1/2 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top">
                                <div className="bg-white rounded-xl shadow-2xl border border-gray-100 w-48 overflow-hidden py-1">
                                    <div className="px-4 py-3 border-b border-gray-50 bg-gray-50">
                                        <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">My Account</p>
                                        <p className="font-bold text-gray-900 truncate">{user.email}</p>
                                    </div>
                                    <Link href="/account" className="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Dashboard</Link>
                                    <Link href="/account?tab=orders" className="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">My Orders</Link>
                                    <div className="border-t border-gray-50 mt-1">
                                        <button onClick={() => {
                                            localStorage.removeItem('user');
                                            localStorage.removeItem('wp_nonce');
                                            localStorage.removeItem('hype_cart');
                                            sessionStorage.clear();
                                            window.location.href = '/';
                                        }} className="w-full text-left px-4 py-3 text-sm font-bold text-red-500 hover:bg-red-50 transition">Sign Out</button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Cart */}
                    <div className="relative cursor-pointer hover:text-purple-600 transition group z-40" onClick={() => setIsCartOpen(true)}>
                        <ShoppingCart className="w-7 h-7 text-black group-hover:text-[#8b00ff]" strokeWidth={2} />
                        {cartCount > 0 && (
                            <span className="absolute -top-1 -right-1 bg-[#8b00ff] text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full font-bold shadow-md border-2 border-white">{cartCount}</span>
                        )}
                    </div>

                    {/* Mobile Menu Toggle (Visible on small screens) */}
                    <button className="lg:hidden text-gray-800 p-1 hover:bg-gray-100 rounded-lg transition" onClick={() => setIsMenuOpen(true)}>
                        <Menu className="w-7 h-7" />
                    </button>

                </div>
            </div>

            {/* Mobile Navigation Sidebar Drawer */}
            {isMenuOpen && (
                <div className="fixed inset-0 z-[100] lg:hidden">
                    {/* Overlay */}
                    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onClick={() => setIsMenuOpen(false)}></div>

                    {/* Sidebar Content */}
                    <div className="fixed inset-y-0 left-0 w-full max-w-xs bg-white shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out">
                        <div className="flex items-center justify-between p-4 border-b">
                            <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png" alt="Hype" className="h-8 w-auto" />
                            <button className="p-2 text-gray-400 hover:text-black" onClick={() => setIsMenuOpen(false)}>
                                <span className="text-2xl font-black">×</span>
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto px-4 py-6">
                            {/* Main Links */}
                            <div className="space-y-4 mb-8">
                                <Link href="/category/new-arrivals" className="block text-sm font-black uppercase text-purple-700 hover:bg-purple-50 p-2 rounded" onClick={() => setIsMenuOpen(false)}>New Arrivals</Link>
                                <Link href="/category/best-sellers" className="block text-sm font-black uppercase text-purple-700 hover:bg-purple-50 p-2 rounded" onClick={() => setIsMenuOpen(false)}>Best Sellers</Link>
                            </div>

                            {/* Category Menu */}
                            <div className="mb-4 text-xs font-black text-gray-400 uppercase tracking-widest border-b pb-2">Categories</div>
                            <div className="space-y-1">
                                {MAIN_MENU_ITEMS.map((name) => {
                                    const dynamicData = getDynamicData(name);
                                    const slug = dynamicData ? dynamicData.slug : name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                                    return (
                                        <div key={name} className="py-2">
                                            <Link
                                                href={`/category/${slug}`}
                                                className="flex items-center justify-between text-sm font-bold text-gray-800 hover:text-purple-600"
                                                onClick={() => setIsMenuOpen(false)}
                                            >
                                                {name}
                                            </Link>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Footer Info */}
                        <div className="p-4 bg-gray-50 border-t space-y-4">
                            <a href="tel:18668189598" className="flex items-center gap-3">
                                <Phone className="w-5 h-5 text-red-500" />
                                <span className="text-xs font-bold text-gray-600">Support: 1 (866) 818-9598</span>
                            </a>
                            {user && (
                                <button onClick={() => {
                                    localStorage.removeItem('user');
                                    window.location.href = '/';
                                }} className="w-full text-center py-2 text-xs font-black text-red-500 border border-red-200 rounded">Sign Out</button>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Mega Menu Bar - FIXED PARENTS, DYNAMIC CHILDREN */}
            <div className="hidden md:block relative bg-[#2d004b] text-white">
                <div className="container mx-auto px-6">
                    <div className="flex justify-center flex-wrap gap-8 text-[11px] font-extrabold tracking-[0.15em] uppercase">
                        {MAIN_MENU_ITEMS.map((name) => {
                            const dynamicData = getDynamicData(name);
                            // Fallback slug if dynamic data not found: just slugify the name
                            const slug = dynamicData ? dynamicData.slug : name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
                            const children = dynamicData?.children || [];

                            return (
                                <div key={name} className="group py-3">
                                    <Link href={`/category/${slug}`} className="hover:text-purple-300 transition-colors flex items-center gap-1.5 py-2 relative">
                                        <span className="relative">
                                            {name}
                                            <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
                                        </span>
                                        {children.length > 0 && <ChevronDown className="w-3 h-3 transition-transform group-hover:rotate-180" />}
                                    </Link>

                                    {/* Dropdown (Only show if we have children or found dynamic data) */}
                                    {children.length > 0 && (
                                        <div className="absolute left-0 top-full w-full bg-white text-gray-800 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[100] border-t-2 border-purple-600">
                                            <div className="container mx-auto">
                                                <div className="flex bg-white min-h-[300px]">

                                                    {/* 1. Categories Grid */}
                                                    <div className="flex-1 p-8 border-r border-gray-100">
                                                        <div className="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Select Category</div>
                                                        <div className="grid grid-cols-4 gap-x-8 gap-y-6">
                                                            {children.map((sub: any) => (
                                                                <div key={sub.id} className="flex flex-col gap-2 break-inside-avoid">
                                                                    {/* Level 2: Header / Main Link */}
                                                                    <Link href={`/category/${sub.slug}`} className={`font-bold text-gray-900 hover:text-purple-700 ${sub.children?.length ? 'text-sm uppercase tracking-wide border-b border-gray-100 pb-1 mb-1' : 'text-sm'}`}>
                                                                        {sub.name}
                                                                    </Link>

                                                                    {/* Level 3: Sub-Links (Grandchildren) */}
                                                                    {sub.children && sub.children.length > 0 && (
                                                                        <div className="flex flex-col gap-1.5 pl-0">
                                                                            {sub.children.map((child: any) => (
                                                                                <Link key={child.id} href={`/category/${child.slug}`} className="text-xs text-gray-500 hover:text-purple-600 transition-colors">
                                                                                    {child.name}
                                                                                </Link>
                                                                            ))}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>

                                                    {/* 2. Trending Section */}
                                                    <div className="w-72 bg-gray-50 p-6 flex flex-col justify-center">
                                                        <div className="mb-4 flex items-center gap-2">
                                                            <span className="text-xs font-black text-purple-700 uppercase tracking-wide">Trending</span>
                                                            <div className="h-px bg-purple-200 flex-1"></div>
                                                        </div>
                                                        <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition cursor-pointer">
                                                            <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/1st-5.jpg" className="w-full h-32 object-contain mb-3" />
                                                            <div className="text-center">
                                                                <h4 className="font-bold text-gray-900 text-sm mb-1">Geek Bar Pulse 15k</h4>
                                                                <span className="text-purple-600 font-extrabold">$14.99</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            <CartSidebar isOpen={isCartOpen} onClose={() => setIsCartOpen(false)} />
        </div >
    );
}
