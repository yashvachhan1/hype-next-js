'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

export interface CartItem {
    id: number | string; // Cart Item key from WC
    product_id: number; // Parent Product ID
    name: string;
    price: number;
    quantity: number;
    image: string;
    sku?: string;
    variation_data?: string;
}

interface CartContextType {
    cart: CartItem[];
    addToCart: (items: CartItem[]) => Promise<void>;
    removeFromCart: (key: number | string) => Promise<void>;
    updateQuantity: (key: number | string, quantity: number) => Promise<void>;
    clearCart: () => void;
    cartTotal: number;
    cartCount: number;
    loading: boolean;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [loading, setLoading] = useState(true);

    // Get stable storage key based on current user
    const getStorageKey = () => {
        if (typeof window === 'undefined') return 'hype_cart_guest';
        const storedUser = localStorage.getItem('user');
        if (storedUser) {
            try {
                const u = JSON.parse(storedUser);
                return u.id ? `hype_cart_user_${u.id}` : 'hype_cart_guest';
            } catch (e) { return 'hype_cart_guest'; }
        }
        return 'hype_cart_guest';
    };

    // Load on mount and whenever user changes
    useEffect(() => {
        const loadCart = () => {
            const key = getStorageKey();
            const stored = localStorage.getItem(key);
            if (stored) {
                try {
                    setCart(JSON.parse(stored));
                } catch (e) {
                    setCart([]);
                }
            } else {
                setCart([]);
            }
            setLoading(false);
        };

        loadCart();

        // Listen for storage events (e.g. login/logout)
        window.addEventListener('storage', loadCart);
        return () => window.removeEventListener('storage', loadCart);
    }, []);

    // Also reload when user logs in/out in-app (not just storage event)
    useEffect(() => {
        const key = getStorageKey();
        const stored = localStorage.getItem(key);
        setCart(stored ? JSON.parse(stored) : []);
    }, [typeof window !== 'undefined' ? localStorage.getItem('user') : null]);

    // Save whenever cart changes
    useEffect(() => {
        if (!loading) {
            const key = getStorageKey();
            localStorage.setItem(key, JSON.stringify(cart));
        }
    }, [cart, loading]);

    const addToCart = async (newItems: CartItem[]) => {
        setCart(prev => {
            const updated = [...prev];
            newItems.forEach(item => {
                const prodId = Number(item.product_id);
                const existing = updated.find(i => Number(i.product_id) === prodId);
                if (existing) {
                    existing.quantity += item.quantity;
                } else {
                    updated.push({ ...item, product_id: prodId, id: Date.now() + Math.random() });
                }
            });
            return updated;
        });
    };

    const removeFromCart = async (key: number | string) => {
        setCart(prev => prev.filter(item => item.id !== key));
    };

    const updateQuantity = async (key: number | string, quantity: number) => {
        if (quantity < 1) {
            removeFromCart(key);
            return;
        }
        setCart(prev => prev.map(item =>
            item.id === key ? { ...item, quantity } : item
        ));
    };

    const clearCart = () => {
        setCart([]);
        localStorage.removeItem(getStorageKey());
    };

    const cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const cartCount = cart.reduce((count, item) => count + item.quantity, 0);

    return (
        <CartContext.Provider value={{ cart, addToCart, removeFromCart, updateQuantity, clearCart, cartTotal, cartCount, loading }}>
            {children}
        </CartContext.Provider>
    );
}

export function useCart() {
    const context = useContext(CartContext);
    if (context === undefined) {
        throw new Error('useCart must be used within a CartProvider');
    }
    return context;
}
