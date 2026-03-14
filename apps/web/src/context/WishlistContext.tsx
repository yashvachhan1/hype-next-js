'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

interface WishlistContextType {
    wishlist: number[];
    toggleWishlist: (productId: number) => void;
    isInWishlist: (productId: number) => boolean;
}

const WishlistContext = createContext<WishlistContextType | undefined>(undefined);

export function WishlistProvider({ children }: { children: ReactNode }) {
    const [wishlist, setWishlist] = useState<number[]>([]);
    const [loading, setLoading] = useState(true);

    const getStorageKey = () => {
        if (typeof window === 'undefined') return 'hype_wishlist_guest';
        const storedUser = localStorage.getItem('user');
        if (storedUser) {
            try {
                const u = JSON.parse(storedUser);
                return u.id ? `hype_wishlist_user_${u.id}` : 'hype_wishlist_guest';
            } catch (e) { return 'hype_wishlist_guest'; }
        }
        return 'hype_wishlist_guest';
    };

    useEffect(() => {
        const loadWishlist = () => {
            const key = getStorageKey();
            const stored = localStorage.getItem(key);
            if (stored) {
                try {
                    setWishlist(JSON.parse(stored));
                } catch (e) {
                    setWishlist([]);
                }
            } else {
                setWishlist([]);
            }
            setLoading(false);
        };

        loadWishlist();
        window.addEventListener('storage', loadWishlist);
        return () => window.removeEventListener('storage', loadWishlist);
    }, []);

    // Sync on user change
    useEffect(() => {
        const key = getStorageKey();
        const stored = localStorage.getItem(key);
        setWishlist(stored ? JSON.parse(stored) : []);
    }, [typeof window !== 'undefined' ? localStorage.getItem('user') : null]);

    // Save on change
    useEffect(() => {
        if (!loading) {
            const key = getStorageKey();
            localStorage.setItem(key, JSON.stringify(wishlist));
        }
    }, [wishlist, loading]);

    const toggleWishlist = (productId: number | string) => {
        const id = Number(productId);
        setWishlist(prev => {
            if (prev.includes(id)) {
                return prev.filter(i => i !== id);
            } else {
                return [...prev, id];
            }
        });
    };

    const isInWishlist = (productId: number | string) => wishlist.includes(Number(productId));

    return (
        <WishlistContext.Provider value={{ wishlist, toggleWishlist, isInWishlist }}>
            {children}
        </WishlistContext.Provider>
    );
}

export function useWishlist() {
    const context = useContext(WishlistContext);
    if (context === undefined) {
        throw new Error('useWishlist must be used within a WishlistProvider');
    }
    return context;
}
