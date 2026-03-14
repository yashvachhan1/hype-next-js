'use client';

import { useState, useEffect } from 'react';
import Navbar from '@/components/Navbar';
import Link from 'next/link';
import { Heart, ShoppingBag, Trash2, Loader2 } from 'lucide-react';
import { useCart } from '@/context/CartContext';
import { useWishlist } from '@/context/WishlistContext';

export default function WishlistPage() {
    const { addToCart } = useCart();
    const { wishlist, toggleWishlist } = useWishlist();
    const [products, setProducts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const loadProducts = async () => {
            if (wishlist.length === 0) {
                setProducts([]);
                setLoading(false);
                return;
            }

            try {
                setLoading(true);
                const ids = wishlist.join(',');
                const res = await fetch(`/api/wp/wcs/v1/products?include=${ids}`);
                const data = await res.json();
                setProducts(Array.isArray(data.products) ? data.products : []);
            } catch (err) {
                console.error('Error fetching wishlist products:', err);
                setProducts([]);
            } finally {
                setLoading(false);
            }
        };

        loadProducts();
    }, [wishlist]);

    const handleAddToCart = (p: any) => {
        addToCart([{
            id: p.id,
            product_id: p.id,
            name: p.name,
            price: Number(p.wholesale_price || p.price || 0),
            quantity: 1,
            image: p.image
        }]);
    };

    return (
        <main className="min-h-screen bg-[#FDFDFD] font-sans">
            <Navbar />

            <div className="container mx-auto px-6 py-12">
                <div className="flex items-center gap-3 mb-8">
                    <Heart className="w-8 h-8 text-purple-600 fill-purple-600" />
                    <h1 className="text-3xl font-black text-gray-900 uppercase tracking-tight">My Wishlist</h1>
                    {!loading && <span className="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded-full">{wishlist.length} Items</span>}
                </div>

                {loading ? (
                    <div className="flex flex-col items-center justify-center py-32">
                        <Loader2 className="w-12 h-12 text-purple-600 animate-spin mb-4" />
                        <p className="text-gray-500 font-bold">Loading your favorites...</p>
                    </div>
                ) : products.length > 0 ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {products.map((item) => (
                            <div key={item.id} className="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm hover:shadow-lg transition relative group">
                                <button
                                    onClick={() => toggleWishlist(item.id)}
                                    className="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition z-10 bg-white/80 p-1.5 rounded-full shadow-sm"
                                    title="Remove"
                                >
                                    <Trash2 size={18} />
                                </button>

                                <Link href={`/product/${item.slug}`} className="h-48 flex items-center justify-center p-4 bg-gray-50 rounded-xl mb-4 group-hover:bg-purple-50/30 transition">
                                    <img src={item.image} className="max-h-full max-w-full object-contain" alt={item.name} />
                                </Link>

                                <h3 className="font-bold text-gray-900 text-sm line-clamp-2 mb-2 h-10">
                                    <Link href={`/product/${item.slug}`} className="hover:text-purple-600 transition">{item.name}</Link>
                                </h3>

                                <div className="flex items-center justify-between mt-auto pt-3 border-t border-gray-50">
                                    <div className="flex flex-col">
                                        <span className="font-black text-purple-700 text-lg">
                                            ${Number(item.wholesale_price || item.price || 0).toFixed(2)}
                                        </span>
                                    </div>
                                    <button
                                        onClick={() => handleAddToCart(item)}
                                        className="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center hover:bg-purple-600 transition shadow-lg"
                                        title="Add to Cart"
                                    >
                                        <ShoppingBag size={18} />
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
