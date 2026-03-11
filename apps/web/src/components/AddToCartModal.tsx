'use client';

import { X, CheckCircle } from 'lucide-react';
import Link from 'next/link';
import { useEffect } from 'react';
import { CartItem } from '@/context/CartContext';

interface AddToCartModalProps {
    isOpen: boolean;
    onClose: () => void;
    items: CartItem[];
}

export default function AddToCartModal({ isOpen, onClose, items }: AddToCartModalProps) {

    // Prevent scrolling when modal is open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'unset';
        }
        return () => { document.body.style.overflow = 'unset'; };
    }, [isOpen]);

    if (!isOpen) return null;

    // Use usage of the first item for the main display, but maybe summarize if multiple
    const mainItem = items[0];
    const multiple = items.length > 1;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                onClick={onClose}
            ></div>

            {/* Modal Content */}
            <div className="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div className="p-8 text-center">
                    <button
                        onClick={onClose}
                        className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
                    >
                        <X size={24} />
                    </button>

                    <h2 className="text-2xl font-black text-gray-900 mb-6 flex items-center justify-center gap-2">
                        Added to your cart!
                    </h2>

                    <div className="max-h-[250px] overflow-y-auto no-scrollbar mb-8 border border-gray-100 rounded-xl bg-gray-50">
                        {items.map((item, idx) => (
                            <div key={idx} className="flex items-start gap-4 p-4 border-b border-gray-100 last:border-0 text-left">
                                <div className="w-16 h-16 bg-white rounded-lg border border-gray-200 p-2 flex-shrink-0">
                                    <img
                                        src={item.image || 'https://placehold.co/100x100?text=No+Img'}
                                        className="w-full h-full object-contain"
                                    />
                                </div>
                                <div className="flex-1">
                                    <h3 className="font-bold text-gray-900 text-sm line-clamp-2">{item.name}</h3>
                                    {item.variation_data && (
                                        <p className="text-xs text-purple-600 font-bold mt-1 uppercase tracking-wide">{item.variation_data}</p>
                                    )}
                                    <div className="flex items-center gap-2 mt-1">
                                        <span className="text-xs font-bold text-gray-400">Qty: {item.quantity}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex flex-col sm:flex-row gap-3">
                        <button
                            onClick={onClose} // Or link to checkout directly? Usually "Checkout Now" goes to checkout
                            // For now, let's make Checkout Go to cart or checkout? User asked for "Checkout Now" and "View Your Cart"
                            // I'll make Checkout go to /checkout (future) or /cart since we only have cart page request right now.
                            // Actually user said "CHECKOUT NOW" and "VIEW YOUR CART".
                            // I'll assume /checkout exists or just link to /cart for now for both if checkout isn't built.
                            // But wait, the request is "cart ka page bana do" (make cart page). So I will make /cart.
                            className="flex-1 bg-black text-white font-bold py-3.5 rounded-xl uppercase tracking-wider text-xs hover:bg-gray-800 transition"
                        >
                            <Link href="/cart" className="w-full h-full block flex items-center justify-center">
                                Checkout Now
                            </Link>
                        </button>
                        <button
                            onClick={onClose}
                            className="flex-1 bg-white border border-gray-200 text-gray-900 font-bold py-3.5 rounded-xl uppercase tracking-wider text-xs hover:bg-gray-50 transition"
                        >
                            <Link href="/cart" className="w-full h-full block flex items-center justify-center">
                                View Your Cart
                            </Link>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
