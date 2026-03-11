export const fetchWishlist = async () => {
    try {
        const wpNonce = typeof window !== 'undefined' ? localStorage.getItem('wp_nonce') : null;
        const headers: Record<string, string> = { 'cache': 'no-store' };
        if (wpNonce) headers['X-WP-Nonce'] = wpNonce;

        const res = await fetch('/api/wp/wcs/v1/wishlist', {
            headers,
            credentials: 'include'
        });
        if (!res.ok) throw new Error('Failed to fetch wishlist');
        return await res.json();
    } catch (error) {
        console.error('Wishlist API Error:', error);
        return { ids: [] };
    }
};

export const toggleWishlist = async (productId: number) => {
    try {
        const wpNonce = typeof window !== 'undefined' ? localStorage.getItem('wp_nonce') : null;
        const headers: Record<string, string> = { 'Content-Type': 'application/json' };
        if (wpNonce) headers['X-WP-Nonce'] = wpNonce;

        const res = await fetch('/api/wp/wcs/v1/wishlist', {
            method: 'POST',
            headers,
            credentials: 'include',
            body: JSON.stringify({ product_id: productId })
        });
        return await res.json();
    } catch (error) {
        console.error('Wishlist Toggle Error:', error);
        throw error;
    }
};
