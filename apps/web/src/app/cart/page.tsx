'use client';

import Navbar from '@/components/Navbar';
import { useCart } from '@/context/CartContext';
import Link from 'next/link';
import React from 'react';
import { Trash2, ChevronRight, ShoppingCart } from 'lucide-react';

export default function CartPage() {
    const { cart, removeFromCart, updateQuantity, cartTotal, clearCart, loading } = useCart();
    const [user, setUser] = React.useState<any>(null);
    const [freeShippingThreshold, setFreeShippingThreshold] = React.useState(999); // Default fallback

    React.useEffect(() => {
        // Load User
        const storedUser = localStorage.getItem('user');
        if (storedUser) setUser(JSON.parse(storedUser));

        // Fetch Settings for Dynamic Threshold
        fetch('/api/wp/wcs/v1/app-data')
            .then(res => res.json())
            .then(data => {
                if (data.free_shipping_threshold) {
                    setFreeShippingThreshold(parseFloat(data.free_shipping_threshold));
                }
            })
            .catch(err => console.error('Failed to load settings', err));
    }, []);

    // Free Shipping Logic
    const remainingForFreeShip = freeShippingThreshold - cartTotal;
    const freeShipPercent = Math.min(100, (cartTotal / freeShippingThreshold) * 100);

    if (cart.length === 0) {
        // ... (Empty State - Keep exact same as before)
        return (
            <div className="min-h-screen bg-[#FDFDFD]">
                <Navbar />
                <div className="container mx-auto px-6 py-20 text-center flex flex-col items-center">
                    <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mb-6">
                        <ShoppingCart size={40} />
                    </div>
                    <h1 className="text-3xl font-black text-gray-900 mb-4">Your Cart is Empty</h1>
                    <p className="text-gray-500 mb-8 max-w-md">Looks like you haven't added anything to your cart yet. Browse our products and find something you love!</p>
                    <Link href="/" className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-10 rounded-xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-transform active:scale-95">
                        Start Shopping
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <main className="min-h-screen bg-[#FDFDFD] pb-20 font-sans">
            <Navbar />

            {/* Breadcrumb */}
            <div className="bg-gray-50 border-b border-gray-100 mb-12">
                <div className="container mx-auto px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                    <Link href="/" className="hover:text-purple-600">Home</Link>
                    <ChevronRight size={12} />
                    <span className="text-gray-900">Cart</span>
                </div>
            </div>

            <div className="container mx-auto px-6">
                <h1 className="text-4xl font-black text-gray-900 mb-12 uppercase tracking-tight">Your Cart</h1>

                <div className={`flex flex-col lg:flex-row gap-12 transition-opacity ${loading ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>

                    {/* Cart Items List */}
                    <div className="flex-1">
                        <div className="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
                            <div className="hidden md:grid grid-cols-12 bg-gray-50 border-b border-gray-100 py-4 px-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                <div className="col-span-6">Product</div>
                                <div className="col-span-2 text-center">Price</div>
                                <div className="col-span-2 text-center">Quantity</div>
                                <div className="col-span-2 text-right">Total</div>
                            </div>

                            <div className="divide-y divide-gray-50">
                                {cart.map(item => (
                                    <div key={item.id} className="grid grid-cols-1 md:grid-cols-12 items-center p-6 gap-6 md:gap-0 group hover:bg-gray-50/50 transition">

                                        {/* Product Info */}
                                        <div className="col-span-6 flex items-center gap-4">
                                            <div className="w-20 h-20 bg-white border border-gray-200 rounded-xl p-2 flex-shrink-0">
                                                <img src={item.image} alt={item.name} className="w-full h-full object-contain" />
                                            </div>
                                            <div className="flex flex-col">
                                                <span className="font-bold text-gray-900 leading-tight mb-1">{item.name}</span>
                                                {item.variation_data && (
                                                    <span className="text-xs text-gray-500 mb-2">{item.variation_data}</span>
                                                )}
                                                <button
                                                    onClick={() => removeFromCart(item.id)}
                                                    className="text-[10px] text-red-400 font-bold uppercase tracking-wider hover:text-red-600 flex items-center gap-1 w-fit"
                                                >
                                                    <Trash2 size={12} /> Remove
                                                </button>
                                            </div>
                                        </div>

                                        {/* Price */}
                                        <div className="hidden md:block col-span-2 text-center">
                                            <span className="font-bold text-gray-700">${item.price.toFixed(2)}</span>
                                        </div>

                                        {/* Quantity */}
                                        <div className="col-span-1 md:col-span-2 flex justify-center">
                                            <div className="flex items-center border border-gray-200 rounded-lg h-9 w-24 bg-white shadow-sm">
                                                <button
                                                    onClick={() => updateQuantity(item.id, Math.max(1, item.quantity - 1))}
                                                    className="w-8 h-full flex items-center justify-center text-gray-400 hover:text-purple-600 font-bold transition"
                                                >-</button>
                                                <span className="flex-1 text-center font-bold text-gray-900 text-sm">{item.quantity}</span>
                                                <button
                                                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                    className="w-8 h-full flex items-center justify-center text-gray-400 hover:text-purple-600 font-bold transition"
                                                >+</button>
                                            </div>
                                        </div>

                                        {/* Total / Mobile Price */}
                                        <div className="col-span-1 md:col-span-2 flex items-center justify-between md:justify-end">
                                            <span className="md:hidden font-bold text-gray-400 text-xs uppercase tracking-wide">Total:</span>
                                            <span className="font-black text-purple-600 text-lg">${(item.price * item.quantity).toFixed(2)}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-between items-center">
                            <Link href="/shop" className="text-xs font-bold text-gray-500 hover:text-purple-600 uppercase tracking-wider flex items-center gap-2">
                                <ChevronRight size={14} className="rotate-180" /> Continue Shopping
                            </Link>
                            <button
                                onClick={clearCart}
                                className="text-xs font-bold text-red-400 hover:text-red-600 uppercase tracking-wider"
                            >
                                Clear Cart
                            </button>
                        </div>
                    </div>

                    {/* Cart Totals */}
                    <div className="w-full lg:w-96 flex-shrink-0">
                        <div className="bg-purple-50 rounded-3xl p-8 border border-purple-100 sticky top-24">
                            <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight mb-6">Order Summary</h2>

                            {/* DYNAMIC FREE SHIPPING PROGRESS BAR */}
                            {freeShippingThreshold > 0 && (
                                <div className="mb-6 bg-white p-4 rounded-xl border border-purple-100 shadow-sm">
                                    {remainingForFreeShip > 0 ? (
                                        <>
                                            <p className="text-xs font-bold text-gray-600 mb-2">
                                                Add <span className="text-purple-600">${remainingForFreeShip.toFixed(2)}</span> more to support Free Shipping!
                                            </p>
                                            <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                                <div
                                                    className="h-full bg-gradient-to-r from-purple-500 to-purple-600 transition-all duration-500"
                                                    style={{ width: `${freeShipPercent}%` }}
                                                ></div>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="flex items-center gap-2 text-green-600">
                                            <div className="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-[10px]">✓</div>
                                            <p className="text-xs font-black uppercase tracking-wide">You are eligible for Free Shipping!</p>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="space-y-4 mb-8">
                                <div className="flex justify-between items-center text-sm">
                                    <span className="font-bold text-gray-500">Subtotal</span>
                                    <span className="font-black text-gray-900">${cartTotal.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="font-bold text-gray-500">Shipping</span>
                                    <span className="font-bold text-gray-400">Calculated at checkout</span>
                                </div>
                                <div className="h-px bg-purple-200 my-4"></div>
                                <div className="flex justify-between items-center">
                                    <span className="font-black text-gray-900 uppercase tracking-wider">Total</span>
                                    <span className="text-2xl font-black text-purple-600">${cartTotal.toFixed(2)}</span>
                                </div>
                            </div>

                            {user ? (
                                <Link
                                    href="/checkout"
                                    className="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-transform active:scale-95 mb-4"
                                >
                                    Proceed to Checkout
                                </Link>
                            ) : (
                                <div className="text-center">
                                    <Link href="/login" className="w-full block bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl uppercase tracking-wider shadow-lg mb-3">
                                        Login to Checkout
                                    </Link>
                                    <p className="text-xs text-gray-500">You must be logged in to complete your purchase.</p>
                                </div>
                            )}

                            <div className="mt-6 flex items-center justify-center gap-2 opacity-50">
                                <span className="text-[10px] font-bold text-gray-400 uppercase">Secure Checkout</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    );
}
