'use client';

import { useState } from 'react';
import { useCart } from '@/context/CartContext';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Navbar from '@/components/Navbar';
import { ChevronRight } from 'lucide-react';

export default function CheckoutPage() {
    const { cart, cartTotal, clearCart } = useCart();
    const router = useRouter();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    // Form State
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        address_1: '',
        city: '',
        state: '',
        postcode: '',
        country: 'US'
    });

    // Shipping Logic (Dynamic logic can be improved later, hardcoded for consistency with page)
    const shippingCost = cartTotal > 999 ? 0 : 20;
    const grandTotal = cartTotal + shippingCost;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        const payload = {
            billing: formData,
            shipping: formData,
            line_items: cart.map(item => ({
                product_id: item.product_id,
                variation_id: item.id !== item.product_id ? item.id : 0,
                quantity: item.quantity
            })),
            payment_method: 'cod'
        };

        try {
            // Use Next.js Proxy to avoid CORS and ensure correct routing
            const res = await fetch('/api/wp/wcs/v1/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Checkout failed');
            }
            setSuccess(true);
            clearCart();
        } catch (err: any) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    if (cart.length === 0 && !success) {
        return (
            <div className="min-h-screen bg-[#FDFDFD]">
                <Navbar />
                <div className="container mx-auto px-6 py-20 text-center">
                    <h1 className="text-3xl font-black text-gray-900 mb-4">Your Cart is Empty</h1>
                    <Link href="/" className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-10 rounded-xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-transform active:scale-95 inline-block">
                        Return to Shop
                    </Link>
                </div>
            </div>
        );
    }

    if (success) {
        return (
            <div className="min-h-screen bg-[#FDFDFD]">
                <Navbar />
                <div className="container mx-auto px-6 py-20 flex flex-col items-center text-center">
                    <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center text-green-500 mb-6">
                        <span className="text-5xl">✓</span>
                    </div>
                    <h1 className="text-4xl font-black text-gray-900 mb-4">Order Received!</h1>
                    <p className="text-gray-500 mb-8 max-w-md">Thank you for your order. We will process it shortly.</p>
                    <Link href="/" className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-10 rounded-xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-transform active:scale-95">
                        Continue Shopping
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
                    <Link href="/cart" className="hover:text-purple-600">Cart</Link>
                    <ChevronRight size={12} />
                    <span className="text-gray-900">Checkout</span>
                </div>
            </div>

            <div className="container mx-auto px-4">
                <h1 className="text-4xl font-black text-gray-900 mb-12 text-center uppercase tracking-tight">Checkout</h1>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    {/* LEFT: Billing Form */}
                    <div className="lg:col-span-2">
                        <div className="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                            <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight mb-8">Billing Details</h2>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">First Name</label>
                                        <input required name="first_name" value={formData.first_name} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="John" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Last Name</label>
                                        <input required name="last_name" value={formData.last_name} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="Doe" />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                                    <input required type="email" name="email" value={formData.email} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="john@example.com" />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Phone</label>
                                    <input required type="tel" name="phone" value={formData.phone} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="(555) 123-4567" />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Street Address</label>
                                    <input required name="address_1" value={formData.address_1} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="123 Main St" />
                                </div>

                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">City</label>
                                        <input required name="city" value={formData.city} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="New York" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">State</label>
                                        <input required name="state" value={formData.state} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="NY" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ZIP / Postcode</label>
                                        <input required name="postcode" value={formData.postcode} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="10001" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Country</label>
                                        <select name="country" value={formData.country} onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium">
                                            <option value="US">United States (US)</option>
                                            <option value="IN">India</option>
                                            <option value="CA">Canada</option>
                                            <option value="GB">United Kingdom</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* RIGHT: Order Summary */}
                    <div className="lg:col-span-1">
                        <div className="bg-purple-50 p-8 rounded-3xl border border-purple-100 sticky top-24">
                            <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight mb-6">Order Summary</h2>

                            <div className="space-y-3 mb-6 max-h-[300px] overflow-y-auto pr-2 no-scrollbar">
                                {cart.map(item => (
                                    <div key={item.id} className="flex justify-between items-start text-sm group">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 bg-white border border-gray-200 rounded-lg p-1 flex-shrink-0">
                                                <img src={item.image} className="w-full h-full object-contain" />
                                            </div>
                                            <div>
                                                <p className="font-bold text-gray-900 line-clamp-1">{item.name}</p>
                                                <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                            </div>
                                        </div>
                                        <span className="font-bold text-gray-700">${(item.price * item.quantity).toFixed(2)}</span>
                                    </div>
                                ))}
                            </div>

                            <div className="border-t border-purple-200 pt-6 space-y-3 mb-6">
                                <div className="flex justify-between text-sm">
                                    <span className="font-bold text-gray-500">Subtotal</span>
                                    <span className="font-black text-gray-900">${cartTotal.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="font-bold text-gray-500">Shipping</span>
                                    {shippingCost === 0 ? (
                                        <span className="text-purple-600 font-bold">Free</span>
                                    ) : (
                                        <span className="font-black text-gray-900">${shippingCost.toFixed(2)}</span>
                                    )}
                                </div>
                                {cartTotal < 999 && (
                                    <p className="text-xs text-gray-500 italic">Add ${(1000 - cartTotal).toFixed(0)} more for Free Shipping</p>
                                )}
                                <div className="h-px bg-purple-200 my-2"></div>
                                <div className="flex justify-between text-xl font-black text-purple-600 items-center">
                                    <span className="text-gray-900 uppercase tracking-wider text-base">Total</span>
                                    <span>${grandTotal.toFixed(2)}</span>
                                </div>
                            </div>

                            {error && (
                                <div className="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 shadow-sm rounded-r" role="alert">
                                    <div className="flex">
                                        <div className="py-1"><svg className="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z" /></svg></div>
                                        <div>
                                            <p className="font-bold">Error</p>
                                            <p className="text-sm" dangerouslySetInnerHTML={{ __html: error }}></p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <button
                                onClick={handleSubmit}
                                disabled={loading}
                                className={`w-full bg-purple-600 text-white py-4 rounded-xl font-bold uppercase tracking-wider shadow-lg shadow-purple-200 hover:bg-purple-700 transition-transform active:scale-95 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
                            >
                                {loading ? 'Processing...' : 'Place Order'}
                            </button>

                            <div className="mt-6 flex items-center justify-center gap-2 opacity-50">
                                <span className="text-[10px] font-bold text-gray-400 uppercase">Secure Encrypted Payment</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    );
}
