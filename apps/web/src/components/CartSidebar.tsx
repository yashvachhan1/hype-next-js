'use client';

import { X, Trash2, ShoppingCart } from 'lucide-react';
import Link from 'next/link';
import { useCart } from '@/context/CartContext';
import { useEffect } from 'react';

interface CartSidebarProps {
    isOpen: boolean;
    onClose: () => void;
}

export default function CartSidebar({ isOpen, onClose }: CartSidebarProps) {
    const { cart, removeFromCart, cartTotal, cartCount, loading } = useCart();

    // Prevent body scroll when open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'unset';
        }
        return () => { document.body.style.overflow = 'unset'; };
    }, [isOpen]);

    return (
        <>
            {/* Backdrop */}
            <div
                className={`fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity z-[60] ${isOpen ? 'opacity-100 visible' : 'opacity-0 invisible'}`}
                onClick={onClose}
            ></div>

            {/* SidebarPanel */}
            <div className={`fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl transform transition-transform duration-300 z-[70] ${isOpen ? 'translate-x-0' : 'translate-x-full'}`}>
                <div className={`flex flex-col h-full transition-opacity ${loading ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>

                    {/* Header */}
                    <div className="p-6 border-b border-gray-100 flex items-center justify-between bg-white relative">
                        <div className="flex items-center gap-3">
                            <ShoppingCart className="w-5 h-5 text-purple-600" />
                            <h2 className="text-lg font-black text-gray-900 uppercase tracking-tight">Your Cart</h2>
                            <span className="bg-purple-100 text-purple-600 text-[10px] font-bold px-2 py-0.5 rounded-full">{cartCount} ITEMS</span>
                        </div>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-900 transition">
                            <X size={24} />
                        </button>
                    </div>

                    {/* Cart Items */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-6 no-scrollbar">
                        {cart.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-center opacity-50">
                                <ShoppingCart size={48} className="mb-4 text-gray-300" />
                                <p className="font-bold text-gray-400">Your cart is empty</p>
                            </div>
                        ) : (
                            cart.map(item => (
                                <div key={item.id} className="flex gap-4 group">
                                    <div className="w-20 h-20 bg-gray-50 rounded-xl border border-gray-100 p-2 flex-shrink-0 flex items-center justify-center">
                                        <img src={item.image} alt={item.name} className="max-w-full max-h-full object-contain" />
                                    </div>
                                    <div className="flex-1 flex flex-col justify-center">
                                        <h3 className="font-bold text-gray-900 text-sm leading-tight mb-1 line-clamp-2">{item.name}</h3>
                                        {item.variation_data && (
                                            <p className="text-[10px] bg-gray-100 text-gray-500 font-bold px-2 py-0.5 rounded w-fit mb-1">{item.variation_data}</p>
                                        )}
                                        <div className="flex items-center justify-between mt-auto">
                                            <div className="text-xs font-bold text-gray-500">
                                                Qty: <span className="text-gray-900">{item.quantity}</span> × ${item.price.toFixed(2)}
                                            </div>
                                            <span className="font-black text-purple-600 text-sm">${(item.quantity * item.price).toFixed(2)}</span>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => removeFromCart(item.id)}
                                        className="h-full px-2 text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100"
                                    >
                                        <Trash2 size={16} />
                                    </button>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Footer */}
                    {cart.length > 0 && (
                        <div className="p-6 border-t border-gray-100 bg-gray-50">
                            <div className="flex justify-between items-end mb-4">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-widest">Subtotal</span>
                                <span className="text-2xl font-black text-gray-900">${cartTotal.toFixed(2)}</span>
                            </div>
                            <div className="space-y-3">
                                <Link
                                    href="/checkout"
                                    onClick={onClose}
                                    className="block w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl text-center uppercase tracking-wider text-xs shadow-lg shadow-purple-200 transition-transform active:scale-95"
                                >
                                    Proceed to Checkout
                                </Link>
                                <Link
                                    href="/cart"
                                    onClick={onClose}
                                    className="block w-full bg-white border border-gray-200 hover:border-gray-300 text-gray-900 font-bold py-4 rounded-xl text-center uppercase tracking-wider text-xs transition"
                                >
                                    View Cart
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
