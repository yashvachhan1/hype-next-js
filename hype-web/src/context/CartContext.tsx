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

    const API_URL = '/api/wp/wc/store/v1/cart';

    const getHeaders = () => {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
        };
        const cartToken = localStorage.getItem('wc_cart_token');
        const wpNonce = localStorage.getItem('wp_nonce');

        if (cartToken) headers['Cart-Token'] = cartToken;
        if (wpNonce) headers['X-WP-Nonce'] = wpNonce;

        return headers;
    };

    const handleApiResponse = async (res: Response) => {
        // Capture Cart-Token if WooCommerce provides it (for guest carts)
        const newCartToken = res.headers.get('Cart-Token');
        if (newCartToken) {
            localStorage.setItem('wc_cart_token', newCartToken);
        }

        if (!res.ok) throw new Error('Cart API Error');
        const data = await res.json();

        // Map WC Store format to our CartItem format
        if (data.items) {
            const mappedCart: CartItem[] = data.items.map((item: any) => ({
                id: item.key, // Store API uses a string key for cart items, Next.js UI expects it as 'id'
                product_id: item.id,
                name: item.name,
                price: parseInt(item.prices.price) / 100, // API returns prices in cents
                quantity: item.quantity,
                image: item.images?.[0]?.src || '',
                sku: item.sku,
                variation_data: item.variation?.map((v: any) => `${v.attribute}: ${v.value}`).join(', ') || ''
            }));
            setCart(mappedCart);
        } else {
            setCart([]);
        }
    };

    const fetchCart = async () => {
        try {
            setLoading(true);
            const res = await fetch(API_URL, {
                headers: getHeaders(),
                credentials: 'include'
            });
            await handleApiResponse(res);
        } catch (e) {
            console.error('Failed to fetch cart', e);
        } finally {
            setLoading(false);
        }
    };

    // Load from WC on mount
    useEffect(() => {
        fetchCart();
    }, []);

    const addToCart = async (newItems: CartItem[]) => {
        // We only process one item at a time for WC Store API in this basic implementation
        // For multiple items, we'd need loop promises
        setLoading(true);
        try {
            for (const item of newItems) {
                const res = await fetch(`${API_URL}/add-item`, {
                    method: 'POST',
                    headers: getHeaders(),
                    credentials: 'include',
                    body: JSON.stringify({
                        id: item.product_id, // Add by product/variation ID
                        quantity: item.quantity
                    })
                });
                await handleApiResponse(res);
            }
        } catch (e) {
            console.error('Add to cart failed', e);
        } finally {
            setLoading(false);
        }
    };

    const removeFromCart = async (key: number | string) => {
        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/remove-item?key=${key}`, {
                method: 'POST', // The WC Store API often prefers POST for item removal
                headers: getHeaders(),
                credentials: 'include'
            });
            await handleApiResponse(res);
        } catch (e) {
            console.error('Remove failed', e);
        } finally {
            setLoading(false);
        }
    };

    const updateQuantity = async (key: number | string, quantity: number) => {
        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/update-item`, {
                method: 'POST',
                headers: getHeaders(),
                credentials: 'include',
                body: JSON.stringify({ key, quantity })
            });
            await handleApiResponse(res);
        } catch (e) {
            console.error('Update failed', e);
        } finally {
            setLoading(false);
        }
    };

    const clearCart = () => setCart([]); // Real WC cart clearing involves looping removes or /items DELETE

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
