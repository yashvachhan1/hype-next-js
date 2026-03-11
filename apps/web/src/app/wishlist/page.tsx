'use client';

import { useState, useEffect } from 'react';
import Navbar from '@/components/Navbar';
import Link from 'next/link';
import { Heart, ShoppingCart, Trash2 } from 'lucide-react';
import { useCart } from '@/context/CartContext';

export default function WishlistPage() {
    const { addToCart } = useCart();
    const [wishlist, setWishlist] = useState<any[]>([]);

    useEffect(() => {
        // Load Wishlist from LocalStorage (Simple Client-Side Logic for now)
        const stored = localStorage.getItem('wishlist');
        if (stored) setWishlist(JSON.parse(stored));
    }, []);

    const removeFromWishlist = (id: number) => {
        const updated = wishlist.filter(item => item.id !== id);
        setWishlist(updated);
        localStorage.setItem('wishlist', JSON.stringify(updated));
    };

    const moveToCart = (item: any) => {
        addToCart(item); // Add to cart
        removeFromWishlist(item.id); // Remove from wishlist
    };

    return (
        <main className="min-h-screen bg-[#FDFDFD] font-sans">
            <Navbar />

            <div className="container mx-auto px-6 py-12">
                <div className="flex items-center gap-3 mb-8">
                    <Heart className="w-8 h-8 text-purple-600 fill-purple-600" />
                    <h1 className="text-3xl font-black text-gray-900 uppercase tracking-tight">My Wishlist</h1>
                    <span className="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded-full">{wishlist.length} Items</span>
                </div>

                {wishlist.length > 0 ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {wishlist.map((item) => (
                            <div key={item.id} className="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm hover:shadow-lg transition relative group">
                                <button
                                    onClick={() => removeFromWishlist(item.id)}
                                    className="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition z-10"
                                    title="Remove"
                                >
                                    <Trash2 size={18} />
                                </button>

                                <div className="h-48 flex items-center justify-center p-4 bg-gray-50 rounded-xl mb-4">
                                    <img src={item.image} className="max-h-full max-w-full object-contain" alt={item.name} />
                                </div>

                                <h3 className="font-bold text-gray-900 text-sm line-clamp-2 mb-2 h-10">{item.name}</h3>

                                <div className="flex items-center justify-between mt-auto pt-3 border-t border-gray-50">
                                    <span className="font-black text-purple-700" dangerouslySetInnerHTML={{ __html: item.price_html }}></span>
                                    <button
                                        onClick={() => moveToCart(item)}
                                        className="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center hover:bg-purple-600 transition shadow-lg"
                                        title="Move to Cart"
                                    >
                                        <ShoppingCart size={16} />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-32 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                        <Heart className="mx-auto text-gray-300 w-16 h-16 mb-4" />
                        <h2 className="text-xl font-bold text-gray-900 mb-2">Your wishlist is empty</h2>
                        <p className="text-gray-500 mb-6">Save items you love here for later.</p>
                        <Link href="/" className="inline-block px-8 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition shadow-lg shadow-purple-200">
                            Start Shopping
                        </Link>
                    </div>
                )}
            </div>
        </main>
    );
}
