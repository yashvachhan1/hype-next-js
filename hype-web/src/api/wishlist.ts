export const fetchWishlist = async () => {
    try {
        const res = await fetch('/api/wp/wcs/v1/wishlist', { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to fetch wishlist');
        return await res.json();
    } catch (error) {
        console.error('Wishlist API Error:', error);
        return { ids: [] };
    }
};

export const toggleWishlist = async (productId: number) => {
    try {
        const res = await fetch('/api/wp/wcs/v1/wishlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        return await res.json();
    } catch (error) {
        console.error('Wishlist Toggle Error:', error);
        throw error;
    }
};
